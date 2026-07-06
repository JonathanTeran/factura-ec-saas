import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

class AuroraBackground extends StatelessWidget {
  const AuroraBackground({super.key});

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [Color(0xFFFFFFFF), Color(0xFFF4F7FB), Color(0xFFEAF0FA)],
        ),
      ),
      child: Stack(
        children: [
          Positioned(
            top: -120,
            right: -80,
            child: GlowOrb(
              size: 260,
              color: AppColors.primary.withValues(alpha: 0.17),
            ),
          ),
          Positioned(
            top: 180,
            left: -120,
            child: GlowOrb(
              size: 280,
              color: AppColors.secondary.withValues(alpha: 0.15),
            ),
          ),
          Positioned(
            bottom: -140,
            right: -70,
            child: GlowOrb(
              size: 260,
              color: AppColors.info.withValues(alpha: 0.16),
            ),
          ),
        ],
      ),
    );
  }
}

class GlowOrb extends StatelessWidget {
  final double size;
  final Color color;

  const GlowOrb({super.key, required this.size, required this.color});

  @override
  Widget build(BuildContext context) {
    // RepaintBoundary: el orbe es estático; evita que el scroll del contenido
    // lo repinte. Blur reducido (era 90px, muy caro para la GPU).
    return RepaintBoundary(
      child: IgnorePointer(
        child: Container(
          width: size,
          height: size,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: color,
            boxShadow: [
              BoxShadow(color: color, blurRadius: 48, spreadRadius: 8),
            ],
          ),
        ),
      ),
    );
  }
}
