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

  /// Tras autenticar, enruta al onboarding si aún no hay empresa configurada.
  Future<void> _routeAfterAuth() async {
    try {
      final status = await ref.read(v1ApiServiceProvider).onboardingStatus();
      if (!mounted) return;
      context.go(status.hasCompany ? '/' : '/onboarding');
    } catch (_) {
      if (mounted) context.go('/');
    }
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
      await _routeAfterAuth();
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
          // La biometría es opcional; no bloquea el ingreso.
        }
      }

      ref.invalidate(backendAvailabilityProvider);
      ref.invalidate(meProvider);
      ref.invalidate(companiesProvider);
      if (!mounted) return;
      await _routeAfterAuth();
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
    final textTheme = Theme.of(context).textTheme;

    return Scaffold(
      body: Stack(
        children: [
          const Positioned.fill(child: AuroraBackground()),
          SafeArea(
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(28, 32, 28, 32),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    const Center(child: _BrandMark()),
                    const SizedBox(height: 26),
                    Text(
                      'Bienvenido',
                      textAlign: TextAlign.center,
                      style: textTheme.displaySmall,
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Ingresá a tu cuenta',
                      textAlign: TextAlign.center,
                      style: textTheme.bodyMedium,
                    ),
                    const SizedBox(height: 28),
                    TextField(
                      controller: _emailCtrl,
                      keyboardType: TextInputType.emailAddress,
                      textInputAction: TextInputAction.next,
                      decoration: const InputDecoration(
                        labelText: 'Correo',
                        prefixIcon: Icon(Icons.alternate_email_rounded),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _passwordCtrl,
                      obscureText: _obscurePassword,
                      onSubmitted: (_) => _submit(),
                      decoration: InputDecoration(
                        labelText: 'Contraseña',
                        prefixIcon: const Icon(Icons.lock_outline_rounded),
                        suffixIcon: IconButton(
                          onPressed: () => setState(
                            () => _obscurePassword = !_obscurePassword,
                          ),
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
                        onPressed: _submitting
                            ? null
                            : () => context.push('/forgot-password'),
                        child: const Text('¿Olvidaste tu contraseña?'),
                      ),
                    ),
                    if (_errorText != null) ...[
                      const SizedBox(height: 4),
                      Text(
                        _errorText!,
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          fontFamily: 'Avenir Next',
                          color: AppColors.error,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      const SizedBox(height: 8),
                    ],
                    const SizedBox(height: 8),
                    SizedBox(
                      height: 52,
                      child: FilledButton(
                        onPressed: _submitting ? null : _submit,
                        child: _submitting
                            ? const SizedBox(
                                width: 18,
                                height: 18,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2.2,
                                ),
                              )
                            : const Text('Entrar'),
                      ),
                    ),
                    if (_quickBiometricEntry) ...[
                      const SizedBox(height: 10),
                      OutlinedButton.icon(
                        onPressed: _submitting ? null : _submitBiometric,
                        icon: Icon(
                          _biometricTypeLabel.contains('Face')
                              ? Icons.face_unlock_rounded
                              : Icons.fingerprint_rounded,
                        ),
                        label: Text('Entrar con $_biometricTypeLabel'),
                      ),
                    ],
                    const SizedBox(height: 14),
                    TextButton(
                      onPressed:
                          _submitting ? null : () => context.go('/register'),
                      child: const Text('Crear cuenta nueva'),
                    ),
                  ],
                ).animate().fadeIn(duration: 340.ms).slideY(begin: 0.06),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// Marca minimalista de Facturón: cuadrado con borde tricolor (bandera) y F.
class _BrandMark extends StatelessWidget {
  const _BrandMark();

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Container(
          padding: const EdgeInsets.all(3),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(22),
            gradient: const LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [Color(0xFFFFCE00), Color(0xFF0653C6), Color(0xFFEF3340)],
            ),
          ),
          child: Container(
            width: 66,
            height: 66,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: const Color(0xFF0B1424),
              borderRadius: BorderRadius.circular(19),
            ),
            child: const Text(
              'F',
              style: TextStyle(
                fontFamily: 'Avenir Next',
                fontWeight: FontWeight.w800,
                fontSize: 38,
                color: Colors.white,
              ),
            ),
          ),
        ),
        const SizedBox(height: 12),
        const Text(
          'Facturón EC',
          style: TextStyle(
            fontFamily: 'Avenir Next',
            fontWeight: FontWeight.w800,
            fontSize: 22,
            letterSpacing: -0.5,
            color: AppColors.textPrimary,
          ),
        ),
      ],
    );
  }
}
