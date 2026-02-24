import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

String statusLabel(String status) {
  return switch (status) {
    'authorized' => 'Autorizado',
    'rejected' => 'Rechazado',
    'draft' => 'Borrador',
    'processing' => 'Procesando',
    'sent' => 'Enviado',
    'failed' => 'Error',
    'voided' => 'Anulado',
    _ => status.toUpperCase(),
  };
}

Color statusColor(String status) {
  return switch (status) {
    'authorized' => AppColors.success,
    'rejected' => AppColors.error,
    'draft' => AppColors.info,
    'processing' => AppColors.warning,
    'sent' => AppColors.primary,
    'failed' => AppColors.error,
    'voided' => AppColors.textMuted,
    _ => AppColors.secondary,
  };
}

class StatusBadge extends StatelessWidget {
  final String status;

  const StatusBadge({super.key, required this.status});

  @override
  Widget build(BuildContext context) {
    final label = statusLabel(status);
    final color = statusColor(status);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label.toUpperCase(),
        style: TextStyle(
          fontFamily: 'Avenir Next',
          fontWeight: FontWeight.w800,
          color: color,
          fontSize: 10,
        ),
      ),
    );
  }
}
