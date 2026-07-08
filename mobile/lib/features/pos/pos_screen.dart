import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../core/api/v1_api_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/loading_widget.dart';
import '../../core/widgets/page_header.dart';
import '../../core/widgets/section_header.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/company_provider.dart';
import '../../data/providers/pos_provider.dart';

final _money = NumberFormat.currency(locale: 'es_EC', symbol: r'$');

/// Punto de venta: abrir/cerrar caja y registrar ventas rápidas desde la app.
class PosScreen extends ConsumerStatefulWidget {
  const PosScreen({super.key});

  @override
  ConsumerState<PosScreen> createState() => _PosScreenState();
}

class _PosScreenState extends ConsumerState<PosScreen> {
  List<ApiPosTransaction> _transactions = [];
  bool _loadingTx = false;
  bool _busy = false;

  V1ApiService get _api => ref.read(v1ApiServiceProvider);

  void _snack(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(message)));
  }

  String _errorMessage(Object e) =>
      e is ApiException ? e.message : 'No se pudo completar la operación.';

  Future<void> _refreshAll({int? sessionId}) async {
    ref.invalidate(posActiveSessionProvider);
    if (sessionId != null) {
      await _loadTransactions(sessionId);
    }
  }

  Future<void> _loadTransactions(int sessionId) async {
    setState(() => _loadingTx = true);
    try {
      final tx = await _api.posTransactions(sessionId);
      if (mounted) setState(() => _transactions = tx);
    } catch (_) {
      // Silencioso: la lista es secundaria al estado de la sesión.
    } finally {
      if (mounted) setState(() => _loadingTx = false);
    }
  }

  // ── Abrir caja ──

  Future<void> _openSession() async {
    if (_busy) return;
    setState(() => _busy = true);
    try {
      // Empresa activa + primer establecimiento/punto de emisión activos.
      final me = await ref.read(meProvider.future);
      final companies = await ref.read(companiesProvider.future);
      if (companies.isEmpty) {
        throw const ApiException('Primero configura tu empresa.');
      }
      final company = companies.firstWhere(
        (c) => c.id == me.currentCompanyId,
        orElse: () => companies.first,
      );
      final points =
          (await _api.companyEmissionPoints(company.id)).where((p) => p.isActive).toList();
      if (points.isEmpty) {
        throw const ApiException(
            'No hay puntos de emisión activos. Configúralos en Establecimientos.');
      }

      if (!mounted) return;
      final result = await showModalBottomSheet<({int pointId, double amount})>(
        context: context,
        isScrollControlled: true,
        backgroundColor: AppColors.surface,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        builder: (ctx) => _OpenSessionSheet(points: points),
      );
      if (result == null) return;

      final point = points.firstWhere((p) => p.id == result.pointId);
      final session = await _api.posOpenSession(
        companyId: company.id,
        branchId: point.branchId,
        emissionPointId: point.id,
        openingAmount: result.amount,
      );
      _snack('Caja abierta.');
      await _refreshAll(sessionId: session.id);
    } catch (e) {
      _snack(_errorMessage(e));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  // ── Cerrar caja ──

  Future<void> _closeSession(ApiPosSession session) async {
    if (_busy) return;
    final result = await showModalBottomSheet<({double amount, String notes})>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => _CloseSessionSheet(session: session),
    );
    if (result == null) return;

    setState(() => _busy = true);
    try {
      await _api.posCloseSession(
        session.id,
        closingAmount: result.amount,
        notes: result.notes.isEmpty ? null : result.notes,
      );
      _snack('Caja cerrada.');
      setState(() => _transactions = []);
      await _refreshAll();
    } catch (e) {
      _snack(_errorMessage(e));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  // ── Nueva venta ──

  Future<void> _newSale(ApiPosSession session) async {
    final registered = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => _SaleSheet(sessionId: session.id),
    );
    if (registered == true) {
      await _refreshAll(sessionId: session.id);
    }
  }

  Future<void> _voidTransaction(ApiPosTransaction tx, int sessionId) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('¿Anular venta ${tx.transactionNumber}?'),
        content: const Text('El monto se descontará de los totales de la caja.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: AppColors.error),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Anular'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await _api.posVoidTransaction(tx.id);
      _snack('Venta anulada.');
      await _refreshAll(sessionId: sessionId);
    } catch (e) {
      _snack(_errorMessage(e));
    }
  }

  @override
  Widget build(BuildContext context) {
    final sessionAsync = ref.watch(posActiveSessionProvider);
    final state = sessionAsync.when(
      data: (_) => AppDataState.ready,
      loading: () => AppDataState.loading,
      error: (error, _) =>
          isOfflineError(error) ? AppDataState.offline : AppDataState.error,
    );

    if (state != AppDataState.ready) {
      return ModuleStateView(
        module: 'Punto de Venta',
        state: state,
        onPrimaryAction: () => ref.invalidate(posActiveSessionProvider),
      );
    }

    final session = sessionAsync.value;
    final hasOpenSession = session != null && session.isOpen;

    // Cargar transacciones al entrar con sesión abierta.
    if (hasOpenSession && _transactions.isEmpty && !_loadingTx) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted && _transactions.isEmpty && !_loadingTx) {
          _loadTransactions(session.id);
        }
      });
    }

    return SafeArea(
      child: RefreshIndicator(
        color: AppColors.primary,
        onRefresh: () => _refreshAll(sessionId: session?.id),
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              PageHeader(
                title: 'Punto de Venta',
                subtitle: hasOpenSession ? 'Caja abierta' : 'Sin sesión activa',
                trailing: IconButton.filledTonal(
                  tooltip: 'Actualizar',
                  onPressed: () => _refreshAll(sessionId: session?.id),
                  icon: const Icon(Icons.refresh_rounded),
                ),
              ),
              const SizedBox(height: 16),
              if (hasOpenSession) ...[
                GlassPanel(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: AppColors.success.withValues(alpha: 0.2),
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: const Text(
                              'ABIERTA',
                              style: TextStyle(
                                color: AppColors.success,
                                fontSize: 12,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          const Spacer(),
                          Text(
                            'Sesión #${session.id}',
                            style: const TextStyle(
                                color: AppColors.textSecondary, fontSize: 13),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      _PosInfoRow(
                          label: 'Monto apertura',
                          value: _money.format(session.openingAmount)),
                      const SizedBox(height: 8),
                      _PosInfoRow(
                          label: 'Transacciones',
                          value: '${session.totalTransactions}'),
                      const SizedBox(height: 8),
                      _PosInfoRow(
                          label: 'Total ventas',
                          value: _money.format(session.totalSales)),
                      const Divider(height: 24),
                      Row(
                        children: [
                          _MiniStat(
                              label: 'Efectivo',
                              value: _money.format(session.totalCash)),
                          const SizedBox(width: 12),
                          _MiniStat(
                              label: 'Tarjeta',
                              value: _money.format(session.totalCard)),
                          const SizedBox(width: 12),
                          _MiniStat(
                              label: 'Transfer.',
                              value: _money.format(session.totalTransfer)),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: FilledButton.icon(
                        onPressed: _busy ? null : () => _newSale(session),
                        icon: const Icon(Icons.add_shopping_cart_rounded),
                        label: const Text('Nueva venta'),
                        style: FilledButton.styleFrom(
                          minimumSize: const Size.fromHeight(52),
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    OutlinedButton.icon(
                      onPressed: _busy ? null : () => _closeSession(session),
                      icon: const Icon(Icons.lock_outline_rounded,
                          color: AppColors.error, size: 18),
                      label: const Text('Cerrar caja',
                          style: TextStyle(color: AppColors.error)),
                      style: OutlinedButton.styleFrom(
                        minimumSize: const Size(0, 52),
                        side: BorderSide(
                            color: AppColors.error.withValues(alpha: 0.5)),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 18),
                SectionHeader(
                  title: 'Ventas de la sesión (${_transactions.length})',
                  actionText: '',
                ),
                const SizedBox(height: 10),
                if (_loadingTx)
                  const Center(
                    child: Padding(
                      padding: EdgeInsets.all(16),
                      child: CircularProgressIndicator(),
                    ),
                  )
                else if (_transactions.isEmpty)
                  const GlassPanel(
                    child: Padding(
                      padding: EdgeInsets.symmetric(vertical: 12),
                      child: Text(
                        'Aún no hay ventas en esta sesión.',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontFamily: 'Avenir Next',
                          color: AppColors.textMuted,
                        ),
                      ),
                    ),
                  )
                else
                  GlassPanel(
                    child: Column(
                      children: [
                        for (var i = 0; i < _transactions.length; i++) ...[
                          _TransactionRow(
                            tx: _transactions[i],
                            onVoid: _transactions[i].status == 'voided'
                                ? null
                                : () => _voidTransaction(
                                    _transactions[i], session.id),
                          ),
                          if (i < _transactions.length - 1)
                            const Divider(height: 18),
                        ],
                      ],
                    ),
                  ),
              ] else ...[
                GlassPanel(
                  child: Column(
                    children: [
                      const Icon(Icons.point_of_sale_rounded,
                          size: 48, color: AppColors.textSecondary),
                      const SizedBox(height: 12),
                      const Text(
                        'No hay sesión activa',
                        style: TextStyle(
                          color: AppColors.textPrimary,
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      const SizedBox(height: 6),
                      const Text(
                        'Abre la caja para comenzar a registrar ventas.',
                        style: TextStyle(
                            color: AppColors.textSecondary, fontSize: 13),
                      ),
                      const SizedBox(height: 16),
                      FilledButton.icon(
                        onPressed: _busy ? null : _openSession,
                        icon: _busy
                            ? const SizedBox(
                                width: 14,
                                height: 14,
                                child: CircularProgressIndicator(
                                    strokeWidth: 2, color: Colors.white),
                              )
                            : const Icon(Icons.play_arrow_rounded),
                        label: const Text('Abrir caja'),
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

// ═══════════════ Hoja: abrir caja ═══════════════

class _OpenSessionSheet extends StatefulWidget {
  final List<ApiEmissionPoint> points;

  const _OpenSessionSheet({required this.points});

  @override
  State<_OpenSessionSheet> createState() => _OpenSessionSheetState();
}

class _OpenSessionSheetState extends State<_OpenSessionSheet> {
  final _amountCtrl = TextEditingController(text: '0');
  late int _pointId = widget.points.first.id;

  @override
  void dispose() {
    _amountCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).viewInsets.bottom;

    return Padding(
      padding: EdgeInsets.fromLTRB(20, 18, 20, 18 + bottomInset),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Abrir caja',
            style: TextStyle(
              fontFamily: 'Avenir Next',
              fontWeight: FontWeight.w800,
              fontSize: 18,
              color: AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 14),
          if (widget.points.length > 1) ...[
            DropdownButtonFormField<int>(
              initialValue: _pointId,
              decoration:
                  const InputDecoration(labelText: 'Punto de emisión'),
              items: [
                for (final p in widget.points)
                  DropdownMenuItem(
                    value: p.id,
                    child: Text('Punto ${p.code}'),
                  ),
              ],
              onChanged: (v) => setState(() => _pointId = v ?? _pointId),
            ),
            const SizedBox(height: 12),
          ],
          TextField(
            controller: _amountCtrl,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            decoration: const InputDecoration(
              labelText: 'Monto de apertura (efectivo en caja)',
              prefixText: r'$ ',
            ),
          ),
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: FilledButton.icon(
              onPressed: () {
                final amount =
                    double.tryParse(_amountCtrl.text.replaceAll(',', '.')) ??
                        -1;
                if (amount < 0) return;
                Navigator.pop(context, (pointId: _pointId, amount: amount));
              },
              icon: const Icon(Icons.play_arrow_rounded),
              label: const Text('Abrir caja'),
            ),
          ),
        ],
      ),
    );
  }
}

// ═══════════════ Hoja: cerrar caja ═══════════════

class _CloseSessionSheet extends StatefulWidget {
  final ApiPosSession session;

  const _CloseSessionSheet({required this.session});

  @override
  State<_CloseSessionSheet> createState() => _CloseSessionSheetState();
}

class _CloseSessionSheetState extends State<_CloseSessionSheet> {
  late final _amountCtrl = TextEditingController(
    text: (widget.session.openingAmount + widget.session.totalCash)
        .toStringAsFixed(2),
  );
  final _notesCtrl = TextEditingController();

  @override
  void dispose() {
    _amountCtrl.dispose();
    _notesCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).viewInsets.bottom;
    final expected =
        widget.session.openingAmount + widget.session.totalCash;

    return Padding(
      padding: EdgeInsets.fromLTRB(20, 18, 20, 18 + bottomInset),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Cerrar caja',
            style: TextStyle(
              fontFamily: 'Avenir Next',
              fontWeight: FontWeight.w800,
              fontSize: 18,
              color: AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            'Efectivo esperado: ${_money.format(expected)} '
            '(apertura + ventas en efectivo).',
            style: const TextStyle(
              fontFamily: 'Avenir Next',
              color: AppColors.textSecondary,
              fontSize: 13,
            ),
          ),
          const SizedBox(height: 14),
          TextField(
            controller: _amountCtrl,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            decoration: const InputDecoration(
              labelText: 'Efectivo contado al cierre',
              prefixText: r'$ ',
            ),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _notesCtrl,
            maxLines: 2,
            decoration: const InputDecoration(
              labelText: 'Notas de cierre (opcional)',
            ),
          ),
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: FilledButton.icon(
              style: FilledButton.styleFrom(backgroundColor: AppColors.error),
              onPressed: () {
                final amount =
                    double.tryParse(_amountCtrl.text.replaceAll(',', '.')) ??
                        -1;
                if (amount < 0) return;
                Navigator.pop(
                    context, (amount: amount, notes: _notesCtrl.text.trim()));
              },
              icon: const Icon(Icons.lock_outline_rounded),
              label: const Text('Cerrar caja'),
            ),
          ),
        ],
      ),
    );
  }
}

// ═══════════════ Hoja: nueva venta ═══════════════

class _SaleLine {
  final ApiProduct product;
  double quantity;

  _SaleLine(this.product, this.quantity);

  double get subtotal => quantity * product.unitPrice;
  double get taxValue => subtotal * (product.taxRate / 100);
  double get total => subtotal + taxValue;
}

class _SaleSheet extends ConsumerStatefulWidget {
  final int sessionId;

  const _SaleSheet({required this.sessionId});

  @override
  ConsumerState<_SaleSheet> createState() => _SaleSheetState();
}

class _SaleSheetState extends ConsumerState<_SaleSheet> {
  final List<_SaleLine> _lines = [];
  final _receivedCtrl = TextEditingController();
  String _paymentMethod = 'cash';
  bool _saving = false;

  @override
  void dispose() {
    _receivedCtrl.dispose();
    super.dispose();
  }

  double get _total => _lines.fold(0, (s, l) => s + l.total);

  double get _received =>
      double.tryParse(_receivedCtrl.text.replaceAll(',', '.')) ?? 0;

  Future<void> _addProduct() async {
    final api = ref.read(v1ApiServiceProvider);
    final selected = await showModalBottomSheet<ApiProduct>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => _ProductSearchSheet(
        loader: (q) async => (await api.products(search: q, perPage: 30)).items,
      ),
    );
    if (selected == null) return;
    setState(() {
      final existing =
          _lines.indexWhere((l) => l.product.id == selected.id);
      if (existing >= 0) {
        _lines[existing].quantity += 1;
      } else {
        _lines.add(_SaleLine(selected, 1));
      }
    });
  }

  Future<void> _submit() async {
    if (_lines.isEmpty || _saving) return;
    if (_paymentMethod == 'cash' &&
        _received > 0 &&
        _received < _total) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
            content: Text('El monto recibido es menor al total.')),
      );
      return;
    }
    setState(() => _saving = true);
    try {
      await ref.read(v1ApiServiceProvider).posCreateTransaction(
            widget.sessionId,
            paymentMethod: _paymentMethod,
            amountReceived: _paymentMethod == 'cash'
                ? (_received > 0 ? _received : _total)
                : _total,
            items: [
              for (final l in _lines)
                {
                  'product_id': l.product.id,
                  'quantity': l.quantity,
                  'unit_price': l.product.unitPrice,
                  'tax_rate': l.product.taxRate,
                },
            ],
          );
      if (!mounted) return;
      Navigator.pop(context, true);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content:
            Text(e is ApiException ? e.message : 'No se pudo registrar.'),
      ));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).viewInsets.bottom;
    final change = _paymentMethod == 'cash' && _received > _total
        ? _received - _total
        : 0.0;

    return Padding(
      padding: EdgeInsets.fromLTRB(20, 18, 20, 18 + bottomInset),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Expanded(
                child: Text(
                  'Nueva venta',
                  style: TextStyle(
                    fontFamily: 'Avenir Next',
                    fontWeight: FontWeight.w800,
                    fontSize: 18,
                    color: AppColors.textPrimary,
                  ),
                ),
              ),
              TextButton.icon(
                onPressed: _addProduct,
                icon: const Icon(Icons.add_rounded, size: 18),
                label: const Text('Producto'),
              ),
            ],
          ),
          if (_lines.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 18),
              child: Text(
                'Agrega productos para empezar.',
                style: TextStyle(
                  fontFamily: 'Avenir Next',
                  color: AppColors.textMuted,
                ),
              ),
            )
          else
            ConstrainedBox(
              constraints: const BoxConstraints(maxHeight: 240),
              child: ListView.builder(
                shrinkWrap: true,
                itemCount: _lines.length,
                itemBuilder: (ctx, i) => Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
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
                            _money.format(_lines[i].total),
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
                      visualDensity: VisualDensity.compact,
                      onPressed: () => setState(() {
                        if (_lines[i].quantity > 1) {
                          _lines[i].quantity -= 1;
                        } else {
                          _lines.removeAt(i);
                        }
                      }),
                      icon: const Icon(Icons.remove_circle_outline_rounded),
                    ),
                    Text(
                      _lines[i].quantity.toStringAsFixed(0),
                      style: const TextStyle(
                        fontFamily: 'Avenir Next',
                        fontWeight: FontWeight.w700,
                        color: AppColors.textPrimary,
                      ),
                    ),
                    IconButton(
                      visualDensity: VisualDensity.compact,
                      onPressed: () =>
                          setState(() => _lines[i].quantity += 1),
                      icon: const Icon(Icons.add_circle_outline_rounded),
                    ),
                  ],
                ),
              ),
            ),
          const Divider(height: 20),
          Row(
            children: [
              const Expanded(
                child: Text(
                  'Total',
                  style: TextStyle(
                    fontFamily: 'Avenir Next',
                    fontWeight: FontWeight.w800,
                    fontSize: 17,
                    color: AppColors.textPrimary,
                  ),
                ),
              ),
              Text(
                _money.format(_total),
                style: const TextStyle(
                  fontFamily: 'Avenir Next',
                  fontWeight: FontWeight.w800,
                  fontSize: 20,
                  color: AppColors.primary,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          SegmentedButton<String>(
            segments: const [
              ButtonSegment(
                  value: 'cash',
                  label: Text('Efectivo'),
                  icon: Icon(Icons.payments_rounded, size: 16)),
              ButtonSegment(
                  value: 'card',
                  label: Text('Tarjeta'),
                  icon: Icon(Icons.credit_card_rounded, size: 16)),
              ButtonSegment(
                  value: 'transfer',
                  label: Text('Transf.'),
                  icon: Icon(Icons.swap_horiz_rounded, size: 16)),
            ],
            selected: {_paymentMethod},
            onSelectionChanged: (s) =>
                setState(() => _paymentMethod = s.first),
          ),
          if (_paymentMethod == 'cash') ...[
            const SizedBox(height: 12),
            TextField(
              controller: _receivedCtrl,
              keyboardType:
                  const TextInputType.numberWithOptions(decimal: true),
              onChanged: (_) => setState(() {}),
              decoration: InputDecoration(
                labelText: 'Recibido (opcional)',
                prefixText: r'$ ',
                helperText: change > 0
                    ? 'Cambio: ${_money.format(change)}'
                    : null,
              ),
            ),
          ],
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: FilledButton.icon(
              onPressed: _lines.isEmpty || _saving ? null : _submit,
              icon: _saving
                  ? const SizedBox(
                      width: 14,
                      height: 14,
                      child: CircularProgressIndicator(
                          strokeWidth: 2, color: Colors.white),
                    )
                  : const Icon(Icons.check_rounded),
              label: Text('Cobrar ${_money.format(_total)}'),
              style:
                  FilledButton.styleFrom(minimumSize: const Size.fromHeight(52)),
            ),
          ),
        ],
      ),
    );
  }
}

// ═══════════════ Hoja: buscador de productos ═══════════════

class _ProductSearchSheet extends StatefulWidget {
  final Future<List<ApiProduct>> Function(String query) loader;

  const _ProductSearchSheet({required this.loader});

  @override
  State<_ProductSearchSheet> createState() => _ProductSearchSheetState();
}

class _ProductSearchSheetState extends State<_ProductSearchSheet> {
  List<ApiProduct> _items = [];
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
          const Text(
            'Agregar producto',
            style: TextStyle(
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
              hintText: 'Buscar producto…',
            ),
            onChanged: (v) {
              _query = v;
              _load(v);
            },
          ),
          const SizedBox(height: 12),
          SizedBox(
            height: 300,
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
                            _items[i].name,
                            style: const TextStyle(
                              fontFamily: 'Avenir Next',
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                          subtitle: Text(
                            '${_money.format(_items[i].unitPrice)} · IVA ${_items[i].taxRate.toStringAsFixed(0)}%',
                            style: const TextStyle(
                                fontFamily: 'Avenir Next', fontSize: 12),
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

// ═══════════════ Widgets auxiliares ═══════════════

class _TransactionRow extends StatelessWidget {
  final ApiPosTransaction tx;
  final VoidCallback? onVoid;

  const _TransactionRow({required this.tx, this.onVoid});

  @override
  Widget build(BuildContext context) {
    final voided = tx.status == 'voided';
    final methodLabel = switch (tx.paymentMethod) {
      'cash' => 'Efectivo',
      'card' => 'Tarjeta',
      'transfer' => 'Transferencia',
      _ => 'Otro',
    };

    return Row(
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                tx.transactionNumber,
                style: TextStyle(
                  fontFamily: 'Avenir Next',
                  fontWeight: FontWeight.w700,
                  color:
                      voided ? AppColors.textMuted : AppColors.textPrimary,
                  decoration: voided ? TextDecoration.lineThrough : null,
                ),
              ),
              Text(
                '$methodLabel${voided ? ' · ANULADA' : ''}'
                '${tx.createdAt != null ? ' · ${DateFormat('HH:mm').format(tx.createdAt!)}' : ''}',
                style: const TextStyle(
                  fontFamily: 'Avenir Next',
                  fontSize: 12,
                  color: AppColors.textMuted,
                ),
              ),
            ],
          ),
        ),
        Text(
          _money.format(tx.total),
          style: TextStyle(
            fontFamily: 'Avenir Next',
            fontWeight: FontWeight.w700,
            color: voided ? AppColors.textMuted : AppColors.textPrimary,
            decoration: voided ? TextDecoration.lineThrough : null,
          ),
        ),
        if (onVoid != null)
          IconButton(
            tooltip: 'Anular',
            visualDensity: VisualDensity.compact,
            onPressed: onVoid,
            icon: const Icon(Icons.block_rounded,
                size: 18, color: AppColors.error),
          ),
      ],
    );
  }
}

class _PosInfoRow extends StatelessWidget {
  final String label;
  final String value;
  const _PosInfoRow({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label,
            style:
                const TextStyle(color: AppColors.textSecondary, fontSize: 13)),
        Text(value,
            style: const TextStyle(
                color: AppColors.textPrimary,
                fontSize: 14,
                fontWeight: FontWeight.w600)),
      ],
    );
  }
}

class _MiniStat extends StatelessWidget {
  final String label;
  final String value;
  const _MiniStat({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label,
              style: const TextStyle(
                  color: AppColors.textSecondary, fontSize: 11)),
          const SizedBox(height: 2),
          Text(value,
              style: const TextStyle(
                  color: AppColors.textPrimary,
                  fontSize: 14,
                  fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}
