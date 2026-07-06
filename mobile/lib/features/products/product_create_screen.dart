import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/page_header.dart';
import '../../data/models/product_model.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/product_provider.dart';

/// Form to create or edit a product/service against the real backend.
class ProductCreateScreen extends ConsumerStatefulWidget {
  /// Cuando viene un producto, la pantalla entra en modo edición.
  final ApiProduct? product;

  const ProductCreateScreen({super.key, this.product});

  @override
  ConsumerState<ProductCreateScreen> createState() =>
      _ProductCreateScreenState();
}

class _ProductCreateScreenState extends ConsumerState<ProductCreateScreen> {
  final _formKey = GlobalKey<FormState>();
  final _codeCtrl = TextEditingController();
  final _nameCtrl = TextEditingController();
  final _priceCtrl = TextEditingController();

  String _type = 'product';
  // SRI tax rate options: 0%, 15% (current Ecuador IVA).
  double _taxRate = 15;
  bool _trackInventory = false;
  bool _isActive = true;
  bool _submitting = false;
  String? _errorText;

  bool get _isEdit => widget.product != null;

  @override
  void initState() {
    super.initState();
    final p = widget.product;
    if (p != null) {
      _codeCtrl.text = p.code;
      _nameCtrl.text = p.name;
      _priceCtrl.text = p.unitPrice.toStringAsFixed(2);
      _type = p.type == 'service' ? 'service' : 'product';
      _taxRate = p.taxRate <= 0 ? 0 : 15;
      _trackInventory = p.trackInventory;
      _isActive = p.isActive;
    }
  }

  @override
  void dispose() {
    _codeCtrl.dispose();
    _nameCtrl.dispose();
    _priceCtrl.dispose();
    super.dispose();
  }

  /// Maps an IVA rate to the SRI tax percentage code.
  String _taxPercentageCode(double rate) {
    if (rate <= 0) return '0';
    if (rate <= 12.5) return '2';
    return '4';
  }

  Future<void> _submit() async {
    if (_submitting) return;
    if (!_formKey.currentState!.validate()) return;

    final price = double.tryParse(
      _priceCtrl.text.replaceAll(',', '.').trim(),
    );
    if (price == null || price < 0) {
      setState(() => _errorText = 'Precio unitario no válido.');
      return;
    }

    setState(() {
      _submitting = true;
      _errorText = null;
    });

    try {
      final data = <String, dynamic>{
        'code': _codeCtrl.text.trim(),
        'name': _nameCtrl.text.trim(),
        'type': _type,
        'unit_price': price,
        'tax_code': '2',
        'tax_percentage_code': _taxPercentageCode(_taxRate),
        'tax_rate': _taxRate,
        'track_inventory': _trackInventory,
        'is_active': _isActive,
      };

      final api = ref.read(v1ApiServiceProvider);
      if (_isEdit) {
        await api.updateProduct(widget.product!.id, data);
      } else {
        await api.createProduct(data);
      }

      ref.invalidate(productsProvider);

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _isEdit ? 'Producto actualizado.' : 'Producto creado correctamente.',
          ),
        ),
      );
      context.pop();
    } catch (error) {
      setState(() => _errorText = error.toString());
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  Future<void> _delete() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('¿Eliminar producto?'),
        content: Text(
          'Se eliminará "${widget.product!.name}". Esta acción no se puede '
          'deshacer.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancelar'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: TextButton.styleFrom(foregroundColor: AppColors.error),
            child: const Text('Eliminar'),
          ),
        ],
      ),
    );
    if (confirm != true) return;

    setState(() {
      _submitting = true;
      _errorText = null;
    });
    try {
      await ref.read(v1ApiServiceProvider).deleteProduct(widget.product!.id);
      ref.invalidate(productsProvider);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Producto eliminado.')),
      );
      context.pop();
    } catch (error) {
      // El backend puede impedir borrar productos con documentos asociados.
      setState(
        () => _errorText =
            'No se pudo eliminar. Si el producto ya se usó en documentos, '
            'desactivalo en vez de eliminarlo.',
      );
    } finally {
      if (mounted) setState(() => _submitting = false);
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
              title: _isEdit ? 'Editar producto' : 'Nuevo producto',
              subtitle: _isEdit
                  ? 'Modificá, desactivá o eliminá'
                  : 'Registra un producto o servicio',
              trailing: IconButton.filledTonal(
                tooltip: 'Cancelar',
                onPressed: () => context.pop(),
                icon: const Icon(Icons.close_rounded),
              ),
            ),
            const SizedBox(height: 12),
            Expanded(
              child: SingleChildScrollView(
                child: GlassPanel(
                  child: Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        TextFormField(
                          controller: _codeCtrl,
                          decoration: const InputDecoration(
                            labelText: 'Código',
                          ),
                          validator: (value) =>
                              (value == null || value.trim().isEmpty)
                              ? 'Requerido'
                              : null,
                        ),
                        const SizedBox(height: 10),
                        TextFormField(
                          controller: _nameCtrl,
                          textCapitalization: TextCapitalization.sentences,
                          decoration: const InputDecoration(
                            labelText: 'Nombre',
                          ),
                          validator: (value) =>
                              (value == null || value.trim().isEmpty)
                              ? 'Requerido'
                              : null,
                        ),
                        const SizedBox(height: 10),
                        DropdownButtonFormField<String>(
                          initialValue: _type,
                          decoration: const InputDecoration(labelText: 'Tipo'),
                          items: const [
                            DropdownMenuItem(
                              value: 'product',
                              child: Text('Producto'),
                            ),
                            DropdownMenuItem(
                              value: 'service',
                              child: Text('Servicio'),
                            ),
                          ],
                          onChanged: (value) =>
                              setState(() => _type = value ?? 'product'),
                        ),
                        const SizedBox(height: 10),
                        TextFormField(
                          controller: _priceCtrl,
                          keyboardType: const TextInputType.numberWithOptions(
                            decimal: true,
                          ),
                          decoration: const InputDecoration(
                            labelText: 'Precio unitario',
                            prefixText: '\$ ',
                          ),
                          validator: (value) =>
                              (value == null || value.trim().isEmpty)
                              ? 'Requerido'
                              : null,
                        ),
                        const SizedBox(height: 10),
                        DropdownButtonFormField<double>(
                          initialValue: _taxRate,
                          decoration: const InputDecoration(labelText: 'IVA'),
                          items: const [
                            DropdownMenuItem(value: 0, child: Text('0%')),
                            DropdownMenuItem(value: 15, child: Text('15%')),
                          ],
                          onChanged: (value) =>
                              setState(() => _taxRate = value ?? 15),
                        ),
                        const SizedBox(height: 4),
                        SwitchListTile.adaptive(
                          value: _trackInventory,
                          contentPadding: EdgeInsets.zero,
                          activeThumbColor: AppColors.primary,
                          title: const Text(
                            'Controlar inventario',
                            style: TextStyle(
                              fontFamily: 'Avenir Next',
                              color: AppColors.textPrimary,
                            ),
                          ),
                          onChanged: (value) =>
                              setState(() => _trackInventory = value),
                        ),
                        SwitchListTile.adaptive(
                          value: _isActive,
                          contentPadding: EdgeInsets.zero,
                          activeThumbColor: AppColors.primary,
                          title: const Text(
                            'Activo',
                            style: TextStyle(
                              fontFamily: 'Avenir Next',
                              color: AppColors.textPrimary,
                            ),
                          ),
                          subtitle: const Text(
                            'Si lo desactivás, no aparece al crear documentos.',
                            style: TextStyle(
                              fontFamily: 'Avenir Next',
                              color: AppColors.textMuted,
                              fontSize: 12,
                            ),
                          ),
                          onChanged: (value) =>
                              setState(() => _isActive = value),
                        ),
                        if (_errorText != null) ...[
                          const SizedBox(height: 12),
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
            ),
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: _submitting ? null : _submit,
                icon: _submitting
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.save_rounded),
                label: Text(
                  _submitting
                      ? 'Guardando...'
                      : (_isEdit ? 'Guardar cambios' : 'Guardar producto'),
                ),
                style: ElevatedButton.styleFrom(
                  minimumSize: const Size.fromHeight(52),
                ),
              ),
            ),
            if (_isEdit) ...[
              const SizedBox(height: 8),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: _submitting ? null : _delete,
                  icon: const Icon(Icons.delete_outline_rounded, size: 20),
                  label: const Text('Eliminar producto'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.error,
                    side: const BorderSide(color: AppColors.error),
                    minimumSize: const Size.fromHeight(50),
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
