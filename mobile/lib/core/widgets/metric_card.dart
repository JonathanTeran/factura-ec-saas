import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

class MetricItem {
  final String title;
  final String value;
  final String delta;
  final Color color;
  final IconData icon;

  const MetricItem({
    required this.title,
    required this.value,
    required this.delta,
    required this.color,
    required this.icon,
  });
}

/// Tarjeta de métrica COMPACTA (ícono + valor + etiqueta en una fila).
/// Antes era casi cuadrada con un Spacer que dejaba mucho vacío vertical.
class MetricCard extends StatelessWidget {
  final MetricItem item;

  const MetricCard({super.key, required this.item});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: AppColors.surface.withValues(alpha: 0.9),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        children: [
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              color: item.color.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(item.icon, size: 20, color: item.color),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.value,
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    fontSize: 20,
                    height: 1.1,
                    fontWeight: FontWeight.w800,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 1),
                Text(
                  item.title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontFamily: 'Avenir Next',
                    fontWeight: FontWeight.w600,
                    fontSize: 12,
                    color: item.color,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
