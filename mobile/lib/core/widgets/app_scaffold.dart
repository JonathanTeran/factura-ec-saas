import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';

import '../theme/app_theme.dart';
import 'aurora_background.dart';

class AppShell extends StatelessWidget {
  final String location;
  final Widget child;

  const AppShell({super.key, required this.location, required this.child});

  /// Rutas raíz (con pestaña) que muestran el dock inferior.
  static const _tabRoots = <String>{
    '/',
    '/documents',
    '/customers',
    '/products',
    '/reports',
    '/settings',
    '/pos',
    '/purchases',
    '/suppliers',
  };

  /// Una pantalla es "raíz" cuando es una de las pestañas/listas principales.
  /// Todo lo demás (formularios `/new`, detalle `/:id`, ajustes internos) es
  /// una sub-página a pantalla completa: sin dock, sin CTA y con teclado que
  /// empuja el contenido.
  bool get _isTabRoot => _tabRoots.contains(location);

  /// El CTA global "Crear Documento" solo tiene sentido en Inicio y Docs.
  bool get _showCreateCta => location == '/' || location == '/documents';

  int _selectedIndexFromLocation() {
    if (location.startsWith('/documents')) return 1;
    if (location.startsWith('/reports')) return 2;
    if (location.startsWith('/settings') ||
        location.startsWith('/customers') ||
        location.startsWith('/products')) {
      return 3;
    }
    return 0;
  }

  void _goToIndex(BuildContext context, int index) {
    switch (index) {
      case 0:
        context.go('/');
        return;
      case 1:
        context.go('/documents');
        return;
      case 2:
        context.go('/reports');
        return;
      case 3:
        context.go('/settings');
        return;
    }
  }

  @override
  Widget build(BuildContext context) {
    // Sub-página (formulario, detalle, ajuste interno): pantalla completa, sin
    // barra inferior ni CTA global. `resizeToAvoidBottomInset: true` hace que el
    // teclado empuje el contenido en lugar de tapar los campos y botones.
    if (!_isTabRoot) {
      return Scaffold(
        backgroundColor: Colors.transparent,
        resizeToAvoidBottomInset: true,
        body: Stack(
          children: [
            const Positioned.fill(child: AuroraBackground()),
            Positioned.fill(child: child),
          ],
        ),
      );
    }

    // El dock consume el safe-area inferior (home indicator). Reservamos ese
    // espacio + la altura del dock + (si aplica) el bloque del CTA, calculado
    // sobre el inset real del dispositivo para que NADA quede detrás del dock.
    final double safeBottom = MediaQuery.of(context).padding.bottom;
    const double dockBlock = 85; // padding + fila de íconos del dock
    const double ctaBlock = 66; // botón (54) + separación
    final double bottomInset =
        safeBottom + dockBlock + (_showCreateCta ? ctaBlock : 0) + 8;

    return Scaffold(
      backgroundColor: Colors.transparent,
      resizeToAvoidBottomInset: false,
      body: Stack(
        children: [
          const Positioned.fill(child: AuroraBackground()),
          Positioned.fill(
            child: Padding(
              padding: EdgeInsets.only(bottom: bottomInset),
              child: child,
            ),
          ),
          // CTA y dock apilados en una sola columna anclada abajo: el CTA queda
          // SIEMPRE por encima del dock, sin solaparse (antes el CTA se metía
          // detrás del menú por un `bottom` fijo menor que la altura del dock).
          Align(
            alignment: Alignment.bottomCenter,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                if (_showCreateCta)
                  Padding(
                    padding: const EdgeInsets.fromLTRB(20, 0, 20, 10),
                    child: PrimaryFlowCta(
                      onTap: () => context.go('/documents/new'),
                    ),
                  ),
                BottomDock(
                  selectedIndex: _selectedIndexFromLocation(),
                  onTap: (index) => _goToIndex(context, index),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class BottomDock extends StatelessWidget {
  final int selectedIndex;
  final ValueChanged<int> onTap;

  const BottomDock({super.key, required this.selectedIndex, required this.onTap});

  @override
  Widget build(BuildContext context) {
    const items = [
      (Icons.home_rounded, 'Inicio'),
      (Icons.description_rounded, 'Docs'),
      (Icons.pie_chart_rounded, 'Reportes'),
      (Icons.tune_rounded, 'Menú'),
    ];

    return SafeArea(
      top: false,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.96),
            borderRadius: BorderRadius.circular(28),
            border: Border.all(color: AppColors.border),
            boxShadow: [
              BoxShadow(
                color: AppColors.primary.withValues(alpha: 0.10),
                blurRadius: 24,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: Row(
            children: List.generate(items.length, (index) {
              final icon = items[index].$1;
              final label = items[index].$2;
              final isSelected = selectedIndex == index;

              return Expanded(
                child: GestureDetector(
                  onTap: () => onTap(index),
                  child: AnimatedContainer(
                    duration: 260.ms,
                    curve: Curves.easeOutCubic,
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(20),
                      color: isSelected
                          ? AppColors.primary.withValues(alpha: 0.12)
                          : null,
                    ),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          icon,
                          color: isSelected
                              ? AppColors.primary
                              : AppColors.textSecondary,
                          size: 23,
                        ),
                        const SizedBox(height: 2),
                        Text(
                          label,
                          maxLines: 1,
                          overflow: TextOverflow.fade,
                          style: TextStyle(
                            fontFamily: 'Avenir Next',
                            fontSize: 11,
                            fontWeight: isSelected
                                ? FontWeight.w700
                                : FontWeight.w500,
                            color: isSelected
                                ? AppColors.textPrimary
                                : AppColors.textMuted,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              );
            }),
          ),
        ),
      ),
    );
  }
}

class PrimaryFlowCta extends StatelessWidget {
  final VoidCallback onTap;

  const PrimaryFlowCta({super.key, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Semantics(
      button: true,
      label: 'Crear documento en un minuto',
      child: ElevatedButton.icon(
        onPressed: onTap,
        icon: const Icon(Icons.bolt_rounded),
        label: const Text('Crear Documento en 1 Minuto'),
        style: ElevatedButton.styleFrom(
          minimumSize: const Size.fromHeight(54),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(18),
          ),
        ),
      ),
    );
  }
}
