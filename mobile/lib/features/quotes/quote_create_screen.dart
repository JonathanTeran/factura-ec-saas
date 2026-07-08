import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../core/api/v1_api_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/page_header.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/company_provider.dart';
import '../../data/providers/quote_provider.dart';

/// Línea editable de la proforma (producto + cantidad).
class _QuoteLine {
  final ApiProduct product;
  double quantity;

  _QuoteLine(this.product, this.quantity);

  double get subtotal => quantity * product.unitPrice;
  double get taxValue => subtotal * (product.taxRate / 100);
  double get total => subtotal + taxValue;

  ApiQuoteItem toItem() => ApiQuoteItem(
        productId: product.id,
        description: product.name,
        quantity: quantity,
        unitPrice: product.unitPrice,
        discount: 0,
        taxRate: product.taxRate,
        subtotal: double.parse(subtotal.toStringAsFixed(2)),
        taxValue: double.parse(taxValue.toStringAsFixed(2)),
        total: double.parse(total.toStringAsFixed(2)),
      );
}

/// Crear proforma/cotización: cliente + productos + vigencia + notas.
class QuoteCreateScreen extends ConsumerStatefulWidget {
  const QuoteCreateScreen({super.key});

  @override
  ConsumerState<QuoteCreateScreen> createState() => _QuoteCreateScreenState();
}

class _QuoteCreateScreenState extends ConsumerState<QuoteCreateScreen> {
  final _notesCtrl = TextEditingController();
  final _termsCtrl = TextEditingController();

  ApiCustomer? _customer;
  final List<_QuoteLine> _lines = [];
  DateTime _issueDate = DateTime.now();
  DateTime? _expiryDate;
  bool _saving = false;

  @override
  void dispose() {
    _notesCtrl.dispose();
    _termsCtrl.dispose();
    super.dispose();
  }

  void _snack(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(message)));
  }

  Future<void> _pickCustomer() async {
    final selected = await _showSearchSheet<ApiCustomer>(
      title: 'Seleccionar cliente',
      loader: (query) async {
        final api = ref.read(v1ApiServiceProvider);
        final result = await api.customers(search: query, perPage: 30);
        return result.items;
      },
      titleOf: (c) => c.name,
      subtitleOf: (c) => c.identificationNumber,
    );
    if (selected != null) setState(() => _customer = selected);
  }

  Future<void> _addProduct() async {
    final selected = await _showSearchSheet<ApiProduct>(
      title: 'Agregar producto',
      loader: (query) async {
        final api = ref.read(v1ApiServiceProvider);
        final result = await api.products(search: query, perPage: 30);
        return result.items;
      },
      titleOf: (p) => p.name,
      subtitleOf: (p) =>
          '\$${p.unitPrice.toStringAsFixed(2)} · IVA ${p.taxRate.toStringAsFixed(0)}%',
    );
    if (selected != null) {
      setState(() => _lines.add(_QuoteLine(selected, 1)));
    }
  }

  /// Hoja genérica de búsqueda + selección.
  Future<T?> _showSearchSheet<T>({
    required String title,
    required Future<List<T>> Function(String query) loader,
    required String Function(T) titleOf,
    required String Function(T) subtitleOf,
  }) {
    return showModalBottomSheet<T>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => _SearchSheet<T>(
        title: title,
        loader: loader,
        titleOf: titleOf,
        subtitleOf: subtitleOf,
      ),
    );
  }

  Future<void> _pickDate({required bool expiry}) async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: expiry ? (_expiryDate ?? now.add(const Duration(days: 15))) : _issueDate,
      firstDate: expiry ? _issueDate : now.subtract(const Duration(days: 30)),
      lastDate: now.add(const Duration(days: 365)),
    );
    if (picked == null) return;
    setState(() {
      if (expiry) {
        _expiryDate = picked;
      } else {
        _issueDate = picked;
        if (_expiryDate != null && _expiryDate!.isBefore(picked)) {
          _expiryDate = null;
        }
      }
    });
  }

  Future<void> _save() async {
    if (_customer == null) {
      _snack('Selecciona un cliente.');
      return;
    }
    if (_lines.isEmpty) {
      _snack('Agrega al menos un producto.');
      return;
    }

    setState(() => _saving = true);
    try {
      final me = await ref.read(meProvider.future);
      final companies = await ref.read(companiesProvider.future);
      final company = companies.isEmpty
          ? null
          : companies.firstWhere(
              (c) => c.id == me.currentCompanyId,
              orElse: () => companies.first,
            );
      if (company == null) {
        throw const ApiException('Primero configura tu empresa.');
      }

      await ref.read(v1ApiServiceProvider).createQuote(
            companyId: company.id,
            customerId: _customer!.id,
            issueDate: _issueDate,
            expiryDate: _expiryDate,
            items: _lines.map((l) => l.toItem()).toList(),
            notes: _notesCtrl.text,
            paymentTerms: _termsCtrl.text,
          );

      ref.read(quotesRefreshProvider.notifier).state++;
      if (!mounted) return;
      _snack('Proforma creada.');
      context.pop();
    } on ApiException catch (e) {
      _snack(e.message);
    } catch (e) {
      _snack(e.toString());
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final money = NumberFormat.currency(locale: 'es_EC', symbol: r'$');
    final dates = DateFormat('dd/MM/yyyy');
    final subtotal = _lines.fold<double>(0, (s, l) => s + l.subtotal);
    final tax = _lines.fold<double>(0, (s, l) => s + l.taxValue);
    final total = subtotal + tax;

    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            PageHeader(
              title: 'Nueva proforma',
              subtitle: 'Cotización para tu cliente',
              trailing: IconButton.filledTonal(
                tooltip: 'Volver',
                onPressed: () => context.pop(),
                icon: const Icon(Icons.close_rounded),
              ),
            ),
            const SizedBox(height: 12),
            Expanded(
              child: SingleChildScrollView(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Cliente
                    GlassPanel(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Cliente',
                            style: TextStyle(
                              fontFamily: 'Avenir Next',
                              fontWeight: FontWeight.w700,
                              color: AppColors.textPrimary,
                            ),
                          ),
                          const SizedBox(height: 8),
                          InkWell(
                            onTap: _pickCustomer,
                            borderRadius: BorderRadius.circular(12),
                            child: Container(
                              width: double.infinity,
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                border: Border.all(color: AppColors.border),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Row(
                                children: [
                                  const Icon(Icons.person_search_rounded,
                                      color: AppColors.textMuted),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Text(
                                      _customer?.name ??
                                          'Toca para seleccionar cliente',
                                      style: TextStyle(
                                        fontFamily: 'Avenir Next',
                                        fontWeight: _customer == null
                                            ? FontWeight.w400
                                            : FontWeight.w600,
                                        color: _customer == null
                                            ? AppColors.textMuted
                                            : AppColors.textPrimary,
                                      ),
                                    ),
                                  ),
                                  const Icon(Icons.chevron_right_rounded,
                                      color: AppColors.textMuted),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 14),

                    // Productos
                    GlassPanel(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Expanded(
                                child: Text(
                                  'Detalle',
                                  style: TextStyle(
                                    fontFamily: 'Avenir Next',
                                    fontWeight: FontWeight.w700,
                                    color: AppColors.textPrimary,
                                  ),
                                ),
                              ),
                              TextButton.icon(
                                onPressed: _addProduct,
                                icon: const Icon(Icons.add_rounded, size: 18),
                                label: const Text('Agregar'),
                              ),
                            ],
                          ),
                          if (_lines.isEmpty)
                            const Padding(
                              padding: EdgeInsets.symmetric(vertical: 14),
                              child: Text(
                                'Sin productos todavía.',
                                style: TextStyle(
                                  fontFamily: 'Avenir Next',
                                  color: AppColors.textMuted,
                                ),
                              ),
                            ),
                          for (var i = 0; i < _lines.length; i++) ...[
                            const SizedBox(height: 8),
                            Row(
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        _lines[i].product.name,
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        style: const TextStyle(
                                          fontFamily: 'Avenir Next',
                                          fontWeight: FontWeight.w600,
                                          color: AppColors.textPrimary,
                                        ),
                                      ),
                                      Text(
                                        '${money.format(_lines[i].product.unitPrice)} · IVA ${_lines[i].product.taxRate.toStringAsFixed(0)}%',
                                        style: const TextStyle(
                                          fontFamily: 'Avenir Next',
                                          fontSize: 12,
                                          color: AppColors.textMuted,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                IconButton(
                                  onPressed: _lines[i].quantity <= 1
                                      ? null
                                      : () => setState(
                                          () => _lines[i].quantity -= 1),
                                  icon: const Icon(
                                      Icons.remove_circle_outline_rounded),
                                ),
                                Text(
                                  _lines[i].quantity.toStringAsFixed(0),
                                  style: const TextStyle(
                                    fontFamily: 'Avenir Next',
                                    fontWeight: FontWeight.w700,
                                    fontSize: 16,
                                    color: AppColors.textPrimary,
                                  ),
                                ),
                                IconButton(
                                  onPressed: () => setState(
                                      () => _lines[i].quantity += 1),
                                  icon: const Icon(
                                      Icons.add_circle_outline_rounded),
                                ),
                                IconButton(
                                  onPressed: () =>
                                      setState(() => _lines.removeAt(i)),
                                  icon: const Icon(
                                    Icons.delete_outline_rounded,
                                    color: AppColors.error,
                                    size: 20,
                                  ),
                                ),
                              ],
                            ),
                          ],
                          if (_lines.isNotEmpty) ...[
                            const Divider(height: 22),
                            _TotalLine(
                                label: 'Subtotal',
                                value: money.format(subtotal)),
                            _TotalLine(label: 'IVA', value: money.format(tax)),
                            _TotalLine(
                              label: 'Total',
                              value: money.format(total),
                              emphasize: true,
                            ),
                          ],
                        ],
                      ),
                    ),
                    const SizedBox(height: 14),

                    // Fechas + notas
                    GlassPanel(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: OutlinedButton.icon(
                                  onPressed: () => _pickDate(expiry: false),
                                  icon: const Icon(
                                      Icons.calendar_today_rounded,
                                      size: 16),
                                  label: Text(
                                      'Emisión: ${dates.format(_issueDate)}'),
                                ),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: OutlinedButton.icon(
                                  onPressed: () => _pickDate(expiry: true),
                                  icon: const Icon(Icons.event_busy_rounded,
                                      size: 16),
                                  label: Text(
                                    _expiryDate == null
                                        ? 'Vigencia (opcional)'
                                        : 'Vence: ${dates.format(_expiryDate!)}',
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          TextField(
                            controller: _termsCtrl,
                            decoration: const InputDecoration(
                              labelText: 'Condiciones de pago (opcional)',
                              hintText: 'Ej: 50% anticipo, 50% contra entrega',
                            ),
                          ),
                          const SizedBox(height: 12),
                          TextField(
                            controller: _notesCtrl,
                            maxLines: 3,
                            maxLength: 500,
                            decoration: const InputDecoration(
                              labelText: 'Notas (opcional)',
                              alignLabelWithHint: true,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton.icon(
                        onPressed: _saving ? null : _save,
                        icon: _saving
                            ? const SizedBox(
                                width: 14,
                                height: 14,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: Colors.white,
                                ),
                              )
                            : const Icon(Icons.save_outlined),
                        label: const Text('Guardar proforma'),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _TotalLine extends StatelessWidget {
  final String label;
  final String value;
  final bool emphasize;

  const _TotalLine({
    required this.label,
    required this.value,
    this.emphasize = false,
  });

  @override
  Widget build(BuildContext context) {
    final style = TextStyle(
      fontFamily: 'Avenir Next',
      fontWeight: emphasize ? FontWeight.w800 : FontWeight.w500,
      fontSize: emphasize ? 17 : 14,
      color: emphasize ? AppColors.textPrimary : AppColors.textSecondary,
    );

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        children: [
          Expanded(child: Text(label, style: style)),
          Text(value, style: style),
        ],
      ),
    );
  }
}

/// Hoja de búsqueda con debounce simple sobre un loader remoto.
class _SearchSheet<T> extends StatefulWidget {
  final String title;
  final Future<List<T>> Function(String query) loader;
  final String Function(T) titleOf;
  final String Function(T) subtitleOf;

  const _SearchSheet({
    required this.title,
    required this.loader,
    required this.titleOf,
    required this.subtitleOf,
  });

  @override
  State<_SearchSheet<T>> createState() => _SearchSheetState<T>();
}

class _SearchSheetState<T> extends State<_SearchSheet<T>> {
  List<T> _items = [];
  bool _loading = true;
  String _query = '';

  @override
  void initState() {
    super.initState();
    _load('');
  }

  Future<void> _load(String query) async {
    setState(() => _loading = true);
    try {
      final items = await widget.loader(query);
      if (!mounted || query != _query) return;
      setState(() => _items = items);
    } catch (_) {
      if (mounted) setState(() => _items = []);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).viewInsets.bottom;

    return Padding(
      padding: EdgeInsets.fromLTRB(20, 16, 20, 16 + bottomInset),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            widget.title,
            style: const TextStyle(
              fontFamily: 'Avenir Next',
              fontWeight: FontWeight.w800,
              fontSize: 18,
              color: AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 12),
          TextField(
            autofocus: true,
            decoration: const InputDecoration(
              prefixIcon: Icon(Icons.search_rounded),
              hintText: 'Buscar…',
            ),
            onChanged: (v) {
              _query = v;
              _load(v);
            },
          ),
          const SizedBox(height: 12),
          SizedBox(
            height: 320,
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : _items.isEmpty
                    ? const Center(
                        child: Text(
                          'Sin resultados.',
                          style: TextStyle(
                            fontFamily: 'Avenir Next',
                            color: AppColors.textMuted,
                          ),
                        ),
                      )
                    : ListView.separated(
                        itemCount: _items.length,
                        separatorBuilder: (_, _) => const Divider(height: 1),
                        itemBuilder: (ctx, i) => ListTile(
                          title: Text(
                            widget.titleOf(_items[i]),
                            style: const TextStyle(
                              fontFamily: 'Avenir Next',
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                          subtitle: Text(
                            widget.subtitleOf(_items[i]),
                            style: const TextStyle(
                              fontFamily: 'Avenir Next',
                              fontSize: 12,
                            ),
                          ),
                          onTap: () => Navigator.pop(ctx, _items[i]),
                        ),
                      ),
          ),
        ],
      ),
    );
  }
}
