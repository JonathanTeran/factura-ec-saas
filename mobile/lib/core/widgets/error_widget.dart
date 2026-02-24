import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import 'aurora_background.dart';
import 'glass_panel.dart';

class AppErrorWidget extends StatelessWidget {
  final String? message;
  final VoidCallback? onRetry;

  const AppErrorWidget({super.key, this.message, this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          const Positioned.fill(child: AuroraBackground()),
          Center(
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: GlassPanel(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(
                      Icons.error_outline_rounded,
                      size: 48,
                      color: AppColors.error,
                    ),
                    const SizedBox(height: 12),
                    const Text(
                      'Algo salió mal',
                      style: TextStyle(
                        fontFamily: 'Avenir Next',
                        fontWeight: FontWeight.w700,
                        fontSize: 20,
                        color: AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      message ?? 'Error desconocido',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontFamily: 'Avenir Next',
                        color: AppColors.textSecondary,
                      ),
                    ),
                    if (onRetry != null) ...[
                      const SizedBox(height: 14),
                      ElevatedButton(
                        onPressed: onRetry,
                        child: const Text('Reintentar'),
                      ),
                    ],
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
