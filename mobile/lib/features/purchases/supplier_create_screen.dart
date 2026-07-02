import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/page_header.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/purchase_provider.dart';

/// Form to create a new supplier (proveedor) against the real backend.
class SupplierCreateScreen extends ConsumerStatefulWidget {
  const SupplierCreateScreen({super.key});

  @override
  ConsumerState<SupplierCreateScreen> createState() =>
      _SupplierCreateScreenState();
}

class _SupplierCreateScreenState extends ConsumerState<SupplierCreateScreen> {
  final _formKey = GlobalKey<FormState>();
  final _businessNameCtrl = TextEditingController();
  final _identificationCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _addressCtrl = TextEditingController();

  // Maps to App\Enums\IdentificationType values.
  String _identificationType = '04';
  bool _submitting = false;
  String? _errorText;

  static const _identificationTypes = <DropdownMenuItem<String>>[
    DropdownMenuItem(value: '04', child: Text('RUC')),
    DropdownMenuItem(value: '05', child: Text('Cédula')),
    DropdownMenuItem(value: '06', child: Text('Pasaporte')),
    DropdownMenuItem(value: '08', child: Text('Identificación del Exterior')),
  ];

  @override
  void dispose() {
    _businessNameCtrl.dispose();
    _identificationCtrl.dispose();
    _emailCtrl.dispose();
    _phoneCtrl.dispose();
    _addressCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_submitting) return;
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _submitting = true;
      _errorText = null;
    });

    try {
      await ref.read(v1ApiServiceProvider).createSupplier({
        'identification_type': _identificationType,
        'identification': _identificationCtrl.text.trim(),
        'business_name': _businessNameCtrl.text.trim(),
        if (_emailCtrl.text.trim().isNotEmpty) 'email': _emailCtrl.text.trim(),
        if (_phoneCtrl.text.trim().isNotEmpty) 'phone': _phoneCtrl.text.trim(),
        if (_addressCtrl.text.trim().isNotEmpty)
          'address': _addressCtrl.text.trim(),
      });

      ref.invalidate(suppliersProvider);

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Proveedor creado correctamente.')),
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

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            PageHeader(
              title: 'Nuevo proveedor',
              subtitle: 'Registra los datos del proveedor',
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
                        DropdownButtonFormField<String>(
                          initialValue: _identificationType,
                          decoration: const InputDecoration(
                            labelText: 'Tipo de identificación',
                          ),
                          items: _identificationTypes,
                          onChanged: (value) => setState(
                            () => _identificationType = value ?? '04',
                          ),
                        ),
                        const SizedBox(height: 10),
                        TextFormField(
                          controller: _identificationCtrl,
                          keyboardType: TextInputType.number,
                          decoration: const InputDecoration(
                            labelText: 'Identificación',
                          ),
                          validator: (value) =>
                              (value == null || value.trim().isEmpty)
                              ? 'Requerido'
                              : null,
                        ),
                        const SizedBox(height: 10),
                        TextFormField(
                          controller: _businessNameCtrl,
                          textCapitalization: TextCapitalization.words,
                          decoration: const InputDecoration(
                            labelText: 'Razón social',
                          ),
                          validator: (value) =>
                              (value == null || value.trim().isEmpty)
                              ? 'Requerido'
                              : null,
                        ),
                        const SizedBox(height: 10),
                        TextFormField(
                          controller: _emailCtrl,
                          keyboardType: TextInputType.emailAddress,
                          decoration: const InputDecoration(
                            labelText: 'Correo (opcional)',
                          ),
                          validator: (value) {
                            final v = value?.trim() ?? '';
                            if (v.isEmpty) return null;
                            return v.contains('@')
                                ? null
                                : 'Correo no válido';
                          },
                        ),
                        const SizedBox(height: 10),
                        TextFormField(
                          controller: _phoneCtrl,
                          keyboardType: TextInputType.phone,
                          decoration: const InputDecoration(
                            labelText: 'Teléfono (opcional)',
                          ),
                        ),
                        const SizedBox(height: 10),
                        TextFormField(
                          controller: _addressCtrl,
                          decoration: const InputDecoration(
                            labelText: 'Dirección (opcional)',
                          ),
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
                  _submitting ? 'Guardando...' : 'Guardar proveedor',
                ),
                style: ElevatedButton.styleFrom(
                  minimumSize: const Size.fromHeight(52),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
