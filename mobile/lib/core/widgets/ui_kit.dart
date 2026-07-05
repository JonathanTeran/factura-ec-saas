import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

// ═══════════════════════════════════════════════════════════════════════
//  UI KIT — componentes base reutilizables para una experiencia coherente.
//  Skeletons de carga, empty states, encabezados de sección y entradas
//  animadas. Todos los screens deben apoyarse en estos para verse "de una
//  sola mano" (como lo haría un equipo de diseño maduro).
// ═══════════════════════════════════════════════════════════════════════

/// Bloque con animación de "shimmer" para estados de carga. Da sensación de
/// velocidad y pulcritud muy superior a un spinner.
class Skeleton extends StatefulWidget {
  final double width;
  final double height;
  final double radius;

  const Skeleton({
    super.key,
    this.width = double.infinity,
    this.height = 14,
    this.radius = 8,
  });

  /// Atajo para una línea de texto.
  const Skeleton.line({super.key, this.width = double.infinity})
      : height = 12,
        radius = 6;

  /// Atajo para un círculo (avatar).
  const Skeleton.circle({super.key, double size = 44})
      : width = size,
        height = size,
        radius = size;

  @override
  State<Skeleton> createState() => _SkeletonState();
}

class _SkeletonState extends State<Skeleton>
    with SingleTickerProviderStateMixin {
  late final AnimationController _c = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 1400),
  )..repeat();

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _c,
      builder: (context, _) {
        final t = _c.value;
        return Container(
          width: widget.width,
          height: widget.height,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(widget.radius),
            gradient: LinearGradient(
              begin: Alignment(-1 - 2 * t, 0),
              end: Alignment(1 - 2 * t, 0),
              colors: [
                AppColors.surfaceRaised.withValues(alpha: 0.35),
                AppColors.surfaceRaised.withValues(alpha: 0.70),
                AppColors.surfaceRaised.withValues(alpha: 0.35),
              ],
              stops: const [0.35, 0.5, 0.65],
            ),
          ),
        );
      },
    );
  }
}

/// Estado vacío consistente: ícono en burbuja, título y mensaje cálido, con
/// una acción opcional. Evita las pantallas "muertas".
class EmptyState extends StatelessWidget {
  final IconData icon;
  final String title;
  final String message;
  final String? actionLabel;
  final VoidCallback? onAction;

  const EmptyState({
    super.key,
    required this.icon,
    required this.title,
    required this.message,
    this.actionLabel,
    this.onAction,
  });

  @override
  Widget build(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 72,
              height: 72,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: AppColors.primary.withValues(alpha: 0.10),
                border: Border.all(
                  color: AppColors.primary.withValues(alpha: 0.20),
                ),
              ),
              child: Icon(icon, size: 32, color: AppColors.primary),
            ),
            const SizedBox(height: 18),
            Text(
              title,
              textAlign: TextAlign.center,
              style: textTheme.titleMedium,
            ),
            const SizedBox(height: 6),
            Text(
              message,
              textAlign: TextAlign.center,
              style: textTheme.bodyMedium?.copyWith(color: AppColors.textMuted),
            ),
            if (actionLabel != null && onAction != null) ...[
              const SizedBox(height: 20),
              FilledButton(onPressed: onAction, child: Text(actionLabel!)),
            ],
          ],
        ),
      ),
    );
  }
}

/// Entrada animada (fade + slide) escalonada para elementos de lista. Envolver
/// cada item con un índice creciente para un reveal secuencial y fluido.
class FadeInUp extends StatefulWidget {
  final Widget child;
  final int index;

  const FadeInUp({super.key, required this.child, this.index = 0});

  @override
  State<FadeInUp> createState() => _FadeInUpState();
}

class _FadeInUpState extends State<FadeInUp>
    with SingleTickerProviderStateMixin {
  late final AnimationController _c = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 260),
  );
  late final Animation<double> _a =
      CurvedAnimation(parent: _c, curve: Curves.easeOutCubic);

  @override
  void initState() {
    super.initState();
    // Stagger mínimo y con tope bajo: el contenido aparece rápido, con apenas
    // una insinuación de secuencia (nada de esperas largas).
    final delay = widget.index.clamp(0, 6) * 22;
    if (delay == 0) {
      _c.forward();
    } else {
      Future.delayed(Duration(milliseconds: delay), () {
        if (mounted) _c.forward();
      });
    }
  }

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _a,
      child: widget.child,
      builder: (context, child) {
        return Opacity(
          opacity: _a.value,
          child: Transform.translate(
            offset: Offset(0, (1 - _a.value) * 16),
            child: child,
          ),
        );
      },
    );
  }
}
