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
  const NewDocumentScreen({super.key});

  @override
  ConsumerState<NewDocumentScreen> createState() => _NewDocumentScreenState();
}

class _NewDocumentScreenState extends ConsumerState<NewDocumentScreen> {
  int _step = 0;
  String _selectedType = '01';
  bool _notifyCustomer = true;
  bool _loading = true;
  bool _submitting = false;
  String? _errorText;

  final TextEditingController _quantityCtrl = TextEditingController(text: '1');

  List<ApiCompany> _companies = const [];
  List<ApiEmissionPoint> _emissionPoints = const [];
  List<ApiCustomer> _customers = const [];
  List<ApiProduct> _products = const [];

  int? _companyId;
  int? _emissionPointId;
  int? _customerId;
  int? _productId;

  ApiDocument? _createdDocument;
  ApiDocumentStatus? _documentStatus;

  @override
  void initState() {
    super.initState();
    unawaited(_loadOptions());
  }

  @override
  void dispose() {
    _quantityCtrl.dispose();
    super.dispose();
  }

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
        _productId = productsPage.items.isNotEmpty
            ? productsPage.items.first.id
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

  ApiProduct? get _selectedProduct {
    final selectedId = _productId;
    if (selectedId == null) return null;
    for (final product in _products) {
      if (product.id == selectedId) return product;
    }
    return null;
  }

  double get _quantity {
    final raw = _quantityCtrl.text.replaceAll(',', '.').trim();
    final parsed = double.tryParse(raw);
    if (parsed == null || parsed <= 0) return 1;
    return parsed;
  }

  double get _subtotal => (_selectedProduct?.unitPrice ?? 0) * _quantity;
  double get _taxValue => _subtotal * (_selectedProduct?.taxRate ?? 0) / 100;
  double get _total => _subtotal + _taxValue;

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
      _quantityCtrl.text = '1';
    });
  }

  Future<void> _createDocument() async {
    if (_companyId == null ||
        _customerId == null ||
        _productId == null ||
        _emissionPointId == null) {
      setState(() => _errorText = 'Faltan datos para crear el documento.');
      return;
    }

    final product = _selectedProduct;
    if (product == null) {
      setState(() => _errorText = 'Selecciona un producto válido.');
      return;
    }

    setState(() {
      _submitting = true;
      _errorText = null;
    });
    try {
      final document = await ref
          .read(v1ApiServiceProvider)
          .createDocument(
            CreateDocumentInput(
              companyId: _companyId!,
              customerId: _customerId!,
              emissionPointId: _emissionPointId!,
              documentType: _selectedType,
              product: product,
              quantity: _quantity,
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

  Widget _stepContent(BuildContext context) {
    if (_loading) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 30),
        child: Center(child: CircularProgressIndicator()),
      );
    }

    if (_companies.isEmpty || _customers.isEmpty || _products.isEmpty) {
      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'No se pudo iniciar el flujo',
            style: TextStyle(
              fontFamily: 'Avenir Next',
              fontWeight: FontWeight.w700,
              fontSize: 20,
              color: AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Necesitas al menos una empresa, un cliente y un producto activos.',
            style: TextStyle(
              fontFamily: 'Avenir Next',
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 14),
          OutlinedButton.icon(
            onPressed: _loadOptions,
            icon: const Icon(Icons.refresh_rounded),
            label: const Text('Recargar datos'),
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
                DropdownMenuItem(value: '07', child: Text('Retención')),
              ],
              onChanged: (value) => setState(() {
                _selectedType = value ?? '01';
              }),
            ),
            const SizedBox(height: 10),
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
            DropdownButtonFormField<int>(
              initialValue: _emissionPointId,
              decoration: const InputDecoration(labelText: 'Punto de emisión'),
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
            const SizedBox(height: 10),
            DropdownButtonFormField<int>(
              initialValue: _productId,
              decoration: const InputDecoration(labelText: 'Producto'),
              items: _products
                  .map(
                    (product) => DropdownMenuItem(
                      value: product.id,
                      child: Text(
                        '${product.name} · ${currency(product.unitPrice)}',
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  )
                  .toList(),
              onChanged: (value) => setState(() => _productId = value),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _quantityCtrl,
              keyboardType: const TextInputType.numberWithOptions(
                decimal: true,
              ),
              onChanged: (_) => setState(() {}),
              decoration: InputDecoration(
                labelText: 'Cantidad',
                hintText: _selectedProduct == null ? '1' : '1.00',
              ),
            ),
            const SizedBox(height: 12),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: AppColors.surfaceDark.withValues(alpha: 0.8),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppColors.border),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Subtotal: ${currency(_subtotal)}',
                    style: const TextStyle(
                      fontFamily: 'Avenir Next',
                      color: AppColors.textSecondary,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Impuesto: ${currency(_taxValue)}',
                    style: const TextStyle(
                      fontFamily: 'Avenir Next',
                      color: AppColors.textSecondary,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Total estimado: ${currency(_total)}',
                    style: const TextStyle(
                      fontFamily: 'Avenir Next',
                      color: AppColors.textPrimary,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            ),
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
