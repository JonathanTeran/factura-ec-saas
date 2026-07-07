import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/page_header.dart';
import '../../data/models/customer_model.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/customer_provider.dart';

/// Form to create or edit a customer (cliente) against the real backend.
class CustomerCreateScreen extends ConsumerStatefulWidget {
  /// Cuando viene un cliente, la pantalla entra en modo edición.
  final ApiCustomer? customer;

  const CustomerCreateScreen({super.key, this.customer});

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
  bool _isActive = true;
  bool _submitting = false;
  bool _lookingUp = false;
  bool _showOptional = false;
  String? _errorText;

  bool get _isEdit => widget.customer != null;

  @override
  void initState() {
    super.initState();
    final c = widget.customer;
    if (c != null) {
      _identificationType = c.identificationTypeCode;
      _identificationCtrl.text = c.identificationNumber;
      _nameCtrl.text = c.name;
      _emailCtrl.text = c.email ?? '';
      _phoneCtrl.text = c.phone ?? '';
      _addressCtrl.text = c.address ?? '';
      _isActive = c.isActive;
      _showOptional =
          (c.email?.isNotEmpty ?? false) ||
          (c.phone?.isNotEmpty ?? false) ||
          (c.address?.isNotEmpty ?? false);
    }
  }

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
      final data = <String, dynamic>{
        'identification_type': _identificationType,
        'identification_number': _identificationCtrl.text.trim(),
        'name': _nameCtrl.text.trim(),
        'email': _emailCtrl.text.trim().isEmpty ? null : _emailCtrl.text.trim(),
        'phone': _phoneCtrl.text.trim().isEmpty ? null : _phoneCtrl.text.trim(),
        'address': _addressCtrl.text.trim().isEmpty
            ? null
            : _addressCtrl.text.trim(),
        'is_active': _isActive,
      };

      final api = ref.read(v1ApiServiceProvider);
      if (_isEdit) {
        await api.updateCustomer(widget.customer!.id, data);
      } else {
        await api.createCustomer(data);
      }

      ref.invalidate(customersProvider);

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _isEdit ? 'Cliente actualizado.' : 'Cliente creado correctamente.',
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
        title: const Text('¿Eliminar cliente?'),
        content: Text(
          'Se eliminará "${widget.customer!.name}". Esta acción no se puede '
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
      await ref.read(v1ApiServiceProvider).deleteCustomer(widget.customer!.id);
      ref.invalidate(customersProvider);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Cliente eliminado.')),
      );
      context.pop();
    } catch (error) {
      setState(
        () => _errorText =
            'No se pudo eliminar. Si el cliente ya se usó en documentos, '
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
              title: _isEdit ? 'Editar cliente' : 'Nuevo cliente',
              subtitle: _isEdit
                  ? 'Modificá, desactivá o eliminá'
                  : 'Registra los datos del cliente',
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
                          validator: (value) {
                            final v = value?.trim() ?? '';
                            if (v.isEmpty) return 'Requerido';
                            if (_identificationType == '05' &&
                                !RegExp(r'^[0-9]{10}$').hasMatch(v)) {
                              return 'La cédula debe tener 10 dígitos.';
                            }
                            if (_identificationType == '04' &&
                                !RegExp(r'^[0-9]{13}$').hasMatch(v)) {
                              return 'El RUC debe tener 13 dígitos.';
                            }
                            return null;
                          },
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
                        if (_isEdit) ...[
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
                            onChanged: (v) => setState(() => _isActive = v),
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
                label: Text(
                  _submitting
                      ? 'Guardando...'
                      : (_isEdit ? 'Guardar cambios' : 'Guardar cliente'),
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
                  label: const Text('Eliminar cliente'),
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
