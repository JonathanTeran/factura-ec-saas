import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/api/v1_api_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/page_header.dart';
import '../../data/providers/auth_provider.dart';

/// Perfil del usuario y seguridad (nombre, teléfono y cambio de contraseña),
/// como en la web — sin necesidad de salir de la app.
class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});

  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
  final _nameCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _currentPwdCtrl = TextEditingController();
  final _newPwdCtrl = TextEditingController();
  final _confirmPwdCtrl = TextEditingController();

  bool _savingProfile = false;
  bool _savingPassword = false;
  bool _prefilled = false;

  @override
  void dispose() {
    _nameCtrl.dispose();
    _phoneCtrl.dispose();
    _currentPwdCtrl.dispose();
    _newPwdCtrl.dispose();
    _confirmPwdCtrl.dispose();
    super.dispose();
  }

  void _notify(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }

  String _errorMessage(Object error) {
    if (error is ApiException) {
      // Primer mensaje de validación si existe; si no, el mensaje general.
      final details = error.details;
      if (details != null && details.isNotEmpty) {
        final first = details.values.first;
        if (first is List && first.isNotEmpty) return first.first.toString();
        if (first is String && first.isNotEmpty) return first;
      }
      return error.message;
    }
    return 'No se pudo completar la operación. Intenta de nuevo.';
  }

  Future<void> _saveProfile() async {
    final name = _nameCtrl.text.trim();
    if (name.isEmpty) {
      _notify('El nombre no puede estar vacío.');
      return;
    }
    setState(() => _savingProfile = true);
    try {
      await ref.read(v1ApiServiceProvider).updateProfile(
            name: name,
            phone: _phoneCtrl.text,
          );
      ref.invalidate(meProvider);
      _notify('Perfil actualizado.');
    } catch (e) {
      _notify(_errorMessage(e));
    } finally {
      if (mounted) setState(() => _savingProfile = false);
    }
  }

  Future<void> _changePassword() async {
    final current = _currentPwdCtrl.text;
    final next = _newPwdCtrl.text;
    final confirm = _confirmPwdCtrl.text;
    if (current.isEmpty || next.isEmpty || confirm.isEmpty) {
      _notify('Completa los tres campos de contraseña.');
      return;
    }
    if (next.length < 8) {
      _notify('La nueva contraseña debe tener al menos 8 caracteres.');
      return;
    }
    if (next != confirm) {
      _notify('La confirmación no coincide con la nueva contraseña.');
      return;
    }
    setState(() => _savingPassword = true);
    try {
      await ref.read(v1ApiServiceProvider).updatePassword(
            currentPassword: current,
            password: next,
            passwordConfirmation: confirm,
          );
      _currentPwdCtrl.clear();
      _newPwdCtrl.clear();
      _confirmPwdCtrl.clear();
      _notify('Contraseña actualizada correctamente.');
    } catch (e) {
      _notify(_errorMessage(e));
    } finally {
      if (mounted) setState(() => _savingPassword = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final meAsync = ref.watch(meProvider);
    final me = meAsync.valueOrNull;

    // Prellenar una sola vez cuando llega el usuario.
    if (!_prefilled && me != null) {
      _prefilled = true;
      _nameCtrl.text = me.name;
      _phoneCtrl.text = me.phone ?? '';
    }

    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            PageHeader(
              title: 'Cuenta y seguridad',
              subtitle: 'Tu perfil y contraseña de acceso',
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
                    GlassPanel(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Perfil',
                            style: TextStyle(
                              fontFamily: 'Avenir Next',
                              fontWeight: FontWeight.w700,
                              fontSize: 16,
                              color: AppColors.textPrimary,
                            ),
                          ),
                          const SizedBox(height: 12),
                          TextField(
                            controller: _nameCtrl,
                            textCapitalization: TextCapitalization.words,
                            decoration: const InputDecoration(
                              labelText: 'Nombre *',
                            ),
                          ),
                          const SizedBox(height: 12),
                          TextField(
                            controller: _phoneCtrl,
                            keyboardType: TextInputType.phone,
                            decoration: const InputDecoration(
                              labelText: 'Teléfono',
                            ),
                          ),
                          const SizedBox(height: 12),
                          Text(
                            'Correo: ${me?.email ?? '—'}',
                            style: const TextStyle(
                              fontFamily: 'Avenir Next',
                              color: AppColors.textSecondary,
                            ),
                          ),
                          const SizedBox(height: 4),
                          const Text(
                            'El correo de acceso no se puede cambiar desde aquí.',
                            style: TextStyle(
                              fontFamily: 'Avenir Next',
                              color: AppColors.textMuted,
                              fontSize: 12,
                            ),
                          ),
                          const SizedBox(height: 14),
                          SizedBox(
                            width: double.infinity,
                            child: FilledButton.icon(
                              onPressed: _savingProfile ? null : _saveProfile,
                              icon: _savingProfile
                                  ? const SizedBox(
                                      width: 14,
                                      height: 14,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                        color: Colors.white,
                                      ),
                                    )
                                  : const Icon(Icons.save_outlined),
                              label: const Text('Guardar cambios'),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 14),
                    GlassPanel(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Cambiar contraseña',
                            style: TextStyle(
                              fontFamily: 'Avenir Next',
                              fontWeight: FontWeight.w700,
                              fontSize: 16,
                              color: AppColors.textPrimary,
                            ),
                          ),
                          const SizedBox(height: 6),
                          const Text(
                            'Mínimo 8 caracteres. Cerrará la sesión en otros dispositivos.',
                            style: TextStyle(
                              fontFamily: 'Avenir Next',
                              color: AppColors.textMuted,
                              fontSize: 12,
                            ),
                          ),
                          const SizedBox(height: 12),
                          TextField(
                            controller: _currentPwdCtrl,
                            obscureText: true,
                            autocorrect: false,
                            decoration: const InputDecoration(
                              labelText: 'Contraseña actual *',
                            ),
                          ),
                          const SizedBox(height: 12),
                          TextField(
                            controller: _newPwdCtrl,
                            obscureText: true,
                            autocorrect: false,
                            decoration: const InputDecoration(
                              labelText: 'Nueva contraseña *',
                            ),
                          ),
                          const SizedBox(height: 12),
                          TextField(
                            controller: _confirmPwdCtrl,
                            obscureText: true,
                            autocorrect: false,
                            decoration: const InputDecoration(
                              labelText: 'Confirmar nueva contraseña *',
                            ),
                          ),
                          const SizedBox(height: 14),
                          SizedBox(
                            width: double.infinity,
                            child: FilledButton.icon(
                              onPressed:
                                  _savingPassword ? null : _changePassword,
                              icon: _savingPassword
                                  ? const SizedBox(
                                      width: 14,
                                      height: 14,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                        color: Colors.white,
                                      ),
                                    )
                                  : const Icon(Icons.lock_reset_rounded),
                              label: const Text('Actualizar contraseña'),
                            ),
                          ),
                        ],
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
