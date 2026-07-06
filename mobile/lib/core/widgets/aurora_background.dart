import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

class AuroraBackground extends StatelessWidget {
  const AuroraBackground({super.key});

  @override
  Widget build(BuildContext context) {
    // Editorial: fondo papel plano y sobrio. Sin orbes ni degradados llamativos
    // (eso es lo que daba el aire "de IA").
    return const ColoredBox(color: AppColors.background);
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
