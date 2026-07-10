import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/theme/app_theme.dart';
import '../../core/utils/password_policy.dart';
import '../../core/widgets/aurora_background.dart';
import '../../core/widgets/glass_panel.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/company_provider.dart';

class RegisterScreen extends ConsumerStatefulWidget {
  const RegisterScreen({super.key});

  @override
  ConsumerState<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends ConsumerState<RegisterScreen> {
  final _nameCtrl = TextEditingController();
  final _companyCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _passwordCtrl = TextEditingController();
  final _confirmCtrl = TextEditingController();
  bool _submitting = false;
  bool _acceptedTerms = false;
  String? _errorText;

  Future<void> _openLegal(String path) async {
    try {
      await launchUrl(
        Uri.parse('https://facturacion.amephia.com$path'),
        mode: LaunchMode.externalApplication,
      );
    } catch (_) {
      // Sin navegador disponible: no bloquea el registro.
    }
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _companyCtrl.dispose();
    _emailCtrl.dispose();
    _passwordCtrl.dispose();
    _confirmCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();

    // Validación local con mensaje claro antes de llamar a la API.
    final policyError = validatePassword(_passwordCtrl.text);
    if (policyError != null) {
      setState(() => _errorText = policyError);
      return;
    }
    if (_passwordCtrl.text != _confirmCtrl.text) {
      setState(() => _errorText = 'Las contraseñas no coinciden.');
      return;
    }
    if (!_acceptedTerms) {
      setState(() => _errorText =
          'Debes aceptar los Términos y Condiciones y la Política de Privacidad.');
      return;
    }

    setState(() {
      _submitting = true;
      _errorText = null;
    });

    try {
      await ref
          .read(v1ApiServiceProvider)
          .register(
            name: _nameCtrl.text,
            email: _emailCtrl.text,
            password: _passwordCtrl.text,
            passwordConfirmation: _confirmCtrl.text,
            companyName: _companyCtrl.text,
          );
      ref.invalidate(backendAvailabilityProvider);
      ref.invalidate(meProvider);
      ref.invalidate(companiesProvider);
      if (!mounted) return;
      // Cuenta nueva: aún no tiene empresa configurada -> onboarding.
      context.go('/onboarding');
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
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(20, 24, 20, 24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Crea tu cuenta', style: textTheme.displaySmall),
                  const SizedBox(height: 8),
                  Text(
                    'Conecta tu empresa y empieza a emitir hoy.',
                    style: textTheme.bodyMedium,
                  ),
                  const SizedBox(height: 18),
                  GlassPanel(
                    child: Column(
                      children: [
                        TextField(
                          controller: _nameCtrl,
                          textInputAction: TextInputAction.next,
                          decoration: const InputDecoration(
                            labelText: 'Nombre completo',
                          ),
                        ),
                        const SizedBox(height: 10),
                        TextField(
                          controller: _companyCtrl,
                          textInputAction: TextInputAction.next,
                          decoration: const InputDecoration(
                            labelText: 'Nombre de empresa',
                          ),
                        ),
                        const SizedBox(height: 10),
                        TextField(
                          controller: _emailCtrl,
                          keyboardType: TextInputType.emailAddress,
                          textInputAction: TextInputAction.next,
                          decoration: const InputDecoration(
                            labelText: 'Correo',
                          ),
                        ),
                        const SizedBox(height: 10),
                        TextField(
                          controller: _passwordCtrl,
                          obscureText: true,
                          textInputAction: TextInputAction.next,
                          decoration: const InputDecoration(
                            labelText: 'Contraseña',
                            helperText: passwordPolicyHint,
                            helperMaxLines: 2,
                          ),
                        ),
                        const SizedBox(height: 10),
                        TextField(
                          controller: _confirmCtrl,
                          obscureText: true,
                          onSubmitted: (_) => _submit(),
                          decoration: const InputDecoration(
                            labelText: 'Confirmar contraseña',
                          ),
                        ),
                        const SizedBox(height: 12),
                        // Aceptación de Términos + Privacidad (obligatoria;
                        // el backend también la exige y guarda constancia).
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            SizedBox(
                              width: 28,
                              height: 28,
                              child: Checkbox(
                                value: _acceptedTerms,
                                onChanged: (v) => setState(
                                    () => _acceptedTerms = v ?? false),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Padding(
                                padding: const EdgeInsets.only(top: 4),
                                child: Text.rich(
                                  TextSpan(
                                    text: 'Acepto los ',
                                    style: const TextStyle(
                                      fontFamily: 'Avenir Next',
                                      fontSize: 12.5,
                                      color: AppColors.textSecondary,
                                      height: 1.4,
                                    ),
                                    children: [
                                      TextSpan(
                                        text: 'Términos y Condiciones',
                                        style: const TextStyle(
                                          color: AppColors.primary,
                                          fontWeight: FontWeight.w700,
                                          decoration:
                                              TextDecoration.underline,
                                        ),
                                        recognizer: TapGestureRecognizer()
                                          ..onTap = () => _openLegal('/terms'),
                                      ),
                                      const TextSpan(text: ' y la '),
                                      TextSpan(
                                        text: 'Política de Privacidad',
                                        style: const TextStyle(
                                          color: AppColors.primary,
                                          fontWeight: FontWeight.w700,
                                          decoration:
                                              TextDecoration.underline,
                                        ),
                                        recognizer: TapGestureRecognizer()
                                          ..onTap = () =>
                                              _openLegal('/privacy'),
                                      ),
                                      const TextSpan(
                                          text: ' de Facturón EC.'),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
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
                        const SizedBox(height: 14),
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton.icon(
                            onPressed: _submitting ? null : _submit,
                            icon: _submitting
                                ? const SizedBox(
                                    width: 16,
                                    height: 16,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                    ),
                                  )
                                : const Icon(Icons.person_add_alt_1_rounded),
                            label: const Text('Crear cuenta'),
                          ),
                        ),
                        const SizedBox(height: 8),
                        SizedBox(
                          width: double.infinity,
                          child: OutlinedButton(
                            onPressed: _submitting
                                ? null
                                : () => context.go('/login'),
                            child: const Text('Ya tengo cuenta'),
                          ),
                        ),
                      ],
                    ),
                  ).animate().fadeIn(duration: 420.ms).slideY(begin: 0.1),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
