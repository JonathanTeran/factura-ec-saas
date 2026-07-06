import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/ui_kit.dart';
import '../../data/models/api_exception.dart';
import '../../core/widgets/loading_widget.dart';
import '../../core/widgets/metric_card.dart';
import '../../core/widgets/money_text.dart';
import '../../core/widgets/page_header.dart';
import '../../core/widgets/status_badge.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/report_provider.dart';

class ReportsScreen extends ConsumerWidget {
  const ReportsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final reportAsync = ref.watch(reportsViewDataProvider);

    // Los reportes avanzados son una función del plan Negocio: si el backend
    // responde 403, mostramos un aviso claro con opción de actualizar en vez
    // del error genérico "no se pudo cargar".
    final error = reportAsync.error;
    if (error is ApiException && error.statusCode == 403) {
      return _ReportsLockedView(message: error.message);
    }

    final state = reportAsync.when(
      data: (data) =>
          data.byStatus.isEmpty ? AppDataState.empty : AppDataState.ready,
      loading: () => AppDataState.loading,
      error: (error, _) =>
          isOfflineError(error) ? AppDataState.offline : AppDataState.error,
    );

    if (state == AppDataState.loading) {
      return const _ReportsSkeleton();
    }

    if (state != AppDataState.ready) {
      return ModuleStateView(
        module: 'Reportes',
        state: state,
        onPrimaryAction: () => ref.invalidate(reportsViewDataProvider),
      );
    }

    final data = reportAsync.value!;
    final totalDocs = data.byStatus.values.fold<int>(
      0,
      (sum, count) => sum + count,
    );
    final slices = data.byStatus.entries
        .map(
          (entry) => _ReportSlice(
            statusLabel(entry.key),
            totalDocs > 0 ? (entry.value * 100 / totalDocs) : 0,
            statusColor(entry.key),
            entry.value,
          ),
        )
        .toList(growable: false);

    return SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            PageHeader(
              title: 'Reportes',
              subtitle: 'Ventas, estados y operación en tiempo real',
              trailing: IconButton.filledTonal(
                onPressed: () => ref.invalidate(reportsViewDataProvider),
                icon: const Icon(Icons.insights_rounded),
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _FilterChip(
                    label: 'Año ${DateTime.now().year}',
                    icon: Icons.keyboard_arrow_down,
                  ),
                ),
                const SizedBox(width: 8),
                const Expanded(
                  child: _FilterChip(
                    label: 'Últimos 30 días',
                    icon: Icons.keyboard_arrow_down,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            GridView.count(
              shrinkWrap: true,
              crossAxisCount: 2,
              childAspectRatio: 1.4,
              mainAxisSpacing: 10,
              crossAxisSpacing: 10,
              physics: const NeverScrollableScrollPhysics(),
              children: [
                MetricCard(
                  item: MetricItem(
                    title: 'Facturas mes',
                    value: data.dashboard.documentsThisMonth.toString(),
                    delta: 'Previo ${data.dashboard.documentsLastMonth}',
                    color: AppColors.primary,
                    icon: Icons.description_rounded,
                  ),
                ),
                MetricCard(
                  item: MetricItem(
                    title: 'Ingresos mes',
                    value: currency(data.dashboard.revenueThisMonth),
                    delta:
                        'Previo ${currency(data.dashboard.revenueLastMonth)}',
                    color: AppColors.secondary,
                    icon: Icons.show_chart_rounded,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            GlassPanel(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      const Expanded(
                        child: Text(
                          'Distribución por estado',
                          style: TextStyle(
                            fontFamily: 'Avenir Next',
                            fontWeight: FontWeight.w700,
                            fontSize: 17,
                            color: AppColors.textPrimary,
                          ),
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 10,
                          vertical: 6,
                        ),
                        decoration: BoxDecoration(
                          color: AppColors.primary.withValues(alpha: 0.16),
                          borderRadius: BorderRadius.circular(999),
                        ),
                        child: Text(
                          '$totalDocs docs',
                          style: const TextStyle(
                            fontFamily: 'Avenir Next',
                            fontWeight: FontWeight.w700,
                            color: AppColors.primaryLight,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  SizedBox(
                    height: 250,
                    child: PieChart(
                      PieChartData(
                        centerSpaceRadius: 56,
                        sectionsSpace: 3,
                        startDegreeOffset: -90,
                        sections: [
                          for (final item in slices)
                            PieChartSectionData(
                              color: item.color,
                              value: item.percent,
                              radius: 74,
                              title: '${item.percent.toStringAsFixed(0)}%',
                              titleStyle: const TextStyle(
                                fontFamily: 'Avenir Next',
                                fontWeight: FontWeight.w700,
                                fontSize: 15,
                                color: AppColors.backgroundDark,
                              ),
                            ),
                        ],
                      ),
                    ),
                  ),
                  Wrap(
                    spacing: 10,
                    runSpacing: 8,
                    children: [
                      for (final item in slices)
                        _LegendDot(
                          label: item.label,
                          color: item.color,
                          value:
                              '${item.count} · ${item.percent.toStringAsFixed(0)}%',
                        ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            GlassPanel(
              child: Column(
                children: [
                  for (var i = 0; i < slices.length; i++) ...[
                    _ReportLine(item: slices[i]),
                    if (i < slices.length - 1) ...[
                      const SizedBox(height: 14),
                      const Divider(height: 1),
                      const SizedBox(height: 14),
                    ],
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Private helpers ──

class _ReportSlice {
  final String label;
  final double percent;
  final Color color;
  final int count;

  _ReportSlice(this.label, this.percent, this.color, this.count);
}

class _FilterChip extends StatelessWidget {
  final String label;
  final IconData icon;

  const _FilterChip({required this.label, required this.icon});

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 38,
      padding: const EdgeInsets.symmetric(horizontal: 12),
      decoration: BoxDecoration(
        color: AppColors.surfaceDark,
        border: Border.all(color: AppColors.border),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 18, color: AppColors.textSecondary),
          const SizedBox(width: 8),
          Flexible(
            child: Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                fontFamily: 'Avenir Next',
                color: AppColors.textPrimary,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _LegendDot extends StatelessWidget {
  final String label;
  final String value;
  final Color color;

  const _LegendDot({
    required this.label,
    required this.value,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: AppColors.surfaceDark.withValues(alpha: 0.8),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 10,
            height: 10,
            decoration: BoxDecoration(color: color, shape: BoxShape.circle),
          ),
          const SizedBox(width: 6),
          Text(
            '$label · $value',
            style: const TextStyle(
              fontFamily: 'Avenir Next',
              fontWeight: FontWeight.w700,
              color: AppColors.textPrimary,
              fontSize: 12,
            ),
          ),
        ],
      ),
    );
  }
}

class _ReportLine extends StatelessWidget {
  final _ReportSlice item;

  const _ReportLine({required this.item});

  @override
  Widget build(BuildContext context) {
    final progress = (item.percent / 100).clamp(0.0, 1.0);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                item.label,
                style: const TextStyle(
                  fontFamily: 'Avenir Next',
                  color: AppColors.textPrimary,
                  fontWeight: FontWeight.w700,
                  fontSize: 16,
                ),
              ),
            ),
            Text(
              '${item.count} documentos · ${item.percent.toStringAsFixed(1)}%',
              style: const TextStyle(
                fontFamily: 'Avenir Next',
                color: AppColors.textSecondary,
                fontWeight: FontWeight.w700,
                fontSize: 13,
              ),
            ),
          ],
        ),
        const SizedBox(height: 8),
        ClipRRect(
          borderRadius: BorderRadius.circular(999),
          child: LinearProgressIndicator(
            value: progress,
            minHeight: 10,
            backgroundColor: AppColors.border.withValues(alpha: 0.85),
            valueColor: AlwaysStoppedAnimation<Color>(item.color),
          ),
        ),
      ],
    );
  }
}

/// Aviso cuando los reportes avanzados no están en el plan del usuario (403).
class _ReportsLockedView extends StatelessWidget {
  final String message;

  const _ReportsLockedView({required this.message});

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const PageHeader(
              title: 'Reportes',
              subtitle: 'Ventas, estados y operación',
            ),
            const Spacer(),
            GlassPanel(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 64,
                    height: 64,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: AppColors.primary.withValues(alpha: 0.12),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.workspace_premium_rounded,
                      color: AppColors.primary,
                      size: 30,
                    ),
                  ),
                  const SizedBox(height: 14),
                  const Text(
                    'Reportes avanzados',
                    style: TextStyle(
                      fontFamily: 'Avenir Next',
                      fontWeight: FontWeight.w800,
                      fontSize: 20,
                      color: AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    message,
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontFamily: 'Avenir Next',
                      color: AppColors.textSecondary,
                      height: 1.4,
                    ),
                  ),
                  const SizedBox(height: 18),
                  SizedBox(
                    width: double.infinity,
                    child: FilledButton.icon(
                      onPressed: () => context.push('/settings/billing'),
                      icon: const Icon(Icons.arrow_upward_rounded, size: 18),
                      label: const Text('Actualizar plan'),
                    ),
                  ),
                ],
              ),
            ),
            const Spacer(flex: 2),
          ],
        ),
      ),
    );
  }
}

/// Skeleton de Reportes: encabezado + filtros + métricas + panel de gráfico.
class _ReportsSkeleton extends StatelessWidget {
  const _ReportsSkeleton();

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: SingleChildScrollView(
        physics: const NeverScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: const [
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Skeleton(width: 160, height: 26, radius: 8),
                      SizedBox(height: 10),
                      Skeleton(width: 210, height: 12),
                    ],
                  ),
                ),
                Skeleton.circle(size: 44),
              ],
            ),
            SizedBox(height: 16),
            Row(
              children: [
                Expanded(child: Skeleton(height: 44, radius: 14)),
                SizedBox(width: 8),
                Expanded(child: Skeleton(height: 44, radius: 14)),
              ],
            ),
            SizedBox(height: 12),
            Row(
              children: [
                Expanded(child: Skeleton(height: 110, radius: 18)),
                SizedBox(width: 10),
                Expanded(child: Skeleton(height: 110, radius: 18)),
              ],
            ),
            SizedBox(height: 12),
            Skeleton(height: 340, radius: 20),
          ],
        ),
      ),
    );
  }
}
