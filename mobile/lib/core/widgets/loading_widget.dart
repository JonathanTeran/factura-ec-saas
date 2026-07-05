import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import 'ui_kit.dart';

enum AppDataState { ready, loading, empty, error, offline }

/// Vista de estado compartida por los módulos (clientes, productos, compras,
/// reportes, POS…). Carga → skeletons con shimmer (percepción de velocidad);
/// vacío/error/offline → panel centrado, cálido y consistente.
class ModuleStateView extends StatelessWidget {
  final String module;
  final AppDataState state;
  final VoidCallback onPrimaryAction;

  const ModuleStateView({
    super.key,
    required this.module,
    required this.state,
    required this.onPrimaryAction,
  });

  @override
  Widget build(BuildContext context) {
    if (state == AppDataState.loading) {
      return const _ModuleSkeleton();
    }

    final (IconData icon, String title, String message, String action) =
        switch (state) {
      AppDataState.empty => (
          Icons.inbox_rounded,
          'Todavía no hay nada aquí',
          'Cuando agregues información en $module, la verás en esta pantalla.',
          'Agregar',
        ),
      AppDataState.error => (
          Icons.cloud_off_rounded,
          'No se pudo cargar',
          'Tuvimos un problema al sincronizar $module. Volvé a intentarlo.',
          'Reintentar',
        ),
      AppDataState.offline => (
          Icons.wifi_off_rounded,
          'Sin conexión',
          'No hay internet. Podés seguir con los datos guardados.',
          'Usar datos en caché',
        ),
      AppDataState.ready => (
          Icons.check_circle_rounded,
          '$module listo',
          'La información está actualizada.',
          'Continuar',
        ),
      AppDataState.loading => (Icons.sync_rounded, '', '', ''),
    };

    final textTheme = Theme.of(context).textTheme;
    return SafeArea(
      child: Center(
        child: FadeInUp(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(32, 16, 32, 24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 76,
                  height: 76,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: AppColors.primary.withValues(alpha: 0.10),
                    border: Border.all(
                      color: AppColors.primary.withValues(alpha: 0.20),
                    ),
                  ),
                  child: Icon(icon, size: 34, color: AppColors.primary),
                ),
                const SizedBox(height: 20),
                Text(title, textAlign: TextAlign.center, style: textTheme.titleLarge),
                const SizedBox(height: 8),
                Text(
                  message,
                  textAlign: TextAlign.center,
                  style: textTheme.bodyMedium?.copyWith(color: AppColors.textMuted),
                ),
                const SizedBox(height: 22),
                FilledButton(onPressed: onPrimaryAction, child: Text(action)),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

/// Placeholder de carga con shimmer que imita filas de contenido.
class _ModuleSkeleton extends StatelessWidget {
  const _ModuleSkeleton();

  @override
  Widget build(BuildContext context) {
    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 20),
      itemCount: 8,
      separatorBuilder: (_, _) => const SizedBox(height: 12),
      itemBuilder: (context, index) => Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: AppColors.surface.withValues(alpha: 0.55),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppColors.border),
        ),
        child: Row(
          children: [
            const Skeleton.circle(size: 42),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: const [
                  Skeleton(width: 160, height: 13),
                  SizedBox(height: 9),
                  Skeleton(width: 100, height: 11),
                ],
              ),
            ),
            const SizedBox(width: 12),
            const Skeleton(width: 48, height: 20, radius: 10),
          ],
        ),
      ),
    );
  }
}
