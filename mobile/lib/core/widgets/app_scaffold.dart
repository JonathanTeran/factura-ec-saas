import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../theme/app_theme.dart';
import 'aurora_background.dart';
import 'create_menu.dart';

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
    '/quotes',
  };

  /// Una pantalla es "raíz" cuando es una de las pestañas/listas principales.
  /// Todo lo demás (formularios `/new`, detalle `/:id`, ajustes internos) es
  /// una sub-página a pantalla completa: sin dock, sin CTA y con teclado que
  /// empuja el contenido.
  bool get _isTabRoot => _tabRoots.contains(location);

  int _selectedIndexFromLocation() {
    if (location.startsWith('/documents') ||
        location.startsWith('/quotes')) {
      return 1;
    }
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

    // Reservamos el espacio de la barra inferior (con el FAB central) + el
    // safe-area del home indicator, para que nada del contenido quede tapado.
    final double safeBottom = MediaQuery.of(context).padding.bottom;
    final double bottomInset = safeBottom + 96;

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
          Align(
            alignment: Alignment.bottomCenter,
            child: WalletNavBar(
              selectedIndex: _selectedIndexFromLocation(),
              onTap: (index) => _goToIndex(context, index),
              onCreate: () => showCreateMenu(context),
            ),
          ),
        ],
      ),
    );
  }
}

/// Barra inferior estilo "wallet": 4 pestañas con un botón "+" central elevado
/// (FAB circular con degradado azul) que abre el menú de creación.
class WalletNavBar extends StatelessWidget {
  final int selectedIndex;
  final ValueChanged<int> onTap;
  final VoidCallback onCreate;

  const WalletNavBar({
    super.key,
    required this.selectedIndex,
    required this.onTap,
    required this.onCreate,
  });

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      top: false,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
        // El FAB sobresale por arriba del borde de la barra, por eso el Stack
        // NO recorta (clipBehavior: none) y la barra deja un hueco central.
        child: SizedBox(
          height: 76,
          child: Stack(
            clipBehavior: Clip.none,
            alignment: Alignment.bottomCenter,
            children: [
              // Barra con las 4 pestañas y un hueco central para el FAB.
              Container(
                height: 66,
                padding: const EdgeInsets.symmetric(horizontal: 6),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(26),
                  border: Border.all(color: AppColors.border),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFF141726).withValues(alpha: 0.08),
                      blurRadius: 24,
                      offset: const Offset(0, 8),
                    ),
                  ],
                ),
                child: Row(
                  children: [
                    _NavTab(
                      icon: Icons.home_rounded,
                      label: 'Inicio',
                      selected: selectedIndex == 0,
                      onTap: () => onTap(0),
                    ),
                    _NavTab(
                      icon: Icons.description_rounded,
                      label: 'Docs',
                      selected: selectedIndex == 1,
                      onTap: () => onTap(1),
                    ),
                    // Hueco central donde se posa el FAB "+".
                    const SizedBox(width: 64),
                    _NavTab(
                      icon: Icons.pie_chart_rounded,
                      label: 'Reportes',
                      selected: selectedIndex == 2,
                      onTap: () => onTap(2),
                    ),
                    _NavTab(
                      icon: Icons.tune_rounded,
                      label: 'Menú',
                      selected: selectedIndex == 3,
                      onTap: () => onTap(3),
                    ),
                  ],
                ),
              ),
              // FAB central elevado.
              Positioned(
                top: 0,
                child: _CreateFab(onTap: onCreate),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _NavTab extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool selected;
  final VoidCallback onTap;

  const _NavTab({
    required this.icon,
    required this.label,
    required this.selected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final color = selected ? AppColors.primary : AppColors.textMuted;
    return Expanded(
      child: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: onTap,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, color: color, size: 23),
            const SizedBox(height: 3),
            Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.fade,
              softWrap: false,
              style: TextStyle(
                fontFamily: 'Avenir Next',
                fontSize: 11,
                fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
                color: selected ? AppColors.textPrimary : AppColors.textMuted,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _CreateFab extends StatelessWidget {
  final VoidCallback onTap;

  const _CreateFab({required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Semantics(
      button: true,
      label: 'Crear comprobante',
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          width: 60,
          height: 60,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            gradient: const LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [AppColors.heroGradientStart, AppColors.heroGradientEnd],
            ),
            border: Border.all(color: Colors.white, width: 4),
            boxShadow: [
              BoxShadow(
                color: AppColors.primary.withValues(alpha: 0.40),
                blurRadius: 18,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: const Icon(Icons.add_rounded, color: Colors.white, size: 30),
        ),
      ),
    );
  }
}
