import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';

import '../theme/app_theme.dart';
import 'aurora_background.dart';

class AppShell extends StatelessWidget {
  final String location;
  final Widget child;

  const AppShell({super.key, required this.location, required this.child});

  bool get _isFlowStepRoute {
    if (location.startsWith('/documents/new')) return true;
    return RegExp(r'^/documents/[^/]+$').hasMatch(location);
  }

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
    return Scaffold(
      backgroundColor: Colors.transparent,
      resizeToAvoidBottomInset: false,
      body: Stack(
        children: [
          const Positioned.fill(child: AuroraBackground()),
          Positioned.fill(
            child: Padding(
              padding: EdgeInsets.only(bottom: _isFlowStepRoute ? 102 : 176),
              child: child,
            ),
          ),
          if (!_isFlowStepRoute)
            Positioned(
              left: 20,
              right: 20,
              bottom: 94,
              child: PrimaryFlowCta(onTap: () => context.go('/documents/new')),
            ),
          Align(
            alignment: Alignment.bottomCenter,
            child: BottomDock(
              selectedIndex: _selectedIndexFromLocation(),
              onTap: (index) => _goToIndex(context, index),
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
            color: AppColors.surfaceDark.withValues(alpha: 0.94),
            borderRadius: BorderRadius.circular(28),
            border: Border.all(color: AppColors.border),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.35),
                blurRadius: 24,
                offset: const Offset(0, 10),
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
                      gradient: isSelected
                          ? const LinearGradient(
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                              colors: [Color(0xFF31415F), Color(0xFF253449)],
                            )
                          : null,
                    ),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          icon,
                          color: isSelected
                              ? AppColors.primaryLight
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
