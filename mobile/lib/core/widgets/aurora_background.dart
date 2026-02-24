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
          colors: [Color(0xFF040814), Color(0xFF060E1A), Color(0xFF0A1322)],
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
    return IgnorePointer(
      child: Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: color,
          boxShadow: [
            BoxShadow(color: color, blurRadius: 90, spreadRadius: 20),
          ],
        ),
      ),
    );
  }
}
