import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_theme.dart';

/// Pantalla de bienvenida para quien abre la app por primera vez (sin sesión):
/// presenta la marca, invita a crear la cuenta y ofrece iniciar sesión.
class WelcomeScreen extends StatelessWidget {
  const WelcomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        width: double.infinity,
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [AppColors.heroGradientStart, AppColors.heroGradientEnd],
          ),
        ),
        child: SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(24, 20, 24, 20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Wordmark de la marca en blanco.
                Row(
                  children: [
                    _MiniBrandTile(),
                    const SizedBox(width: 10),
                    const Text(
                      'Facturón EC',
                      style: TextStyle(
                        fontFamily: 'Avenir Next',
                        color: Colors.white,
                        fontWeight: FontWeight.w800,
                        fontSize: 18,
                        letterSpacing: -0.3,
                      ),
                    ),
                  ],
                ).animate().fadeIn(duration: 400.ms),

                const Spacer(flex: 2),

                // Vista previa de un comprobante: refuerza qué hace la app.
                Center(
                  child: const _InvoicePreview()
                      .animate()
                      .fadeIn(duration: 600.ms, delay: 120.ms)
                      .slideY(begin: 0.14, curve: Curves.easeOutCubic)
                      .scale(begin: const Offset(0.94, 0.94)),
                ),

                const Spacer(flex: 2),

                // Titular + descripción.
                const Text(
                  'Factura al SRI\nen segundos',
                  style: TextStyle(
                    fontFamily: 'Avenir Next',
                    color: Colors.white,
                    fontWeight: FontWeight.w800,
                    fontSize: 34,
                    height: 1.1,
                    letterSpacing: -1,
                  ),
                ).animate().fadeIn(duration: 500.ms, delay: 200.ms).slideY(
                      begin: 0.2,
                      curve: Curves.easeOutCubic,
                    ),
                const SizedBox(height: 14),
                Text(
                  'Emití facturas, notas de crédito, retenciones y guías desde '
                  'tu celular. Firma electrónica y autorización al instante.',
                  style: TextStyle(
                    fontFamily: 'Avenir Next',
                    color: Colors.white.withValues(alpha: 0.88),
                    fontWeight: FontWeight.w500,
                    fontSize: 15,
                    height: 1.45,
                  ),
                ).animate().fadeIn(duration: 500.ms, delay: 320.ms),

                const SizedBox(height: 28),

                // Acción principal: crear cuenta.
                SizedBox(
                  width: double.infinity,
                  height: 56,
                  child: ElevatedButton(
                    onPressed: () => context.go('/register'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.white,
                      foregroundColor: AppColors.primaryDark,
                      elevation: 0,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                    ),
                    child: const Text(
                      'Comenzar',
                      style: TextStyle(
                        fontFamily: 'Avenir Next',
                        fontWeight: FontWeight.w800,
                        fontSize: 17,
                      ),
                    ),
                  ),
                ).animate().fadeIn(duration: 500.ms, delay: 420.ms).slideY(
                      begin: 0.4,
                      curve: Curves.easeOutCubic,
                    ),
                const SizedBox(height: 14),

                // Acción secundaria: iniciar sesión.
                Center(
                  child: TextButton(
                    onPressed: () => context.go('/login'),
                    style: TextButton.styleFrom(
                      foregroundColor: Colors.white,
                    ),
                    child: RichText(
                      text: TextSpan(
                        style: TextStyle(
                          fontFamily: 'Avenir Next',
                          color: Colors.white.withValues(alpha: 0.9),
                          fontWeight: FontWeight.w600,
                          fontSize: 15,
                        ),
                        children: const [
                          TextSpan(text: '¿Ya tienes cuenta?  '),
                          TextSpan(
                            text: 'Inicia sesión',
                            style: TextStyle(
                              fontWeight: FontWeight.w800,
                              decoration: TextDecoration.underline,
                              decorationColor: Colors.white,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ).animate().fadeIn(duration: 500.ms, delay: 500.ms),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

/// Cuadrito de marca (bandera + F) en pequeño, para el encabezado del welcome.
class _MiniBrandTile extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(2),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(11),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFFFFCE00), Color(0xFF0653C6), Color(0xFFEF3340)],
        ),
      ),
      child: Container(
        width: 30,
        height: 30,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: const Color(0xFF0B1424),
          borderRadius: BorderRadius.circular(9),
        ),
        child: const Text(
          'F',
          style: TextStyle(
            fontFamily: 'Avenir Next',
            fontWeight: FontWeight.w800,
            fontSize: 17,
            color: Colors.white,
          ),
        ),
      ),
    );
  }
}

/// Tarjeta blanca que imita un comprobante autorizado: da contexto visual de
/// lo que produce la app (como en las referencias fintech).
class _InvoicePreview extends StatelessWidget {
  const _InvoicePreview();

  @override
  Widget build(BuildContext context) {
    return Transform.rotate(
      angle: -0.04,
      child: Container(
        width: 260,
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(22),
          boxShadow: [
            BoxShadow(
              color: const Color(0xFF0B1424).withValues(alpha: 0.25),
              blurRadius: 30,
              offset: const Offset(0, 16),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Text(
                  'FACTURA',
                  style: TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.textMuted,
                    fontWeight: FontWeight.w700,
                    fontSize: 12,
                    letterSpacing: 0.6,
                  ),
                ),
                const Spacer(),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                  decoration: BoxDecoration(
                    color: AppColors.success.withValues(alpha: 0.16),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: const Text(
                    'AUTORIZADO',
                    style: TextStyle(
                      fontFamily: 'Avenir Next',
                      color: AppColors.success,
                      fontWeight: FontWeight.w800,
                      fontSize: 9,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            const Text(
              '\$248.50',
              style: TextStyle(
                fontFamily: 'Avenir Next',
                color: AppColors.textPrimary,
                fontWeight: FontWeight.w800,
                fontSize: 30,
                letterSpacing: -1,
              ),
            ),
            const SizedBox(height: 4),
            const Text(
              '001-001-000000042',
              style: TextStyle(
                fontFamily: 'Avenir Next',
                color: AppColors.textSecondary,
                fontWeight: FontWeight.w600,
                fontSize: 12,
              ),
            ),
            const SizedBox(height: 16),
            _previewRow(0.9),
            const SizedBox(height: 9),
            _previewRow(0.65),
            const SizedBox(height: 9),
            _previewRow(0.78),
            const SizedBox(height: 16),
            Row(
              children: [
                Container(
                  width: 34,
                  height: 34,
                  decoration: BoxDecoration(
                    color: AppColors.primary.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(
                    Icons.verified_rounded,
                    color: AppColors.primary,
                    size: 20,
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: const [
                      Text(
                        'Enviada al SRI',
                        style: TextStyle(
                          fontFamily: 'Avenir Next',
                          color: AppColors.textPrimary,
                          fontWeight: FontWeight.w700,
                          fontSize: 13,
                        ),
                      ),
                      Text(
                        'Autorización en segundos',
                        style: TextStyle(
                          fontFamily: 'Avenir Next',
                          color: AppColors.textMuted,
                          fontWeight: FontWeight.w500,
                          fontSize: 11,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _previewRow(double widthFactor) {
    return Row(
      children: [
        Expanded(
          flex: (widthFactor * 100).round(),
          child: Container(
            height: 9,
            decoration: BoxDecoration(
              color: AppColors.surfaceDark,
              borderRadius: BorderRadius.circular(999),
            ),
          ),
        ),
        Expanded(
          flex: (100 - widthFactor * 100).round(),
          child: const SizedBox(),
        ),
      ],
    );
  }
}
