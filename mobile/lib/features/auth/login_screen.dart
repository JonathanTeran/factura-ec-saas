import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_theme.dart';
import '../../core/widgets/aurora_background.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/company_provider.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  static const String _ameLogoPath = 'assets/images/branding/ame_logo.webp';
  static const String _poweredByLogoPath =
      'assets/images/branding/powered_by_logo.png';

  final _emailCtrl = TextEditingController();
  final _passwordCtrl = TextEditingController();
  bool _obscurePassword = true;
  bool _submitting = false;
  bool _deviceHasBiometrics = false;
  bool _quickBiometricEntry = false;
  bool _enableBiometricsAfterLogin = false;
  String _biometricTypeLabel = 'Biometría';
  String? _errorText;

  @override
  void initState() {
    super.initState();
    unawaited(_loadBiometricOptions());
  }

  Future<void> _loadBiometricOptions() async {
    final status = await ref.read(biometricAuthServiceProvider).status();
    final hasSession = await ref.read(v1ApiServiceProvider).hasSession();

    if (!mounted) return;
    setState(() {
      _deviceHasBiometrics = status.canUse;
      _quickBiometricEntry = status.canUse && status.enabled && hasSession;
      _enableBiometricsAfterLogin =
          status.canUse && (status.enabled || !hasSession);
      _biometricTypeLabel = status.typeLabel;
    });
  }

  @override
  void dispose() {
    _emailCtrl.dispose();
    _passwordCtrl.dispose();
    super.dispose();
  }

  Future<void> _submitBiometric() async {
    FocusScope.of(context).unfocus();
    setState(() {
      _submitting = true;
      _errorText = null;
    });

    try {
      final biometricService = ref.read(biometricAuthServiceProvider);
      final unlocked = await biometricService.authenticate(
        reason: 'Confirma tu identidad para ingresar rápidamente.',
      );
      if (!unlocked) {
        setState(() => _errorText = 'No se pudo validar $_biometricTypeLabel.');
        return;
      }

      await ref.read(v1ApiServiceProvider).me();
      ref.invalidate(backendAvailabilityProvider);
      ref.invalidate(meProvider);
      ref.invalidate(companiesProvider);
      if (!mounted) return;
      context.go('/');
    } catch (error) {
      setState(() => _errorText = error.toString());
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    setState(() {
      _submitting = true;
      _errorText = null;
    });

    try {
      await ref
          .read(v1ApiServiceProvider)
          .login(email: _emailCtrl.text, password: _passwordCtrl.text);

      if (_deviceHasBiometrics) {
        try {
          await ref
              .read(biometricAuthServiceProvider)
              .setEnabled(_enableBiometricsAfterLogin);
          ref.invalidate(biometricStatusProvider);
        } catch (_) {
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text(
                  'No fue posible activar biometría en este dispositivo.',
                ),
              ),
            );
          }
        }
      }

      ref.invalidate(backendAvailabilityProvider);
      ref.invalidate(meProvider);
      ref.invalidate(companiesProvider);
      if (!mounted) return;
      context.go('/');
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
    return Scaffold(
      body: Stack(
        children: [
          const Positioned.fill(child: AuroraBackground()),
          Positioned(
            top: -130,
            left: -80,
            child: GlowOrb(
              size: 260,
              color: AppColors.primary.withValues(alpha: 0.16),
            ),
          ),
          Positioned(
            bottom: 120,
            right: -120,
            child: GlowOrb(
              size: 280,
              color: AppColors.secondary.withValues(alpha: 0.13),
            ),
          ),
          SafeArea(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(20, 24, 20, 24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Image.asset(
                          _ameLogoPath,
                          height: 58,
                          fit: BoxFit.contain,
                          alignment: Alignment.centerLeft,
                          errorBuilder: (_, _, _) => const Text(
                            'Factura EC',
                            style: TextStyle(
                              fontFamily: 'Avenir Next',
                              fontWeight: FontWeight.w800,
                              fontSize: 30,
                              letterSpacing: -0.8,
                              color: AppColors.textPrimary,
                            ),
                          ),
                        ),
                      ),
                      const _AuthInsightPill(
                        icon: Icons.workspace_premium_rounded,
                        label: 'Enterprise',
                        color: AppColors.warning,
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    'Ecosistema Amephia',
                    style: TextStyle(
                      fontFamily: 'Avenir Next',
                      fontWeight: FontWeight.w700,
                      fontSize: 14,
                      color: AppColors.textMuted,
                    ),
                  ),
                  const SizedBox(height: 22),
                  const Text(
                    'Inicia sesión\ncon alto impacto',
                    style: TextStyle(
                      fontFamily: 'Avenir Next',
                      fontWeight: FontWeight.w800,
                      fontSize: 46,
                      height: 0.94,
                      letterSpacing: -1.2,
                      color: AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 10),
                  const Text(
                    'Tu operación tributaria en tiempo real, sin fricción ni pantallas vacías.',
                    style: TextStyle(
                      fontFamily: 'Avenir Next',
                      fontSize: 15,
                      fontWeight: FontWeight.w500,
                      color: AppColors.textSecondary,
                    ),
                  ),
                  const SizedBox(height: 14),
                  const Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      _AuthInsightPill(
                        icon: Icons.verified_rounded,
                        label: 'API en vivo',
                        color: AppColors.success,
                      ),
                      _AuthInsightPill(
                        icon: Icons.speed_rounded,
                        label: 'Flujo < 1 min',
                        color: AppColors.primary,
                      ),
                      _AuthInsightPill(
                        icon: Icons.lock_rounded,
                        label: 'Sesión segura',
                        color: AppColors.info,
                      ),
                    ],
                  ),
                  const SizedBox(height: 18),
                  Container(
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(22),
                          gradient: LinearGradient(
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                            colors: [
                              AppColors.primary.withValues(alpha: 0.55),
                              AppColors.secondary.withValues(alpha: 0.35),
                              AppColors.surface.withValues(alpha: 0.08),
                            ],
                          ),
                        ),
                        padding: const EdgeInsets.all(1.3),
                        child: Container(
                          decoration: BoxDecoration(
                            color: AppColors.surface.withValues(alpha: 0.96),
                            borderRadius: BorderRadius.circular(21),
                          ),
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Acceso seguro',
                                style: TextStyle(
                                  fontFamily: 'Avenir Next',
                                  fontWeight: FontWeight.w800,
                                  fontSize: 20,
                                  color: AppColors.textPrimary,
                                ),
                              ),
                              const SizedBox(height: 4),
                              const Text(
                                'Ingresa con tu cuenta empresarial',
                                style: TextStyle(
                                  fontFamily: 'Avenir Next',
                                  color: AppColors.textSecondary,
                                ),
                              ),
                              const SizedBox(height: 14),
                              TextField(
                                controller: _emailCtrl,
                                keyboardType: TextInputType.emailAddress,
                                textInputAction: TextInputAction.next,
                                decoration: const InputDecoration(
                                  labelText: 'Correo',
                                  hintText: 'tu@empresa.com',
                                  prefixIcon: Icon(
                                    Icons.alternate_email_rounded,
                                  ),
                                ),
                              ),
                              const SizedBox(height: 10),
                              TextField(
                                controller: _passwordCtrl,
                                obscureText: _obscurePassword,
                                onSubmitted: (_) => _submit(),
                                decoration: InputDecoration(
                                  labelText: 'Contraseña',
                                  prefixIcon: const Icon(
                                    Icons.lock_outline_rounded,
                                  ),
                                  suffixIcon: IconButton(
                                    onPressed: () {
                                      setState(() {
                                        _obscurePassword = !_obscurePassword;
                                      });
                                    },
                                    icon: Icon(
                                      _obscurePassword
                                          ? Icons.visibility_rounded
                                          : Icons.visibility_off_rounded,
                                    ),
                                  ),
                                ),
                              ),
                              Align(
                                alignment: Alignment.centerRight,
                                child: TextButton(
                                  onPressed: _submitting ? null : () {},
                                  child: const Text(
                                    '¿Olvidaste tu contraseña?',
                                  ),
                                ),
                              ),
                              if (_quickBiometricEntry) ...[
                                SizedBox(
                                  width: double.infinity,
                                  child: OutlinedButton.icon(
                                    onPressed: _submitting
                                        ? null
                                        : _submitBiometric,
                                    icon: Icon(
                                      _biometricTypeLabel.contains('Face')
                                          ? Icons.face_unlock_rounded
                                          : Icons.fingerprint_rounded,
                                    ),
                                    label: Text(
                                      'Entrar con $_biometricTypeLabel',
                                    ),
                                  ),
                                ),
                                const SizedBox(height: 8),
                              ],
                              if (_deviceHasBiometrics) ...[
                                Container(
                                  width: double.infinity,
                                  padding: const EdgeInsets.fromLTRB(
                                    12,
                                    10,
                                    12,
                                    10,
                                  ),
                                  decoration: BoxDecoration(
                                    color: AppColors.surfaceDark.withValues(
                                      alpha: 0.72,
                                    ),
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(
                                      color: AppColors.border.withValues(
                                        alpha: 0.75,
                                      ),
                                    ),
                                  ),
                                  child: Row(
                                    children: [
                                      Icon(
                                        _biometricTypeLabel.contains('Face')
                                            ? Icons.face_unlock_rounded
                                            : Icons.fingerprint_rounded,
                                        color: AppColors.primaryLight,
                                      ),
                                      const SizedBox(width: 10),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment:
                                              CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              'Activar $_biometricTypeLabel',
                                              style: const TextStyle(
                                                fontFamily: 'Avenir Next',
                                                fontWeight: FontWeight.w700,
                                                color: AppColors.textPrimary,
                                              ),
                                            ),
                                            const SizedBox(height: 2),
                                            const Text(
                                              'En tu próximo ingreso podrás entrar más rápido.',
                                              style: TextStyle(
                                                fontFamily: 'Avenir Next',
                                                color: AppColors.textSecondary,
                                                fontSize: 12,
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                      Switch.adaptive(
                                        value: _enableBiometricsAfterLogin,
                                        onChanged: _submitting
                                            ? null
                                            : (value) {
                                                setState(() {
                                                  _enableBiometricsAfterLogin =
                                                      value;
                                                });
                                              },
                                      ),
                                    ],
                                  ),
                                ),
                                const SizedBox(height: 10),
                              ],
                              if (_errorText != null) ...[
                                Container(
                                  width: double.infinity,
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 12,
                                    vertical: 10,
                                  ),
                                  decoration: BoxDecoration(
                                    color: AppColors.error.withValues(
                                      alpha: 0.14,
                                    ),
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(
                                      color: AppColors.error.withValues(
                                        alpha: 0.4,
                                      ),
                                    ),
                                  ),
                                  child: Text(
                                    _errorText!,
                                    style: const TextStyle(
                                      fontFamily: 'Avenir Next',
                                      color: AppColors.error,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ),
                                const SizedBox(height: 10),
                              ],
                              _ImpactPrimaryButton(
                                label: 'Entrar al panel',
                                icon: Icons.login_rounded,
                                loading: _submitting,
                                onTap: _submitting ? null : _submit,
                              ),
                              const SizedBox(height: 8),
                              SizedBox(
                                width: double.infinity,
                                child: OutlinedButton(
                                  onPressed: _submitting
                                      ? null
                                      : () => context.go('/register'),
                                  child: const Text('Crear cuenta'),
                                ),
                              ),
                            ],
                          ),
                        ),
                      )
                      .animate()
                      .fadeIn(duration: 420.ms)
                      .slideY(begin: 0.12, duration: 420.ms),
                  const SizedBox(height: 14),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(
                      horizontal: 14,
                      vertical: 12,
                    ),
                    decoration: BoxDecoration(
                      color: AppColors.surfaceDark.withValues(alpha: 0.7),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(
                        color: AppColors.border.withValues(alpha: 0.7),
                      ),
                    ),
                    child: Column(
                      children: [
                        Image.asset(
                          _poweredByLogoPath,
                          height: 24,
                          fit: BoxFit.contain,
                        ),
                        const SizedBox(height: 6),
                        const Text(
                          'Protección de sesión, auditoría y sincronización en tiempo real.',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontFamily: 'Avenir Next',
                            color: AppColors.textMuted,
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
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
    );
  }
}

class _AuthInsightPill extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;

  const _AuthInsightPill({
    required this.icon,
    required this.label,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
      decoration: BoxDecoration(
        color: AppColors.surfaceDark.withValues(alpha: 0.86),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 15, color: color),
          const SizedBox(width: 6),
          Text(
            label,
            style: const TextStyle(
              fontFamily: 'Avenir Next',
              fontWeight: FontWeight.w700,
              color: AppColors.textPrimary,
              fontSize: 12,
            ),
          ),
        ],
      ),
    );
  }
}

class _ImpactPrimaryButton extends StatelessWidget {
  final String label;
  final IconData icon;
  final bool loading;
  final VoidCallback? onTap;

  const _ImpactPrimaryButton({
    required this.label,
    required this.icon,
    required this.loading,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final enabled = onTap != null && !loading;

    return Semantics(
      button: true,
      enabled: enabled,
      label: label,
      child: Material(
        color: Colors.transparent,
        child: Ink(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: enabled
                  ? const [AppColors.primary, AppColors.secondary]
                  : [
                      AppColors.textMuted.withValues(alpha: 0.45),
                      AppColors.textMuted.withValues(alpha: 0.45),
                    ],
            ),
            boxShadow: enabled
                ? [
                    BoxShadow(
                      color: AppColors.primary.withValues(alpha: 0.28),
                      blurRadius: 18,
                      offset: const Offset(0, 8),
                    ),
                  ]
                : null,
          ),
          child: InkWell(
            borderRadius: BorderRadius.circular(16),
            onTap: enabled ? onTap : null,
            child: SizedBox(
              width: double.infinity,
              height: 54,
              child: Center(
                child: loading
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2.2,
                          color: AppColors.backgroundDark,
                        ),
                      )
                    : Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(icon, color: AppColors.backgroundDark),
                          const SizedBox(width: 8),
                          Text(
                            label,
                            style: const TextStyle(
                              fontFamily: 'Avenir Next',
                              fontWeight: FontWeight.w800,
                              color: AppColors.backgroundDark,
                              fontSize: 16,
                            ),
                          ),
                        ],
                      ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
