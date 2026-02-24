import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_panel.dart';
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
    final state = reportAsync.when(
      data: (data) =>
          data.byStatus.isEmpty ? AppDataState.empty : AppDataState.ready,
      loading: () => AppDataState.loading,
      error: (error, _) =>
          isOfflineError(error) ? AppDataState.offline : AppDataState.error,
    );

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
