import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import 'glass_panel.dart';

enum AppDataState { ready, loading, empty, error, offline }

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
    final text = switch (state) {
      AppDataState.loading => (
        Icons.sync_rounded,
        'Sincronizando $module',
        'Estamos actualizando datos para mostrar el panel más reciente.',
        'Actualizar ahora',
      ),
      AppDataState.empty => (
        Icons.inbox_rounded,
        'No hay datos en $module',
        'Crea tu primer documento para comenzar a operar.',
        'Crear primer documento',
      ),
      AppDataState.error => (
        Icons.error_outline_rounded,
        'Error al cargar $module',
        'No pudimos completar la sincronización en este momento.',
        'Reintentar sincronización',
      ),
      AppDataState.offline => (
        Icons.wifi_off_rounded,
        'Sin conexión',
        'No hay internet. Puedes continuar con los datos guardados.',
        'Usar datos en caché',
      ),
      AppDataState.ready => (
        Icons.check_circle_rounded,
        '$module listo',
        'La información está actualizada.',
        'Continuar',
      ),
    };

    return SafeArea(
      child: Center(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
          child: GlassPanel(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(text.$1, size: 52, color: AppColors.primaryLight),
                const SizedBox(height: 10),
                Text(
                  text.$2,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    fontWeight: FontWeight.w700,
                    fontSize: 22,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  text.$3,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.textSecondary,
                  ),
                ),
                const SizedBox(height: 14),
                if (state == AppDataState.loading)
                  const Padding(
                    padding: EdgeInsets.symmetric(horizontal: 20),
                    child: LinearProgressIndicator(minHeight: 6),
                  ),
                if (state != AppDataState.loading)
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: onPrimaryAction,
                      child: Text(text.$4),
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
