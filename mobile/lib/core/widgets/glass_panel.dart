import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

class GlassPanel extends StatelessWidget {
  final Widget child;

  const GlassPanel({super.key, required this.child});

  @override
  Widget build(BuildContext context) {
    // Tarjeta editorial: plana, con borde fino (hairline) y sin sombra difusa.
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.border),
      ),
      child: child,
    );
  }
}
