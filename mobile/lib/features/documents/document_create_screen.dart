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

  /// Si viene, se edita ese borrador (se precargan sus datos y al guardar se
  /// actualiza en vez de crear). Solo para tipos con ítems (01/03/04/05).
  final int? editDocumentId;

  const NewDocumentScreen({super.key, this.initialType, this.editDocumentId});

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

  // Solo para Nota de Crédito (04) / Nota de Débito (05).
  final TextEditingController _reasonCtrl = TextEditingController();
  int? _referenceDocumentId;
  List<ApiDocument> _referenceDocs = const [];

  // Solo para Comprobante de Retención (07).
  final List<WithholdingLine> _withholdings = [];
  final TextEditingController _supportNumberCtrl = TextEditingController();
  final TextEditingController _supportTotalCtrl = TextEditingController(
    text: '0',
  );
  String _supportDocCode = '01';
  DateTime _supportDocDate = DateTime.now();
  List<ApiPurchase> _purchases = const [];

  // Solo para Guía de Remisión (06).
  final TextEditingController _carrierNameCtrl = TextEditingController();
  final TextEditingController _carrierIdCtrl = TextEditingController();
  final TextEditingController _plateCtrl = TextEditingController();
  final TextEditingController _startAddressCtrl = TextEditingController();
  final TextEditingController _destAddressCtrl = TextEditingController();
  final TextEditingController _motiveCtrl = TextEditingController();
  final TextEditingController _routeCtrl = TextEditingController();
  String _carrierIdType = '04';
  DateTime _transportStart = DateTime.now();
  DateTime _transportEnd = DateTime.now();

  bool get _needsReference => _selectedType == '04' || _selectedType == '05';
  bool get _isRetention => _selectedType == '07';
  bool get _isWaybill => _selectedType == '06';

  String get _resultTitle {
    final s = _documentStatus;
    if (s == null) return 'Resultado';
    if (s.authorizationNumber != null && s.authorizationNumber!.isNotEmpty) {
      return '¡Documento autorizado!';
    }
    if (s.sriMessages.isNotEmpty) return 'El SRI devolvió observaciones';
    return 'Estado: ${s.statusLabel}';
  }

  String get _docTypeLabel => switch (_selectedType) {
    '03' => 'Liquidación de compra',
    '04' => 'Nota de crédito',
    '05' => 'Nota de débito',
    '06' => 'Guía de remisión',
    '07' => 'Retención',
    _ => 'Nueva factura',
  };

  List<ApiCompany> _companies = const [];
  List<ApiBranch> _branches = const [];
  List<ApiEmissionPoint> _emissionPoints = const [];
  List<ApiCustomer> _customers = const [];
  List<ApiProduct> _products = const [];

  int? _companyId;
  int? _branchId;
  int? _emissionPointId;
  int? _customerId;

  ApiDocument? _createdDocument;
  ApiDocumentStatus? _documentStatus;

  bool get _isEditing => widget.editDocumentId != null;

  @override
  void initState() {
    super.initState();
    unawaited(_loadOptions());
  }

  @override
  void dispose() {
    _tipCtrl.dispose();
    _termCtrl.dispose();
    _reasonCtrl.dispose();
    _supportNumberCtrl.dispose();
    _supportTotalCtrl.dispose();
    _carrierNameCtrl.dispose();
    _carrierIdCtrl.dispose();
    _plateCtrl.dispose();
    _startAddressCtrl.dispose();
    _destAddressCtrl.dispose();
    _motiveCtrl.dispose();
    _routeCtrl.dispose();
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
      List<ApiBranch> branches = const [];
      if (companies.isNotEmpty) {
        companyId = companies.first.id;
        // Solo establecimientos activos: los desactivados no se pueden usar.
        branches = (await api.branches(companyId))
            .where((b) => b.isActive)
            .toList(growable: false);
      }
      final firstBranch = _pickBranch(branches);

      setState(() {
        _companies = companies;
        _customers = customersPage.items;
        _products = productsPage.items;
        _companyId = companyId;
        _branches = branches;
        _branchId = firstBranch?.id;
        _emissionPoints = _activePoints(firstBranch);
        _emissionPointId = _emissionPoints.isNotEmpty
            ? _emissionPoints.first.id
            : null;
        final custItems = customersPage.items;
        _customerId = custItems.isEmpty
            ? null
            : custItems
                  .firstWhere(
                    (c) => c.identificationNumber == '9999999999999',
                    orElse: () => custItems.first,
                  )
                  .id;
      });

      // Modo edición: precargar el borrador.
      if (_isEditing) {
        await _prefillFromDraft(api, widget.editDocumentId!);
      }
    } catch (error) {
      setState(() => _errorText = error.toString());
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  /// Carga un borrador y precarga el formulario: tipo, cliente, ítems, formas
  /// de pago, propina e información adicional.
  Future<void> _prefillFromDraft(V1ApiService api, int id) async {
    final doc = await api.getDocument(id);

    final lines = <InvoiceLine>[];
    for (final item in doc.items) {
      // Reconstruimos un producto (real si existe en el catálogo, o sintético
      // a partir del ítem) para conservar códigos de impuesto al reenviar.
      final product = _products.firstWhere(
        (p) => item.productId != null && p.id == item.productId,
        orElse: () => ApiProduct(
          id: item.productId ?? 0,
          code: item.mainCode ?? '-',
          name: item.description,
          type: 'product',
          typeLabel: 'Producto',
          unitPrice: item.unitPrice,
          taxRate: item.taxRate,
          taxCode: item.taxCode,
          taxPercentageCode: item.taxPercentageCode,
          trackInventory: false,
          stock: null,
          isActive: true,
        ),
      );
      lines.add(InvoiceLine(
        product: product,
        quantity: item.quantity,
        unitPrice: item.unitPrice,
        discount: item.discount,
      ));
    }

    if (!mounted) return;
    setState(() {
      _selectedType = doc.documentType.isNotEmpty ? doc.documentType : _selectedType;
      if (doc.customerId != null &&
          _customers.any((c) => c.id == doc.customerId)) {
        _customerId = doc.customerId;
      }
      _lines
        ..clear()
        ..addAll(lines);
      _payments
        ..clear()
        ..addAll(doc.paymentMethods
            .map((p) => InvoicePayment(code: p.code, amount: p.amount)));
      _additional
        ..clear()
        ..addAll(doc.additionalInfoPairs
            .map((e) => (name: e.name, value: e.value)));
      _tipCtrl.text = doc.tip.toStringAsFixed(2);
    });
  }

  /// Establecimiento por defecto: la matriz si existe, si no el primero.
  ApiBranch? _pickBranch(List<ApiBranch> branches) {
    if (branches.isEmpty) return null;
    for (final b in branches) {
      if (b.isMain) return b;
    }
    return branches.first;
  }

  /// Puntos de emisión ACTIVOS de un establecimiento (los inactivos no se
  /// pueden usar para emitir).
  List<ApiEmissionPoint> _activePoints(ApiBranch? branch) {
    if (branch == null) return const [];
    return branch.emissionPoints
        .where((e) => e.isActive)
        .toList(growable: false);
  }

  /// Al elegir un establecimiento, sus puntos de emisión activos pasan a estar
  /// disponibles y se preselecciona el primero.
  void _selectBranch(int branchId) {
    final branch = _branches.firstWhere(
      (b) => b.id == branchId,
      orElse: () => _branches.first,
    );
    final points = _activePoints(branch);
    setState(() {
      _branchId = branchId;
      _emissionPoints = points;
      _emissionPointId = points.isNotEmpty ? points.first.id : null;
    });
  }

  Future<void> _loadEmissionPoints(int companyId) async {
    setState(() {
      _loading = true;
      _errorText = null;
    });
    try {
      final branches = (await ref.read(v1ApiServiceProvider).branches(companyId))
          .where((b) => b.isActive)
          .toList(growable: false);
      final firstBranch = _pickBranch(branches);
      setState(() {
        _branches = branches;
        _branchId = firstBranch?.id;
        _emissionPoints = _activePoints(firstBranch);
        _emissionPointId =
            _emissionPoints.isNotEmpty ? _emissionPoints.first.id : null;
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
      _withholdings.clear();
      _supportNumberCtrl.clear();
      _supportTotalCtrl.text = '0';
      _carrierNameCtrl.clear();
      _carrierIdCtrl.clear();
      _plateCtrl.clear();
      _startAddressCtrl.clear();
      _destAddressCtrl.clear();
      _motiveCtrl.clear();
      _routeCtrl.clear();
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
      final api = ref.read(v1ApiServiceProvider);
      final input = CreateInvoiceInput(
        companyId: _companyId!,
        customerId: _customerId!,
        emissionPointId: _emissionPointId!,
        documentType: _selectedType,
        lines: _lines,
        payments: _payments,
        paymentTerm: term,
        tip: _tip < 0 ? 0 : _tip,
        additionalInfo: _additional,
      );
      final document = _isEditing
          ? await api.updateInvoice(widget.editDocumentId!, input)
          : await api.createInvoice(input);

      _invalidateData();
      if (_isEditing) {
        ref.invalidate(documentDetailProvider(widget.editDocumentId!));
      }
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

  /// Valida y crea el documento (borrador) en el backend. Devuelve el documento
  /// o null si falta algo o hubo error. Base de "Guardar" y "Enviar".
  Future<ApiDocument?> _submitInvoice() async {
    if (_isRetention) return _submitRetention();
    if (_isWaybill) return _submitWaybill();
    if (_companyId == null ||
        _customerId == null ||
        _emissionPointId == null) {
      setState(() => _errorText = 'Elegí cliente, empresa y punto de emisión.');
      return null;
    }
    if (_lines.isEmpty) {
      setState(() => _errorText = 'Agregá al menos un producto.');
      return null;
    }
    if (_needsReference &&
        (_referenceDocumentId == null || _reasonCtrl.text.trim().isEmpty)) {
      setState(
        () => _errorText =
            'Elegí la factura a modificar y escribí el motivo.',
      );
      return null;
    }

    setState(() {
      _submitting = true;
      _errorText = null;
    });
    try {
      final term = int.tryParse(_termCtrl.text.trim()) ?? 0;
      final api = ref.read(v1ApiServiceProvider);
      final input = CreateInvoiceInput(
        companyId: _companyId!,
        customerId: _customerId!,
        emissionPointId: _emissionPointId!,
        documentType: _selectedType,
        lines: _lines,
        payments: _payments,
        paymentTerm: term,
        tip: _tip < 0 ? 0 : _tip,
        additionalInfo: _additional,
        referenceDocumentId: _needsReference ? _referenceDocumentId : null,
        modificationReason: _needsReference ? _reasonCtrl.text.trim() : null,
      );
      final doc = _isEditing
          ? await api.updateInvoice(widget.editDocumentId!, input)
          : await api.createInvoice(input);
      _invalidateData();
      if (_isEditing) {
        ref.invalidate(documentDetailProvider(widget.editDocumentId!));
      }
      return doc;
    } catch (error) {
      setState(() => _errorText = error.toString());
      return null;
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  /// Valida y crea un Comprobante de Retención (07).
  Future<ApiDocument?> _submitRetention() async {
    if (_companyId == null ||
        _customerId == null ||
        _emissionPointId == null) {
      setState(() => _errorText = 'Elegí cliente, empresa y punto de emisión.');
      return null;
    }
    if (_supportNumberCtrl.text.trim().isEmpty) {
      setState(
        () => _errorText = 'Ingresá el número del documento sustento.',
      );
      return null;
    }
    if (_withholdings.isEmpty) {
      setState(() => _errorText = 'Agregá al menos una retención.');
      return null;
    }

    setState(() {
      _submitting = true;
      _errorText = null;
    });
    try {
      final doc = await ref
          .read(v1ApiServiceProvider)
          .createRetention(
            CreateRetentionInput(
              companyId: _companyId!,
              customerId: _customerId!,
              emissionPointId: _emissionPointId!,
              supportDocCode: _supportDocCode,
              supportDocNumber: _supportNumberCtrl.text.trim(),
              supportDocDate: _supportDocDate,
              supportDocTotal:
                  double.tryParse(
                    _supportTotalCtrl.text.replaceAll(',', '.'),
                  ) ??
                  0,
              withholdings: _withholdings,
              additionalInfo: _additional,
            ),
          );
      _invalidateData();
      return doc;
    } catch (error) {
      setState(() => _errorText = error.toString());
      return null;
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  /// Valida y crea una Guía de Remisión (06).
  Future<ApiDocument?> _submitWaybill() async {
    if (_companyId == null ||
        _customerId == null ||
        _emissionPointId == null) {
      setState(() => _errorText = 'Elegí cliente, empresa y punto de emisión.');
      return null;
    }
    final customer = _selectedCustomer;
    if (customer == null) {
      setState(() => _errorText = 'Elegí el destinatario (cliente).');
      return null;
    }
    if (_lines.isEmpty) {
      setState(() => _errorText = 'Agregá al menos un bien a trasladar.');
      return null;
    }
    if (_carrierNameCtrl.text.trim().isEmpty ||
        _carrierIdCtrl.text.trim().isEmpty ||
        _plateCtrl.text.trim().isEmpty) {
      setState(
        () => _errorText =
            'Completá transportista (nombre, identificación) y placa.',
      );
      return null;
    }
    if (_startAddressCtrl.text.trim().isEmpty ||
        _destAddressCtrl.text.trim().isEmpty ||
        _motiveCtrl.text.trim().isEmpty) {
      setState(
        () => _errorText =
            'Completá partida, destino y motivo del traslado.',
      );
      return null;
    }

    setState(() {
      _submitting = true;
      _errorText = null;
    });
    try {
      final doc = await ref
          .read(v1ApiServiceProvider)
          .createWaybill(
            CreateWaybillInput(
              companyId: _companyId!,
              customerId: _customerId!,
              emissionPointId: _emissionPointId!,
              lines: _lines,
              startAddress: _startAddressCtrl.text.trim(),
              carrierName: _carrierNameCtrl.text.trim(),
              carrierId: _carrierIdCtrl.text.trim(),
              carrierIdType: _carrierIdType,
              plate: _plateCtrl.text.trim(),
              startDate: _transportStart,
              endDate: _transportEnd,
              recipient: WaybillRecipient(
                identification: customer.identificationNumber,
                name: customer.name,
                address: _destAddressCtrl.text.trim(),
                reason: _motiveCtrl.text.trim(),
                route: _routeCtrl.text.trim().isEmpty
                    ? null
                    : _routeCtrl.text.trim(),
              ),
            ),
          );
      _invalidateData();
      return doc;
    } catch (error) {
      setState(() => _errorText = error.toString());
      return null;
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  /// Guarda como borrador (no envía al SRI) y vuelve a la lista.
  Future<void> _saveDraft() async {
    final doc = await _submitInvoice();
    if (doc == null || !mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          _isEditing
              ? 'Cambios guardados en el borrador.'
              : 'Factura guardada como borrador.',
        ),
      ),
    );
    context.go('/documents');
  }

  /// Crea y envía al SRI en un solo paso; muestra el resultado (paso 3).
  Future<void> _createAndSend() async {
    final doc = await _submitInvoice();
    if (doc == null || !mounted) return;

    setState(() {
      _createdDocument = doc;
      _submitting = true;
      _errorText = null;
    });
    try {
      final sent = await ref.read(v1ApiServiceProvider).sendDocument(doc.id);
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
      // El borrador ya quedó guardado; informamos que falló el envío.
      setState(() {
        _errorText =
            'Se guardó el borrador, pero falló el envío al SRI: $error';
        _step = 2;
      });
    } finally {
      if (mounted) setState(() => _submitting = false);
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
                '${_qtyLabel(_lines[i].quantity)} × ${currency(_lines[i].unitPrice)}'
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

  ApiCustomer? get _selectedCustomer {
    if (_customerId == null) return null;
    for (final c in _customers) {
      if (c.id == _customerId) return c;
    }
    return null;
  }

  Widget _customerCard() {
    final c = _selectedCustomer;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: _openCustomerSearch,
        borderRadius: BorderRadius.circular(14),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          decoration: BoxDecoration(
            color: AppColors.surfaceDark,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: AppColors.border),
          ),
          child: Row(
            children: [
              const Icon(
                Icons.person_outline_rounded,
                color: AppColors.textMuted,
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      c?.name ?? 'Seleccioná un cliente',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontFamily: 'Avenir Next',
                        fontWeight: FontWeight.w700,
                        color: AppColors.textPrimary,
                      ),
                    ),
                    if (c != null) ...[
                      const SizedBox(height: 2),
                      Text(
                        c.identificationNumber,
                        style: const TextStyle(
                          fontFamily: 'Avenir Next',
                          color: AppColors.textSecondary,
                          fontSize: 13,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              const Icon(Icons.search_rounded, color: AppColors.primary),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _openCustomerSearch() async {
    final controller = TextEditingController();
    final selected = await showModalBottomSheet<int>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => Padding(
        padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom),
        child: SizedBox(
          height:
              (MediaQuery.of(ctx).size.height * 0.85 -
                      MediaQuery.of(ctx).viewInsets.bottom)
                  .clamp(240.0, MediaQuery.of(ctx).size.height * 0.85),
          child: StatefulBuilder(
            builder: (ctx, setSheet) {
              final query = controller.text.trim().toLowerCase();
              final filtered = query.isEmpty
                  ? _customers
                  : _customers
                        .where(
                          (c) =>
                              c.name.toLowerCase().contains(query) ||
                              c.identificationNumber.toLowerCase().contains(
                                query,
                              ),
                        )
                        .toList();
              return Column(
                children: [
                  const SizedBox(height: 12),
                  _sheetHandle(),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
                    child: TextField(
                      controller: controller,
                      autofocus: true,
                      onChanged: (_) => setSheet(() {}),
                      decoration: const InputDecoration(
                        hintText: 'Buscar por nombre o identificación',
                        prefixIcon: Icon(Icons.search_rounded),
                      ),
                    ),
                  ),
                  Expanded(
                    child: filtered.isEmpty
                        ? const Center(
                            child: Text(
                              'Sin resultados',
                              style: TextStyle(
                                fontFamily: 'Avenir Next',
                                color: AppColors.textMuted,
                              ),
                            ),
                          )
                        : ListView.builder(
                            itemCount: filtered.length,
                            itemBuilder: (ctx, i) {
                              final c = filtered[i];
                              return ListTile(
                                leading: const Icon(
                                  Icons.person_outline_rounded,
                                  color: AppColors.textMuted,
                                ),
                                title: Text(
                                  c.name,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    fontFamily: 'Avenir Next',
                                    fontWeight: FontWeight.w600,
                                    color: AppColors.textPrimary,
                                  ),
                                ),
                                subtitle: Text(
                                  c.identificationNumber,
                                  style: const TextStyle(
                                    fontFamily: 'Avenir Next',
                                    color: AppColors.textSecondary,
                                  ),
                                ),
                                onTap: () => Navigator.pop(ctx, c.id),
                              );
                            },
                          ),
                  ),
                ],
              );
            },
          ),
        ),
      ),
    );
    controller.dispose();
    if (selected != null) setState(() => _customerId = selected);
  }

  ApiDocument? get _selectedReferenceDoc {
    if (_referenceDocumentId == null) return null;
    for (final d in _referenceDocs) {
      if (d.id == _referenceDocumentId) return d;
    }
    return null;
  }

  Widget _referenceCard() {
    final d = _selectedReferenceDoc;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: _openReferenceSearch,
        borderRadius: BorderRadius.circular(14),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          decoration: BoxDecoration(
            color: AppColors.surfaceDark,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: AppColors.border),
          ),
          child: Row(
            children: [
              const Icon(
                Icons.description_outlined,
                color: AppColors.textMuted,
              ),
              const SizedBox(width: 12),
              Expanded(
                child: d == null
                    ? const Text(
                        'Elegí la factura a modificar',
                        style: TextStyle(
                          fontFamily: 'Avenir Next',
                          color: AppColors.textMuted,
                        ),
                      )
                    : Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            d.documentNumber,
                            style: const TextStyle(
                              fontFamily: 'Avenir Next',
                              fontWeight: FontWeight.w700,
                              color: AppColors.textPrimary,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            '${d.issuer} · ${currency(d.total)}',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontFamily: 'Avenir Next',
                              color: AppColors.textSecondary,
                              fontSize: 13,
                            ),
                          ),
                        ],
                      ),
              ),
              const Icon(Icons.search_rounded, color: AppColors.primary),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _openReferenceSearch() async {
    // Carga las facturas la primera vez.
    if (_referenceDocs.isEmpty) {
      setState(() {
        _submitting = true;
        _errorText = null;
      });
      try {
        final page = await ref
            .read(v1ApiServiceProvider)
            .documents(documentType: '01', perPage: 50);
        _referenceDocs = page.items;
      } catch (_) {
        // El sheet mostrará "sin resultados".
      } finally {
        if (mounted) setState(() => _submitting = false);
      }
    }
    if (!mounted) return;

    final controller = TextEditingController();
    final selected = await showModalBottomSheet<int>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => Padding(
        padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom),
        child: SizedBox(
          height:
              (MediaQuery.of(ctx).size.height * 0.85 -
                      MediaQuery.of(ctx).viewInsets.bottom)
                  .clamp(240.0, MediaQuery.of(ctx).size.height * 0.85),
          child: StatefulBuilder(
            builder: (ctx, setSheet) {
              final query = controller.text.trim().toLowerCase();
              final filtered = query.isEmpty
                  ? _referenceDocs
                  : _referenceDocs
                        .where(
                          (d) =>
                              d.documentNumber.toLowerCase().contains(query) ||
                              d.issuer.toLowerCase().contains(query),
                        )
                        .toList();
              return Column(
                children: [
                  const SizedBox(height: 12),
                  _sheetHandle(),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
                    child: TextField(
                      controller: controller,
                      autofocus: true,
                      onChanged: (_) => setSheet(() {}),
                      decoration: const InputDecoration(
                        hintText: 'Buscar factura por número o cliente',
                        prefixIcon: Icon(Icons.search_rounded),
                      ),
                    ),
                  ),
                  Expanded(
                    child: filtered.isEmpty
                        ? const Center(
                            child: Text(
                              'Sin facturas para referenciar',
                              style: TextStyle(
                                fontFamily: 'Avenir Next',
                                color: AppColors.textMuted,
                              ),
                            ),
                          )
                        : ListView.builder(
                            itemCount: filtered.length,
                            itemBuilder: (ctx, i) {
                              final d = filtered[i];
                              return ListTile(
                                leading: const Icon(
                                  Icons.description_outlined,
                                  color: AppColors.textMuted,
                                ),
                                title: Text(
                                  d.documentNumber,
                                  style: const TextStyle(
                                    fontFamily: 'Avenir Next',
                                    fontWeight: FontWeight.w600,
                                    color: AppColors.textPrimary,
                                  ),
                                ),
                                subtitle: Text(
                                  '${d.issuer} · ${currency(d.total)}',
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    fontFamily: 'Avenir Next',
                                    color: AppColors.textSecondary,
                                  ),
                                ),
                                onTap: () => Navigator.pop(ctx, d.id),
                              );
                            },
                          ),
                  ),
                ],
              );
            },
          ),
        ),
      ),
    );
    controller.dispose();
    if (selected != null) setState(() => _referenceDocumentId = selected);
  }

  // ─────────────────────────── Retención (07) ──────────────────────────────

  double get _totalRetained =>
      _withholdings.fold(0.0, (s, w) => s + w.retained);

  String _fmtDate(DateTime d) =>
      '${d.day.toString().padLeft(2, '0')}/${d.month.toString().padLeft(2, '0')}/${d.year}';

  Future<void> _pickSupportDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _supportDocDate,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
    );
    if (picked != null) setState(() => _supportDocDate = picked);
  }

  String _supportCodeFor(String docType) =>
      kSupportDocTypes.any((t) => t.code == docType) ? docType : '01';

  Future<void> _openSupportDocSearch() async {
    if (_purchases.isEmpty) {
      setState(() {
        _submitting = true;
        _errorText = null;
      });
      try {
        final page = await ref.read(v1ApiServiceProvider).purchases(perPage: 50);
        _purchases = page.items;
      } catch (_) {
        // El sheet mostrará "sin resultados".
      } finally {
        if (mounted) setState(() => _submitting = false);
      }
    }
    if (!mounted) return;

    final controller = TextEditingController();
    final selected = await showModalBottomSheet<ApiPurchase>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => Padding(
        padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom),
        child: SizedBox(
          height:
              (MediaQuery.of(ctx).size.height * 0.85 -
                      MediaQuery.of(ctx).viewInsets.bottom)
                  .clamp(240.0, MediaQuery.of(ctx).size.height * 0.85),
          child: StatefulBuilder(
            builder: (ctx, setSheet) {
              final query = controller.text.trim().toLowerCase();
              final filtered = query.isEmpty
                  ? _purchases
                  : _purchases
                        .where(
                          (p) =>
                              p.supplierDocumentNumber.toLowerCase().contains(
                                query,
                              ) ||
                              (p.supplierName ?? '').toLowerCase().contains(
                                query,
                              ),
                        )
                        .toList();
              return Column(
                children: [
                  const SizedBox(height: 12),
                  _sheetHandle(),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
                    child: TextField(
                      controller: controller,
                      autofocus: true,
                      onChanged: (_) => setSheet(() {}),
                      decoration: const InputDecoration(
                        hintText: 'Buscar compra por número o proveedor',
                        prefixIcon: Icon(Icons.search_rounded),
                      ),
                    ),
                  ),
                  Expanded(
                    child: filtered.isEmpty
                        ? const Center(
                            child: Text(
                              'No hay compras registradas',
                              style: TextStyle(
                                fontFamily: 'Avenir Next',
                                color: AppColors.textMuted,
                              ),
                            ),
                          )
                        : ListView.builder(
                            itemCount: filtered.length,
                            itemBuilder: (ctx, i) {
                              final p = filtered[i];
                              return ListTile(
                                leading: const Icon(
                                  Icons.receipt_outlined,
                                  color: AppColors.textMuted,
                                ),
                                title: Text(
                                  p.supplierDocumentNumber,
                                  style: const TextStyle(
                                    fontFamily: 'Avenir Next',
                                    fontWeight: FontWeight.w600,
                                    color: AppColors.textPrimary,
                                  ),
                                ),
                                subtitle: Text(
                                  '${p.supplierName ?? 'Proveedor'} · ${currency(p.total)}',
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    fontFamily: 'Avenir Next',
                                    color: AppColors.textSecondary,
                                  ),
                                ),
                                onTap: () => Navigator.pop(ctx, p),
                              );
                            },
                          ),
                  ),
                ],
              );
            },
          ),
        ),
      ),
    );
    controller.dispose();
    if (selected != null) {
      setState(() {
        _supportDocCode = _supportCodeFor(selected.documentType);
        _supportNumberCtrl.text = selected.supplierDocumentNumber;
        if (selected.issueDate != null) _supportDocDate = selected.issueDate!;
        _supportTotalCtrl.text = selected.total.toStringAsFixed(2);
      });
    }
  }

  Widget _supportDocSection() {
    return Column(
      children: [
        Align(
          alignment: Alignment.centerLeft,
          child: OutlinedButton.icon(
            onPressed: _submitting ? null : _openSupportDocSearch,
            icon: const Icon(Icons.search_rounded, size: 18),
            label: const Text('Buscar compra registrada'),
          ),
        ),
        const SizedBox(height: 8),
        DropdownButtonFormField<String>(
          initialValue: _supportDocCode,
          isExpanded: true,
          decoration: const InputDecoration(labelText: 'Tipo de documento'),
          items: kSupportDocTypes
              .map((t) => DropdownMenuItem(value: t.code, child: Text(t.label)))
              .toList(),
          onChanged: (v) => setState(() => _supportDocCode = v ?? '01'),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _supportNumberCtrl,
          keyboardType: TextInputType.number,
          decoration: const InputDecoration(
            labelText: 'Número (001-001-000000001)',
          ),
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: InkWell(
                onTap: _pickSupportDate,
                borderRadius: BorderRadius.circular(14),
                child: InputDecorator(
                  decoration: const InputDecoration(labelText: 'Fecha'),
                  child: Text(
                    _fmtDate(_supportDocDate),
                    style: const TextStyle(
                      fontFamily: 'Avenir Next',
                      color: AppColors.textPrimary,
                    ),
                  ),
                ),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: TextField(
                controller: _supportTotalCtrl,
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: true,
                ),
                decoration: const InputDecoration(
                  labelText: 'Total sustento',
                  prefixText: '\$ ',
                ),
              ),
            ),
          ],
        ),
      ],
    );
  }

  List<Widget> _buildWithholdings() {
    if (_withholdings.isEmpty) {
      return const [
        Text(
          'Agregá al menos una retención.',
          style: TextStyle(fontFamily: 'Avenir Next', color: AppColors.textMuted),
        ),
        SizedBox(height: 8),
      ];
    }
    return [
      for (var i = 0; i < _withholdings.length; i++)
        _itemCard(
          onRemove: () => setState(() => _withholdings.removeAt(i)),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                '${_withholdings[i].taxType == 'iva' ? 'IVA' : 'Renta'} · Cód. ${_withholdings[i].code}',
                style: const TextStyle(
                  fontFamily: 'Avenir Next',
                  fontWeight: FontWeight.w700,
                  color: AppColors.textPrimary,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                'Base ${currency(_withholdings[i].base)} × ${_withholdings[i].rate}%  =  ${currency(_withholdings[i].retained)}',
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

  Widget _retentionTotalPanel() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: AppColors.surfaceDark,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border),
      ),
      child: _totalsRow(
        'Total retenido',
        currency(_totalRetained),
        strong: true,
      ),
    );
  }

  // ───────────────────────── Guía de Remisión (06) ─────────────────────────

  Widget _dateField(String label, DateTime value, VoidCallback onTap) => InkWell(
    onTap: onTap,
    borderRadius: BorderRadius.circular(14),
    child: InputDecorator(
      decoration: InputDecoration(labelText: label),
      child: Text(
        _fmtDate(value),
        style: const TextStyle(
          fontFamily: 'Avenir Next',
          color: AppColors.textPrimary,
        ),
      ),
    ),
  );

  Future<void> _pickTransportDate(bool isStart) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: isStart ? _transportStart : _transportEnd,
      firstDate: DateTime(2020),
      lastDate: DateTime(2030),
    );
    if (picked == null) return;
    setState(() {
      if (isStart) {
        _transportStart = picked;
      } else {
        _transportEnd = picked;
      }
    });
  }

  Widget _transportSection() {
    return Column(
      children: [
        TextField(
          controller: _carrierNameCtrl,
          textCapitalization: TextCapitalization.words,
          decoration: const InputDecoration(
            labelText: 'Razón social del transportista',
          ),
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              flex: 2,
              child: DropdownButtonFormField<String>(
                initialValue: _carrierIdType,
                isExpanded: true,
                decoration: const InputDecoration(labelText: 'Tipo ID'),
                items: const [
                  DropdownMenuItem(value: '04', child: Text('RUC')),
                  DropdownMenuItem(value: '05', child: Text('Cédula')),
                  DropdownMenuItem(value: '06', child: Text('Pasaporte')),
                ],
                onChanged: (v) => setState(() => _carrierIdType = v ?? '04'),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              flex: 3,
              child: TextField(
                controller: _carrierIdCtrl,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(
                  labelText: 'Identificación',
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _plateCtrl,
          textCapitalization: TextCapitalization.characters,
          decoration: const InputDecoration(labelText: 'Placa'),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _startAddressCtrl,
          decoration: const InputDecoration(labelText: 'Dirección de partida'),
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: _dateField(
                'Inicio transporte',
                _transportStart,
                () => _pickTransportDate(true),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _dateField(
                'Fin transporte',
                _transportEnd,
                () => _pickTransportDate(false),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _destinationSection() {
    return Column(
      children: [
        TextField(
          controller: _destAddressCtrl,
          decoration: const InputDecoration(labelText: 'Dirección de destino'),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _motiveCtrl,
          decoration: const InputDecoration(labelText: 'Motivo del traslado'),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _routeCtrl,
          decoration: const InputDecoration(labelText: 'Ruta (opcional)'),
        ),
      ],
    );
  }

  Future<void> _openAddWithholdingSheet() async {
    var taxType = 'renta';
    var code = kRentaRetentionCodes.first.code;
    final baseCtrl = TextEditingController(text: '0');
    final rateCtrl = TextEditingController(
      text: kRentaRetentionCodes.first.percentage.toString(),
    );

    final result = await showModalBottomSheet<WithholdingLine>(
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
            final catalog = taxType == 'iva'
                ? kIvaRetentionCodes
                : kRentaRetentionCodes;
            final base =
                double.tryParse(baseCtrl.text.replaceAll(',', '.')) ?? 0;
            final r = double.tryParse(rateCtrl.text.replaceAll(',', '.')) ?? 0;
            final retained = base * r / 100;
            return SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _sheetHandle(),
                  const SizedBox(height: 10),
                  _sectionTitle('Agregar retención'),
                  const SizedBox(height: 14),
                  DropdownButtonFormField<String>(
                    initialValue: taxType,
                    decoration: const InputDecoration(
                      labelText: 'Tipo de impuesto',
                    ),
                    items: const [
                      DropdownMenuItem(value: 'renta', child: Text('Renta')),
                      DropdownMenuItem(value: 'iva', child: Text('IVA')),
                    ],
                    onChanged: (v) => setSheet(() {
                      taxType = v ?? 'renta';
                      final cat = taxType == 'iva'
                          ? kIvaRetentionCodes
                          : kRentaRetentionCodes;
                      code = cat.first.code;
                      rateCtrl.text = cat.first.percentage.toString();
                    }),
                  ),
                  const SizedBox(height: 10),
                  DropdownButtonFormField<String>(
                    initialValue: code,
                    isExpanded: true,
                    decoration: const InputDecoration(
                      labelText: 'Código de retención',
                    ),
                    items: catalog
                        .map(
                          (c) => DropdownMenuItem(
                            value: c.code,
                            child: Text(
                              '${c.code} · ${c.name} (${c.percentage}%)',
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        )
                        .toList(),
                    onChanged: (v) => setSheet(() {
                      code = v ?? code;
                      final sel = catalog.firstWhere((c) => c.code == code);
                      rateCtrl.text = sel.percentage.toString();
                    }),
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: baseCtrl,
                          keyboardType: const TextInputType.numberWithOptions(
                            decimal: true,
                          ),
                          onChanged: (_) => setSheet(() {}),
                          decoration: const InputDecoration(
                            labelText: 'Base imponible',
                            prefixText: '\$ ',
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: TextField(
                          controller: rateCtrl,
                          keyboardType: const TextInputType.numberWithOptions(
                            decimal: true,
                          ),
                          onChanged: (_) => setSheet(() {}),
                          decoration: const InputDecoration(
                            labelText: '% Retención',
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Align(
                    alignment: Alignment.centerRight,
                    child: Text(
                      'Valor retenido: ${currency(retained)}',
                      style: const TextStyle(
                        fontFamily: 'Avenir Next',
                        fontWeight: FontWeight.w700,
                        color: AppColors.textPrimary,
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  ElevatedButton(
                    onPressed: () => Navigator.pop(
                      ctx,
                      WithholdingLine(
                        taxType: taxType,
                        code: code,
                        base: base,
                        rate: r,
                      ),
                    ),
                    style: ElevatedButton.styleFrom(
                      minimumSize: const Size.fromHeight(50),
                    ),
                    child: const Text('Agregar retención'),
                  ),
                ],
              ),
            );
          },
        ),
      ),
    );

    baseCtrl.dispose();
    rateCtrl.dispose();
    if (result != null) setState(() => _withholdings.add(result));
  }

  Future<void> _openAddProductSheet() async {
    if (_products.isEmpty) return;
    var selected = _products.first;
    final qtyCtrl = TextEditingController(text: '1');
    final discCtrl = TextEditingController(text: '0');
    final priceCtrl = TextEditingController(
      text: selected.unitPrice.toStringAsFixed(2),
    );

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
            final price =
                double.tryParse(priceCtrl.text.replaceAll(',', '.')) ??
                selected.unitPrice;
            final line = InvoiceLine(
              product: selected,
              quantity: q,
              discount: d,
              unitPrice: price,
            );
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
                  // Al cambiar de producto, se precarga su precio (editable).
                  onChanged: (v) => setSheet(() {
                    selected = _products.firstWhere((p) => p.id == v);
                    priceCtrl.text = selected.unitPrice.toStringAsFixed(2);
                  }),
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: priceCtrl,
                  keyboardType: const TextInputType.numberWithOptions(
                    decimal: true,
                  ),
                  onChanged: (_) => setSheet(() {}),
                  decoration: const InputDecoration(
                    labelText: 'Precio unitario',
                    prefixText: '\$ ',
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
    priceCtrl.dispose();
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
    if (_companies.isEmpty ||
        _customers.isEmpty ||
        (!_isRetention && _products.isEmpty)) {
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
          if (!_isRetention) ...[
            const SizedBox(height: 10),
            _RequirementRow(
              done: _products.isNotEmpty,
              label: 'Al menos un producto',
              actionLabel: 'Crear producto',
              onAction: _products.isEmpty
                  ? () => context.push('/products/new')
                  : null,
            ),
          ],
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
                DropdownMenuItem(
                  value: '03',
                  child: Text('Liquidación de compra'),
                ),
                DropdownMenuItem(value: '07', child: Text('Retención')),
                DropdownMenuItem(
                  value: '06',
                  child: Text('Guía de remisión'),
                ),
              ],
              onChanged: (value) => setState(() {
                _selectedType = value ?? '01';
              }),
            ),
            const SizedBox(height: 10),
            // Nota de Crédito / Débito: documento de referencia + motivo.
            if (_needsReference) ...[
              _sectionTitle('Documento que modifica'),
              const SizedBox(height: 8),
              _referenceCard(),
              const SizedBox(height: 10),
              TextField(
                controller: _reasonCtrl,
                decoration: const InputDecoration(
                  labelText: 'Motivo de la modificación',
                ),
              ),
              const SizedBox(height: 10),
            ],
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
            // Establecimiento y punto de emisión: siempre visibles, lado a
            // lado. Con una sola opción quedan preseleccionados.
            if (_branchId != null || _emissionPointId != null) ...[
              _sectionTitle('Establecimiento y punto de emisión'),
              const SizedBox(height: 8),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: DropdownButtonFormField<int>(
                      isExpanded: true,
                      initialValue: _branchId,
                      decoration: const InputDecoration(
                        labelText: 'Establecimiento',
                      ),
                      items: _branches
                          .map(
                            (b) => DropdownMenuItem(
                              value: b.id,
                              child: Text(
                                '${b.code} · ${b.name}',
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: (value) {
                        if (value != null) _selectBranch(value);
                      },
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: DropdownButtonFormField<int>(
                      isExpanded: true,
                      initialValue: _emissionPointId,
                      decoration: const InputDecoration(
                        labelText: 'Punto de emisión',
                      ),
                      items: _emissionPoints
                          .map(
                            (point) => DropdownMenuItem(
                              value: point.id,
                              child: Text(
                                '${point.code} · ${point.description}',
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: (value) =>
                          setState(() => _emissionPointId = value),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 18),
            ],
            _sectionTitle('Cliente'),
            const SizedBox(height: 8),
            _customerCard(),
            const SizedBox(height: 18),
            if (_isRetention) ...[
              _sectionTitle('Documento sustento'),
              const SizedBox(height: 8),
              _supportDocSection(),
              const SizedBox(height: 18),
              _sectionTitle('Retenciones'),
              const SizedBox(height: 8),
              ..._buildWithholdings(),
              _addTile('Agregar retención', _openAddWithholdingSheet),
              const SizedBox(height: 18),
              _retentionTotalPanel(),
            ] else if (_isWaybill) ...[
              _sectionTitle('Bienes a trasladar'),
              const SizedBox(height: 8),
              ..._buildLineItems(),
              _addTile(
                'Agregar producto',
                _products.isEmpty ? null : _openAddProductSheet,
              ),
              const SizedBox(height: 18),
              _sectionTitle('Transporte'),
              const SizedBox(height: 8),
              _transportSection(),
              const SizedBox(height: 18),
              _sectionTitle('Destino'),
              const SizedBox(height: 8),
              _destinationSection(),
            ] else ...[
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
        final authorized =
            _documentStatus?.authorizationNumber != null &&
            _documentStatus!.authorizationNumber!.isNotEmpty;
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(
                  authorized
                      ? Icons.check_circle_rounded
                      : Icons.info_outline_rounded,
                  color: authorized ? AppColors.success : AppColors.warning,
                  size: 24,
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    _resultTitle,
                    style: const TextStyle(
                      fontFamily: 'Avenir Next',
                      fontWeight: FontWeight.w700,
                      fontSize: 20,
                      color: AppColors.textPrimary,
                    ),
                  ),
                ),
              ],
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
            if (_documentStatus != null &&
                _documentStatus!.sriMessages.isNotEmpty) ...[
              const SizedBox(height: 14),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: AppColors.error.withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(
                    color: AppColors.error.withValues(alpha: 0.4),
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: const [
                        Icon(
                          Icons.warning_amber_rounded,
                          color: AppColors.error,
                          size: 20,
                        ),
                        SizedBox(width: 8),
                        Text(
                          'Respuesta del SRI',
                          style: TextStyle(
                            fontFamily: 'Avenir Next',
                            fontWeight: FontWeight.w700,
                            color: AppColors.textPrimary,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    for (final m in _documentStatus!.sriMessages) ...[
                      Text(
                        '•  $m',
                        style: const TextStyle(
                          fontFamily: 'Avenir Next',
                          color: AppColors.textSecondary,
                          fontSize: 13,
                          height: 1.35,
                        ),
                      ),
                      const SizedBox(height: 6),
                    ],
                  ],
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
              title: _isEditing ? 'Editar borrador' : _docTypeLabel,
              subtitle: 'Crear > Validar SRI > Compartir',
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
            if (_step == 0) ...[
              // Footer Guardar (borrador) / Enviar (al SRI), como Ecuafact.
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _submitting ? null : _saveDraft,
                      icon: const Icon(Icons.save_outlined, size: 20),
                      label: const Text('Guardar'),
                      style: OutlinedButton.styleFrom(
                        minimumSize: const Size.fromHeight(52),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: _submitting ? null : _createAndSend,
                      icon: const Icon(Icons.send_rounded, size: 18),
                      label: const Text('Enviar'),
                      style: ElevatedButton.styleFrom(
                        minimumSize: const Size.fromHeight(52),
                      ),
                    ),
                  ),
                ],
              ),
              if (_submitting) ...[
                const SizedBox(height: 10),
                const Center(
                  child: SizedBox(
                    width: 22,
                    height: 22,
                    child: CircularProgressIndicator(strokeWidth: 2.4),
                  ),
                ),
              ],
            ] else ...[
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: _submitting ? null : _advanceFlow,
                  icon: Icon(
                    _step == 2
                        ? Icons.add_rounded
                        : Icons.arrow_forward_rounded,
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
