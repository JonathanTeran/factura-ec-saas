import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/page_header.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/customer_provider.dart';

/// Form to create a new customer (cliente) against the real backend.
class CustomerCreateScreen extends ConsumerStatefulWidget {
  const CustomerCreateScreen({super.key});

  @override
  ConsumerState<CustomerCreateScreen> createState() =>
      _CustomerCreateScreenState();
}

class _CustomerCreateScreenState extends ConsumerState<CustomerCreateScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameCtrl = TextEditingController();
  final _identificationCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _addressCtrl = TextEditingController();

  // Maps to App\Enums\IdentificationType values.
  String _identificationType = '05';
  bool _submitting = false;
  bool _lookingUp = false;
  bool _showOptional = false;
  String? _errorText;

  /// La consulta al catastro del SRI aplica solo para RUC (04) y Cédula (05).
  bool get _canLookupSri =>
      _identificationType == '04' || _identificationType == '05';

  static const _identificationTypes = <DropdownMenuItem<String>>[
    DropdownMenuItem(value: '04', child: Text('RUC')),
    DropdownMenuItem(value: '05', child: Text('Cédula')),
    DropdownMenuItem(value: '06', child: Text('Pasaporte')),
    DropdownMenuItem(value: '07', child: Text('Consumidor Final')),
    DropdownMenuItem(value: '08', child: Text('Identificación del Exterior')),
  ];

  @override
  void dispose() {
    _nameCtrl.dispose();
    _identificationCtrl.dispose();
    _emailCtrl.dispose();
    _phoneCtrl.dispose();
    _addressCtrl.dispose();
    super.dispose();
  }

  /// Al elegir "Consumidor Final" precarga la identificación y el nombre,
  /// igual que la web.
  void _onTypeChanged(String? value) {
    setState(() {
      _identificationType = value ?? '05';
      if (_identificationType == '07') {
        _identificationCtrl.text = '9999999999999';
        if (_nameCtrl.text.trim().isEmpty) {
          _nameCtrl.text = 'CONSUMIDOR FINAL';
        }
      } else if (_identificationCtrl.text == '9999999999999') {
        _identificationCtrl.clear();
        if (_nameCtrl.text.trim() == 'CONSUMIDOR FINAL') _nameCtrl.clear();
      }
    });
  }

  /// Consulta el catastro público del SRI y autocompleta nombre y dirección,
  /// replicando el comportamiento del panel web.
  Future<void> _lookupSri() async {
    final id = _identificationCtrl.text.trim();
    if (!RegExp(r'^([0-9]{10}|[0-9]{13})$').hasMatch(id)) {
      setState(
        () => _errorText =
            'Ingresá una cédula (10 dígitos) o RUC (13) para consultar el SRI.',
      );
      return;
    }

    FocusScope.of(context).unfocus();
    setState(() {
      _lookingUp = true;
      _errorText = null;
    });

    try {
      final result = await ref
          .read(v1ApiServiceProvider)
          .lookupIdentification(id);
      if (!mounted) return;
      setState(() {
        if (_nameCtrl.text.trim().isEmpty) {
          _nameCtrl.text = result.businessName;
        }
        if (_addressCtrl.text.trim().isEmpty && result.address != null) {
          _addressCtrl.text = result.address!;
          _showOptional = true; // mostrar la dirección que trajo el SRI
        }
      });
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Datos traídos del SRI.')),
      );
    } catch (_) {
      if (!mounted) return;
      setState(
        () => _errorText =
            'No se encontró en el catastro del SRI. Ingresá los datos manualmente.',
      );
    } finally {
      if (mounted) setState(() => _lookingUp = false);
    }
  }

  Future<void> _submit() async {
    if (_submitting) return;
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _submitting = true;
      _errorText = null;
    });

    try {
      await ref.read(v1ApiServiceProvider).createCustomer({
        'identification_type': _identificationType,
        'identification_number': _identificationCtrl.text.trim(),
        'name': _nameCtrl.text.trim(),
        if (_emailCtrl.text.trim().isNotEmpty) 'email': _emailCtrl.text.trim(),
        if (_phoneCtrl.text.trim().isNotEmpty) 'phone': _phoneCtrl.text.trim(),
        if (_addressCtrl.text.trim().isNotEmpty)
          'address': _addressCtrl.text.trim(),
        'is_active': true,
      });

      ref.invalidate(customersProvider);

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Cliente creado correctamente.')),
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
              title: 'Nuevo cliente',
              subtitle: 'Registra los datos del cliente',
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
                          onChanged: _onTypeChanged,
                        ),
                        const SizedBox(height: 10),
                        TextFormField(
                          controller: _identificationCtrl,
                          keyboardType: TextInputType.number,
                          textInputAction: _canLookupSri
                              ? TextInputAction.search
                              : TextInputAction.next,
                          onFieldSubmitted: _canLookupSri
                              ? (_) => _lookupSri()
                              : null,
                          decoration: InputDecoration(
                            labelText: 'Número de identificación',
                            helperText: _canLookupSri
                                ? 'Escribí el RUC/cédula y tocá la lupa para traer los datos del SRI'
                                : null,
                            helperMaxLines: 2,
                            suffixIcon: _canLookupSri
                                ? (_lookingUp
                                      ? const Padding(
                                          padding: EdgeInsets.all(12),
                                          child: SizedBox(
                                            width: 18,
                                            height: 18,
                                            child: CircularProgressIndicator(
                                              strokeWidth: 2,
                                            ),
                                          ),
                                        )
                                      : IconButton(
                                          tooltip: 'Consultar SRI',
                                          onPressed: _submitting
                                              ? null
                                              : _lookupSri,
                                          icon: const Icon(Icons.search_rounded),
                                        ))
                                : null,
                          ),
                          validator: (value) =>
                              (value == null || value.trim().isEmpty)
                              ? 'Requerido'
                              : null,
                        ),
                        const SizedBox(height: 10),
                        TextFormField(
                          controller: _nameCtrl,
                          textCapitalization: TextCapitalization.words,
                          decoration: const InputDecoration(
                            labelText: 'Nombre / Razón social',
                          ),
                          validator: (value) =>
                              (value == null || value.trim().isEmpty)
                              ? 'Requerido'
                              : null,
                        ),
                        const SizedBox(height: 4),
                        // Datos de contacto opcionales, ocultos por defecto para
                        // que el formulario se vea simple: solo tipo, número y
                        // nombre a la vista.
                        if (!_showOptional)
                          Align(
                            alignment: Alignment.centerLeft,
                            child: TextButton.icon(
                              onPressed: () =>
                                  setState(() => _showOptional = true),
                              icon: const Icon(Icons.add_rounded, size: 18),
                              label: const Text(
                                'Agregar correo, teléfono y dirección',
                              ),
                            ),
                          ),
                        if (_showOptional) ...[
                          const SizedBox(height: 6),
                          TextFormField(
                            controller: _emailCtrl,
                            keyboardType: TextInputType.emailAddress,
                            decoration: const InputDecoration(
                              labelText: 'Correo (opcional)',
                            ),
                            validator: (value) {
                              final v = value?.trim() ?? '';
                              if (v.isEmpty) return null;
                              return v.contains('@') ? null : 'Correo no válido';
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
                        ],
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
                label: Text(_submitting ? 'Guardando...' : 'Guardar cliente'),
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
