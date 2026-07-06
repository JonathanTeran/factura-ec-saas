import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/api/v1_api_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/money_text.dart';
import '../../core/widgets/page_header.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/dashboard_provider.dart';
import '../../data/providers/document_provider.dart';
import '../../data/providers/report_provider.dart';

class NewDocumentScreen extends ConsumerStatefulWidget {
  /// Tipo de comprobante inicial (viene del menú "+" del inicio).
  final String? initialType;

  const NewDocumentScreen({super.key, this.initialType});

  @override
  ConsumerState<NewDocumentScreen> createState() => _NewDocumentScreenState();
}

class _NewDocumentScreenState extends ConsumerState<NewDocumentScreen> {
  int _step = 0;
  late String _selectedType = widget.initialType ?? '01';
  bool _notifyCustomer = true;
  bool _loading = true;
  bool _submitting = false;
  String? _errorText;

  // Builder de la factura: ítems, formas de pago e información adicional.
  final List<InvoiceLine> _lines = [];
  final List<InvoicePayment> _payments = [];
  final List<({String name, String value})> _additional = [];
  final TextEditingController _tipCtrl = TextEditingController(text: '0');
  final TextEditingController _termCtrl = TextEditingController(text: '0');

  List<ApiCompany> _companies = const [];
  List<ApiEmissionPoint> _emissionPoints = const [];
  List<ApiCustomer> _customers = const [];
  List<ApiProduct> _products = const [];

  int? _companyId;
  int? _emissionPointId;
  int? _customerId;

  ApiDocument? _createdDocument;
  ApiDocumentStatus? _documentStatus;

  @override
  void initState() {
    super.initState();
    unawaited(_loadOptions());
  }

  @override
  void dispose() {
    _tipCtrl.dispose();
    _termCtrl.dispose();
    super.dispose();
  }

  // ── Totales calculados en vivo sobre las líneas ──
  double get _tip => double.tryParse(_tipCtrl.text.replaceAll(',', '.')) ?? 0;
  double get _subtotal0 =>
      _lines.where((l) => l.taxRate <= 0).fold(0.0, (s, l) => s + l.base);
  double get _subtotalIva =>
      _lines.where((l) => l.taxRate > 0).fold(0.0, (s, l) => s + l.base);
  double get _totalDiscount => _lines.fold(0.0, (s, l) => s + l.lineDiscount);
  double get _totalIva => _lines.fold(0.0, (s, l) => s + l.taxValue);
  double get _grandTotal =>
      _lines.fold(0.0, (s, l) => s + l.total) + (_tip < 0 ? 0 : _tip);
  double get _paid => _payments.fold(0.0, (s, p) => s + p.amount);

  Future<void> _loadOptions() async {
    setState(() {
      _loading = true;
      _errorText = null;
    });

    try {
      final api = ref.read(v1ApiServiceProvider);
      final companies = await api.companies();
      final customersPage = await api.customers(perPage: 100);
      final productsPage = await api.products(perPage: 100);

      int? companyId;
      List<ApiEmissionPoint> emissionPoints = const [];
      if (companies.isNotEmpty) {
        companyId = companies.first.id;
        emissionPoints = await api.companyEmissionPoints(companyId);
      }

      setState(() {
        _companies = companies;
        _customers = customersPage.items;
        _products = productsPage.items;
        _companyId = companyId;
        _emissionPoints = emissionPoints;
        _emissionPointId = emissionPoints.isNotEmpty
            ? emissionPoints.first.id
            : null;
        _customerId = customersPage.items.isNotEmpty
            ? customersPage.items.first.id
            : null;
      });
    } catch (error) {
      setState(() => _errorText = error.toString());
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _loadEmissionPoints(int companyId) async {
    setState(() {
      _loading = true;
      _errorText = null;
    });
    try {
      final emissionPoints = await ref
          .read(v1ApiServiceProvider)
          .companyEmissionPoints(companyId);
      setState(() {
        _emissionPoints = emissionPoints;
        _emissionPointId = emissionPoints.isNotEmpty
            ? emissionPoints.first.id
            : null;
      });
    } catch (error) {
      setState(() => _errorText = error.toString());
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  void _invalidateData() {
    ref.invalidate(dashboardViewDataProvider);
    ref.invalidate(sentDocumentsProvider);
    ref.invalidate(receivedDocumentsProvider);
    ref.invalidate(draftDocumentsProvider);
    ref.invalidate(reportsViewDataProvider);
  }

  Future<void> _advanceFlow() async {
    if (_submitting || _loading) return;
    if (_step == 0) {
      await _createDocument();
      return;
    }
    if (_step == 1) {
      await _sendAndValidate();
      return;
    }

    setState(() {
      _step = 0;
      _errorText = null;
      _createdDocument = null;
      _documentStatus = null;
      _lines.clear();
      _payments.clear();
      _additional.clear();
      _tipCtrl.text = '0';
      _termCtrl.text = '0';
    });
  }

  Future<void> _createDocument() async {
    if (_companyId == null ||
        _customerId == null ||
        _emissionPointId == null) {
      setState(() => _errorText = 'Faltan datos para crear el documento.');
      return;
    }
    if (_lines.isEmpty) {
      setState(() => _errorText = 'Agregá al menos un producto.');
      return;
    }

    setState(() {
      _submitting = true;
      _errorText = null;
    });
    try {
      final term = int.tryParse(_termCtrl.text.trim()) ?? 0;
      final document = await ref
          .read(v1ApiServiceProvider)
          .createInvoice(
            CreateInvoiceInput(
              companyId: _companyId!,
              customerId: _customerId!,
              emissionPointId: _emissionPointId!,
              documentType: _selectedType,
              lines: _lines,
              payments: _payments,
              paymentTerm: term,
              tip: _tip < 0 ? 0 : _tip,
              additionalInfo: _additional,
            ),
          );

      _invalidateData();
      setState(() {
        _createdDocument = document;
        _step = 1;
      });
    } catch (error) {
      setState(() => _errorText = error.toString());
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  Future<void> _sendAndValidate() async {
    final document = _createdDocument;
    if (document == null) {
      setState(() => _errorText = 'Primero debes crear el documento.');
      return;
    }

    setState(() {
      _submitting = true;
      _errorText = null;
    });
    try {
      final sent = await ref
          .read(v1ApiServiceProvider)
          .sendDocument(document.id);
      final status = await ref
          .read(v1ApiServiceProvider)
          .checkDocumentStatus(sent.id);

      _invalidateData();
      setState(() {
        _createdDocument = sent;
        _documentStatus = status;
        _step = 2;
      });
    } catch (error) {
      setState(() => _errorText = error.toString());
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  String get _ctaText {
    switch (_step) {
      case 0:
        return 'Crear documento';
      case 1:
        return 'Enviar y validar SRI';
      default:
        return 'Crear otro documento';
    }
  }

  // ─────────────────────────── Builder de la factura ───────────────────────

  Widget _sectionTitle(String text) => Text(
    text,
    style: const TextStyle(
      fontFamily: 'Avenir Next',
      fontWeight: FontWeight.w700,
      fontSize: 16,
      color: AppColors.textPrimary,
    ),
  );

  Widget _addTile(String label, VoidCallback? onTap) => Align(
    alignment: Alignment.centerLeft,
    child: OutlinedButton.icon(
      onPressed: onTap,
      icon: const Icon(Icons.add_rounded, size: 18),
      label: Text(label),
    ),
  );

  Widget _itemCard({required Widget child, VoidCallback? onRemove}) => Container(
    margin: const EdgeInsets.only(bottom: 8),
    padding: const EdgeInsets.fromLTRB(14, 12, 4, 12),
    decoration: BoxDecoration(
      color: AppColors.surfaceDark,
      borderRadius: BorderRadius.circular(14),
      border: Border.all(color: AppColors.border),
    ),
    child: Row(
      children: [
        Expanded(child: child),
        if (onRemove != null)
          IconButton(
            tooltip: 'Quitar',
            onPressed: onRemove,
            icon: const Icon(
              Icons.close_rounded,
              size: 20,
              color: AppColors.textMuted,
            ),
          ),
      ],
    ),
  );

  String _qtyLabel(double q) =>
      q == q.roundToDouble() ? q.toInt().toString() : q.toString();

  String _paymentLabel(String code) => kSriPaymentMethods
      .firstWhere((m) => m.code == code, orElse: () => (code: code, label: code))
      .label;

  List<Widget> _buildLineItems() {
    if (_lines.isEmpty) {
      return const [
        Text(
          'Aún no agregaste productos.',
          style: TextStyle(fontFamily: 'Avenir Next', color: AppColors.textMuted),
        ),
        SizedBox(height: 8),
      ];
    }
    return [
      for (var i = 0; i < _lines.length; i++)
        _itemCard(
          onRemove: () => setState(() => _lines.removeAt(i)),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                _lines[i].product.name,
                style: const TextStyle(
                  fontFamily: 'Avenir Next',
                  fontWeight: FontWeight.w700,
                  color: AppColors.textPrimary,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                '${_qtyLabel(_lines[i].quantity)} × ${currency(_lines[i].product.unitPrice)}'
                '${_lines[i].lineDiscount > 0 ? '  ·  -${currency(_lines[i].lineDiscount)}' : ''}'
                '   =   ${currency(_lines[i].total)}',
                style: const TextStyle(
                  fontFamily: 'Avenir Next',
                  color: AppColors.textSecondary,
                  fontSize: 13,
                ),
              ),
            ],
          ),
        ),
    ];
  }

  List<Widget> _buildPayments() {
    if (_payments.isEmpty) {
      return const [
        Text(
          'Si no agregás una, se registra Efectivo por el total.',
          style: TextStyle(fontFamily: 'Avenir Next', color: AppColors.textMuted),
        ),
        SizedBox(height: 8),
      ];
    }
    return [
      for (var i = 0; i < _payments.length; i++)
        _itemCard(
          onRemove: () => setState(() => _payments.removeAt(i)),
          child: Text(
            '${_paymentLabel(_payments[i].code)}   ·   ${currency(_payments[i].amount)}',
            style: const TextStyle(
              fontFamily: 'Avenir Next',
              fontWeight: FontWeight.w600,
              color: AppColors.textPrimary,
            ),
          ),
        ),
    ];
  }

  List<Widget> _buildAdditional() {
    return [
      for (var i = 0; i < _additional.length; i++)
        _itemCard(
          onRemove: () => setState(() => _additional.removeAt(i)),
          child: Text(
            '${_additional[i].name}: ${_additional[i].value}',
            style: const TextStyle(
              fontFamily: 'Avenir Next',
              color: AppColors.textPrimary,
            ),
          ),
        ),
    ];
  }

  Widget _totalsRow(String label, String value, {bool strong = false}) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 5),
    child: Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: TextStyle(
            fontFamily: 'Avenir Next',
            color: strong ? AppColors.textPrimary : AppColors.textSecondary,
            fontWeight: strong ? FontWeight.w800 : FontWeight.w500,
            fontSize: strong ? 18 : 14,
          ),
        ),
        Text(
          value,
          style: TextStyle(
            fontFamily: 'Avenir Next',
            color: strong ? AppColors.primary : AppColors.textPrimary,
            fontWeight: strong ? FontWeight.w800 : FontWeight.w600,
            fontSize: strong ? 18 : 14,
          ),
        ),
      ],
    ),
  );

  Widget _totalsPanel() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: AppColors.surfaceDark,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        children: [
          _totalsRow('Subtotal 0%', currency(_subtotal0)),
          _totalsRow('Subtotal IVA', currency(_subtotalIva)),
          if (_totalDiscount > 0)
            _totalsRow('Descuento', '-${currency(_totalDiscount)}'),
          _totalsRow('IVA', currency(_totalIva)),
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 6),
            child: Row(
              children: [
                const Expanded(
                  child: Text(
                    'Propina',
                    style: TextStyle(
                      fontFamily: 'Avenir Next',
                      color: AppColors.textSecondary,
                    ),
                  ),
                ),
                SizedBox(
                  width: 120,
                  child: TextField(
                    controller: _tipCtrl,
                    keyboardType: const TextInputType.numberWithOptions(
                      decimal: true,
                    ),
                    textAlign: TextAlign.right,
                    onChanged: (_) => setState(() {}),
                    decoration: const InputDecoration(
                      prefixText: '\$ ',
                      isDense: true,
                      contentPadding: EdgeInsets.symmetric(
                        horizontal: 10,
                        vertical: 8,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 18),
          _totalsRow('Total', currency(_grandTotal), strong: true),
        ],
      ),
    );
  }

  Widget _sheetHandle() => Center(
    child: Container(
      width: 40,
      height: 4,
      decoration: BoxDecoration(
        color: AppColors.border,
        borderRadius: BorderRadius.circular(999),
      ),
    ),
  );

  Future<void> _openAddProductSheet() async {
    if (_products.isEmpty) return;
    var selected = _products.first;
    final qtyCtrl = TextEditingController(text: '1');
    final discCtrl = TextEditingController(text: '0');

    final result = await showModalBottomSheet<InvoiceLine>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => Padding(
        padding: EdgeInsets.fromLTRB(
          20,
          14,
          20,
          16 + MediaQuery.of(ctx).viewInsets.bottom,
        ),
        child: StatefulBuilder(
          builder: (ctx, setSheet) {
            final q = double.tryParse(qtyCtrl.text.replaceAll(',', '.')) ?? 1;
            final d = double.tryParse(discCtrl.text.replaceAll(',', '.')) ?? 0;
            final line = InvoiceLine(product: selected, quantity: q, discount: d);
            return Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _sheetHandle(),
                const SizedBox(height: 10),
                _sectionTitle('Agregar producto'),
                const SizedBox(height: 14),
                DropdownButtonFormField<int>(
                  initialValue: selected.id,
                  isExpanded: true,
                  decoration: const InputDecoration(labelText: 'Producto'),
                  items: _products
                      .map(
                        (p) => DropdownMenuItem(
                          value: p.id,
                          child: Text(
                            '${p.name} · ${currency(p.unitPrice)}',
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      )
                      .toList(),
                  onChanged: (v) => setSheet(
                    () => selected = _products.firstWhere((p) => p.id == v),
                  ),
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: qtyCtrl,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        onChanged: (_) => setSheet(() {}),
                        decoration: const InputDecoration(labelText: 'Cantidad'),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: TextField(
                        controller: discCtrl,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        onChanged: (_) => setSheet(() {}),
                        decoration: const InputDecoration(
                          labelText: 'Descuento \$',
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Align(
                  alignment: Alignment.centerRight,
                  child: Text(
                    'Total línea: ${currency(line.total)}',
                    style: const TextStyle(
                      fontFamily: 'Avenir Next',
                      fontWeight: FontWeight.w700,
                      color: AppColors.textPrimary,
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                ElevatedButton(
                  onPressed: () => Navigator.pop(ctx, line),
                  style: ElevatedButton.styleFrom(
                    minimumSize: const Size.fromHeight(50),
                  ),
                  child: const Text('Agregar producto'),
                ),
              ],
            );
          },
        ),
      ),
    );

    qtyCtrl.dispose();
    discCtrl.dispose();
    if (result != null) setState(() => _lines.add(result));
  }

  Future<void> _openAddPaymentSheet() async {
    var code = kSriPaymentMethods.first.code;
    final remaining = (_grandTotal - _paid).clamp(0.0, double.infinity);
    final amountCtrl = TextEditingController(
      text: remaining.toStringAsFixed(2),
    );

    final result = await showModalBottomSheet<InvoicePayment>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => Padding(
        padding: EdgeInsets.fromLTRB(
          20,
          14,
          20,
          16 + MediaQuery.of(ctx).viewInsets.bottom,
        ),
        child: StatefulBuilder(
          builder: (ctx, setSheet) => Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _sheetHandle(),
              const SizedBox(height: 10),
              _sectionTitle('Agregar forma de pago'),
              const SizedBox(height: 14),
              DropdownButtonFormField<String>(
                initialValue: code,
                isExpanded: true,
                decoration: const InputDecoration(labelText: 'Tipo de pago'),
                items: kSriPaymentMethods
                    .map(
                      (m) => DropdownMenuItem(
                        value: m.code,
                        child: Text(m.label),
                      ),
                    )
                    .toList(),
                onChanged: (v) => setSheet(() => code = v ?? code),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: amountCtrl,
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: true,
                ),
                decoration: const InputDecoration(
                  labelText: 'Valor',
                  prefixText: '\$ ',
                ),
              ),
              const SizedBox(height: 12),
              ElevatedButton(
                onPressed: () {
                  final amount =
                      double.tryParse(amountCtrl.text.replaceAll(',', '.')) ??
                      0;
                  Navigator.pop(ctx, InvoicePayment(code: code, amount: amount));
                },
                style: ElevatedButton.styleFrom(
                  minimumSize: const Size.fromHeight(50),
                ),
                child: const Text('Agregar forma de pago'),
              ),
            ],
          ),
        ),
      ),
    );

    amountCtrl.dispose();
    if (result != null) setState(() => _payments.add(result));
  }

  Future<void> _openAddInfoSheet() async {
    final nameCtrl = TextEditingController();
    final valueCtrl = TextEditingController();

    final result = await showModalBottomSheet<({String name, String value})>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => Padding(
        padding: EdgeInsets.fromLTRB(
          20,
          14,
          20,
          16 + MediaQuery.of(ctx).viewInsets.bottom,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _sheetHandle(),
            const SizedBox(height: 10),
            _sectionTitle('Información adicional'),
            const SizedBox(height: 14),
            TextField(
              controller: nameCtrl,
              decoration: const InputDecoration(
                labelText: 'Campo (ej. Dirección, Teléfono)',
              ),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: valueCtrl,
              decoration: const InputDecoration(labelText: 'Valor'),
            ),
            const SizedBox(height: 12),
            ElevatedButton(
              onPressed: () {
                final name = nameCtrl.text.trim();
                final value = valueCtrl.text.trim();
                if (name.isEmpty || value.isEmpty) {
                  Navigator.pop(ctx);
                  return;
                }
                Navigator.pop(ctx, (name: name, value: value));
              },
              style: ElevatedButton.styleFrom(
                minimumSize: const Size.fromHeight(50),
              ),
              child: const Text('Agregar'),
            ),
          ],
        ),
      ),
    );

    nameCtrl.dispose();
    valueCtrl.dispose();
    if (result != null) setState(() => _additional.add(result));
  }

  Widget _stepContent(BuildContext context) {
    if (_loading) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 30),
        child: Center(child: CircularProgressIndicator()),
      );
    }

    // Error real de carga (red / servidor). NO es que falten datos.
    if (_errorText != null &&
        _companies.isEmpty &&
        _customers.isEmpty &&
        _products.isEmpty) {
      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'No pudimos cargar tus datos',
            style: TextStyle(
              fontFamily: 'Avenir Next',
              fontWeight: FontWeight.w700,
              fontSize: 20,
              color: AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Revisá tu conexión a internet e intentá de nuevo.',
            style: TextStyle(
              fontFamily: 'Avenir Next',
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 14),
          OutlinedButton.icon(
            onPressed: _loadOptions,
            icon: const Icon(Icons.refresh_rounded),
            label: const Text('Reintentar'),
          ),
        ],
      );
    }

    // Faltan datos base para poder facturar: estado guiado con accesos directos
    // para crear justo lo que falta.
    if (_companies.isEmpty || _customers.isEmpty || _products.isEmpty) {
      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Casi listo para facturar',
            style: TextStyle(
              fontFamily: 'Avenir Next',
              fontWeight: FontWeight.w700,
              fontSize: 20,
              color: AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Para emitir tu primer documento necesitás tener esto activo:',
            style: TextStyle(
              fontFamily: 'Avenir Next',
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 16),
          _RequirementRow(
            done: _companies.isNotEmpty,
            label: 'Empresa configurada',
            actionLabel: 'Configurar',
            onAction: _companies.isEmpty ? () => context.go('/settings') : null,
          ),
          const SizedBox(height: 10),
          _RequirementRow(
            done: _customers.isNotEmpty,
            label: 'Al menos un cliente',
            actionLabel: 'Crear cliente',
            onAction: _customers.isEmpty
                ? () => context.push('/customers/new')
                : null,
          ),
          const SizedBox(height: 10),
          _RequirementRow(
            done: _products.isNotEmpty,
            label: 'Al menos un producto',
            actionLabel: 'Crear producto',
            onAction: _products.isEmpty
                ? () => context.push('/products/new')
                : null,
          ),
          const SizedBox(height: 16),
          OutlinedButton.icon(
            onPressed: _loadOptions,
            icon: const Icon(Icons.refresh_rounded),
            label: const Text('Volver a comprobar'),
          ),
        ],
      );
    }

    switch (_step) {
      case 0:
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              '1. Crear documento',
              style: TextStyle(
                fontFamily: 'Avenir Next',
                fontWeight: FontWeight.w700,
                fontSize: 20,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Todos los datos se envían al backend real.',
              style: TextStyle(
                fontFamily: 'Avenir Next',
                color: AppColors.textSecondary,
              ),
            ),
            const SizedBox(height: 16),
            DropdownButtonFormField<String>(
              initialValue: _selectedType,
              decoration: const InputDecoration(labelText: 'Tipo'),
              items: const [
                DropdownMenuItem(value: '01', child: Text('Factura')),
                DropdownMenuItem(value: '04', child: Text('Nota de crédito')),
                DropdownMenuItem(value: '05', child: Text('Nota de débito')),
                DropdownMenuItem(value: '07', child: Text('Retención')),
              ],
              onChanged: (value) => setState(() {
                _selectedType = value ?? '01';
              }),
            ),
            const SizedBox(height: 10),
            // Empresa y punto de emisión solo se muestran si hay más de una
            // opción; con una sola quedan preseleccionados y no estorban.
            if (_companies.length > 1) ...[
              DropdownButtonFormField<int>(
                initialValue: _companyId,
                decoration: const InputDecoration(labelText: 'Empresa'),
                items: _companies
                    .map(
                      (company) => DropdownMenuItem(
                        value: company.id,
                        child: Text('${company.businessName} · ${company.ruc}'),
                      ),
                    )
                    .toList(),
                onChanged: (value) {
                  if (value == null) return;
                  setState(() => _companyId = value);
                  unawaited(_loadEmissionPoints(value));
                },
              ),
              const SizedBox(height: 10),
            ],
            if (_emissionPoints.length > 1) ...[
              DropdownButtonFormField<int>(
                initialValue: _emissionPointId,
                decoration: const InputDecoration(
                  labelText: 'Punto de emisión',
                ),
                items: _emissionPoints
                    .map(
                      (point) => DropdownMenuItem(
                        value: point.id,
                        child: Text('${point.code} · ${point.description}'),
                      ),
                    )
                    .toList(),
                onChanged: (value) => setState(() => _emissionPointId = value),
              ),
              const SizedBox(height: 10),
            ],
            DropdownButtonFormField<int>(
              initialValue: _customerId,
              decoration: const InputDecoration(labelText: 'Cliente'),
              items: _customers
                  .map(
                    (customer) => DropdownMenuItem(
                      value: customer.id,
                      child: Text(
                        '${customer.name} · ${customer.identificationNumber}',
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  )
                  .toList(),
              onChanged: (value) => setState(() => _customerId = value),
            ),
            const SizedBox(height: 18),
            _sectionTitle('Productos'),
            const SizedBox(height: 8),
            ..._buildLineItems(),
            _addTile(
              'Agregar producto',
              _products.isEmpty ? null : _openAddProductSheet,
            ),
            const SizedBox(height: 18),
            _sectionTitle('Forma de pago'),
            const SizedBox(height: 8),
            ..._buildPayments(),
            _addTile('Agregar forma de pago', _openAddPaymentSheet),
            const SizedBox(height: 10),
            TextField(
              controller: _termCtrl,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Plazo del pago (días)',
              ),
            ),
            const SizedBox(height: 18),
            _sectionTitle('Información adicional'),
            const SizedBox(height: 8),
            ..._buildAdditional(),
            _addTile('Agregar información', _openAddInfoSheet),
            const SizedBox(height: 18),
            _totalsPanel(),
          ],
        );
      case 1:
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              '2. Validación SRI',
              style: TextStyle(
                fontFamily: 'Avenir Next',
                fontWeight: FontWeight.w700,
                fontSize: 20,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              _createdDocument == null
                  ? 'Documento listo para enviar al SRI.'
                  : 'Documento ${_createdDocument!.documentNumber} creado.',
              style: const TextStyle(
                fontFamily: 'Avenir Next',
                color: AppColors.textSecondary,
              ),
            ),
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: AppColors.success.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(
                  color: AppColors.success.withValues(alpha: 0.45),
                ),
              ),
              child: Row(
                children: [
                  const Icon(
                    Icons.cloud_upload_rounded,
                    color: AppColors.success,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      _createdDocument == null
                          ? 'Estado: listo para envío'
                          : 'Estado actual: ${_createdDocument!.statusLabel}',
                      style: const TextStyle(
                        fontFamily: 'Avenir Next',
                        fontWeight: FontWeight.w700,
                        color: AppColors.success,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 10),
            CheckboxListTile(
              value: _notifyCustomer,
              contentPadding: EdgeInsets.zero,
              activeColor: AppColors.primary,
              title: const Text(
                'Notificar al cliente automáticamente',
                style: TextStyle(
                  fontFamily: 'Avenir Next',
                  color: AppColors.textPrimary,
                ),
              ),
              onChanged: (value) =>
                  setState(() => _notifyCustomer = value ?? true),
            ),
          ],
        );
      default:
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              '3. Compartir PDF',
              style: TextStyle(
                fontFamily: 'Avenir Next',
                fontWeight: FontWeight.w700,
                fontSize: 20,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              _documentStatus == null
                  ? 'No hay estado disponible.'
                  : 'Estado SRI: ${_documentStatus!.statusLabel}',
              style: const TextStyle(
                fontFamily: 'Avenir Next',
                color: AppColors.textSecondary,
              ),
            ),
            if (_documentStatus != null) ...[
              const SizedBox(height: 8),
              Text(
                _documentStatus!.authorizationNumber == null
                    ? 'Sin número de autorización todavía.'
                    : 'Autorización: ${_documentStatus!.authorizationNumber}',
                style: const TextStyle(
                  fontFamily: 'Avenir Next',
                  color: AppColors.textSecondary,
                ),
              ),
            ],
            const SizedBox(height: 16),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: const [
                _ActionPill(
                  icon: Icons.picture_as_pdf_rounded,
                  label: 'Vista PDF',
                ),
                _ActionPill(
                  icon: Icons.mail_outline_rounded,
                  label: 'Enviar correo',
                ),
                _ActionPill(icon: Icons.share_rounded, label: 'Compartir link'),
              ],
            ),
            if (_documentStatus != null &&
                _documentStatus!.sriMessages.isNotEmpty) ...[
              const SizedBox(height: 12),
              for (final message in _documentStatus!.sriMessages)
                Padding(
                  padding: const EdgeInsets.only(bottom: 6),
                  child: Text(
                    '• $message',
                    style: const TextStyle(
                      fontFamily: 'Avenir Next',
                      color: AppColors.textSecondary,
                    ),
                  ),
                ),
            ],
          ],
        );
    }
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            PageHeader(
              title: 'Flujo rápido',
              subtitle: 'Inicio > Crear > Validar SRI > Compartir',
              trailing: IconButton.filledTonal(
                tooltip: 'Volver a documentos',
                onPressed: () => context.go('/documents'),
                icon: const Icon(Icons.close_rounded),
              ),
            ),
            const SizedBox(height: 12),
            _FlowProgress(step: _step),
            const SizedBox(height: 12),
            Expanded(
              child: SingleChildScrollView(
                child: GlassPanel(
                  child: Column(
                    children: [
                      _stepContent(context),
                      if (_errorText != null) ...[
                        const SizedBox(height: 10),
                        Text(
                          _errorText!,
                          style: const TextStyle(
                            fontFamily: 'Avenir Next',
                            color: AppColors.error,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ),
            ),
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: _submitting ? null : _advanceFlow,
                icon: Icon(
                  _step == 2 ? Icons.add_rounded : Icons.arrow_forward_rounded,
                ),
                label: Text(_ctaText),
                style: ElevatedButton.styleFrom(
                  minimumSize: const Size.fromHeight(52),
                ),
              ),
            ),
            const SizedBox(height: 8),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton(
                onPressed: () => context.go('/documents'),
                child: const Text('Volver a Documentos'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _FlowProgress extends StatelessWidget {
  final int step;

  const _FlowProgress({required this.step});

  @override
  Widget build(BuildContext context) {
    const labels = ['Crear', 'Validar SRI', 'Compartir'];

    return Row(
      children: List.generate(labels.length, (index) {
        final isActive = index <= step;
        return Expanded(
          child: Padding(
            padding: EdgeInsets.only(right: index < labels.length - 1 ? 6 : 0),
            child: Column(
              children: [
                AnimatedContainer(
                  duration: const Duration(milliseconds: 220),
                  height: 6,
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(999),
                    color: isActive
                        ? AppColors.primary
                        : AppColors.border.withValues(alpha: 0.8),
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  labels[index],
                  style: TextStyle(
                    fontFamily: 'Avenir Next',
                    fontWeight: FontWeight.w700,
                    fontSize: 11,
                    color: isActive
                        ? AppColors.primaryLight
                        : AppColors.textMuted,
                  ),
                ),
              ],
            ),
          ),
        );
      }),
    );
  }
}

/// Fila de requisito en el estado "Casi listo para facturar": muestra un check
/// si ya está cumplido, o una acción para crear lo que falta.
class _RequirementRow extends StatelessWidget {
  final bool done;
  final String label;
  final String actionLabel;
  final VoidCallback? onAction;

  const _RequirementRow({
    required this.done,
    required this.label,
    required this.actionLabel,
    required this.onAction,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(
          done ? Icons.check_circle_rounded : Icons.radio_button_unchecked,
          color: done ? AppColors.success : AppColors.textMuted,
          size: 22,
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Text(
            label,
            style: TextStyle(
              fontFamily: 'Avenir Next',
              fontWeight: FontWeight.w600,
              color: done ? AppColors.textSecondary : AppColors.textPrimary,
            ),
          ),
        ),
        if (!done && onAction != null)
          TextButton(onPressed: onAction, child: Text(actionLabel)),
      ],
    );
  }
}

class _ActionPill extends StatelessWidget {
  final IconData icon;
  final String label;

  const _ActionPill({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: AppColors.surfaceDark.withValues(alpha: 0.9),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: AppColors.primaryLight),
          const SizedBox(width: 6),
          Text(
            label,
            style: const TextStyle(
              fontFamily: 'Avenir Next',
              fontWeight: FontWeight.w700,
              color: AppColors.textPrimary,
            ),
          ),
        ],
      ),
    );
  }
}
