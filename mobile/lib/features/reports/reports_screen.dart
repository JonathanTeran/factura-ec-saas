import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/loading_widget.dart';
import '../../core/widgets/money_text.dart';
import '../../core/widgets/page_header.dart';
import '../../core/widgets/status_badge.dart';
import '../../core/widgets/ui_kit.dart';
import '../../data/models/api_exception.dart';
import '../../data/models/dashboard_model.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/report_provider.dart';

class ReportsScreen extends ConsumerWidget {
  const ReportsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final reportAsync = ref.watch(reportsViewDataProvider);

    // Reportes avanzados = función del plan Negocio: si el backend responde 403,
    // mostramos un aviso claro con opción de actualizar.
    final error = reportAsync.error;
    if (error is ApiException && error.statusCode == 403) {
      return _ReportsLockedView(message: error.message);
    }

    final state = reportAsync.when(
      data: (_) => AppDataState.ready,
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

    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: () async {
        ref.invalidate(reportsViewDataProvider);
        await ref.read(reportsViewDataProvider.future);
      },
      child: SafeArea(
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 28),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              PageHeader(
                title: 'Analítica',
                subtitle: 'Tu facturación de un vistazo',
                trailing: IconButton.filledTonal(
                  tooltip: 'Actualizar',
                  onPressed: () => ref.invalidate(reportsViewDataProvider),
                  icon: const Icon(Icons.refresh_rounded),
                ),
              ),
              const SizedBox(height: 16),
              _SpendingCard(data: data)
                  .animate()
                  .fadeIn(duration: 420.ms)
                  .slideY(begin: 0.08, curve: Curves.easeOutCubic),
              const SizedBox(height: 14),
              _EvolutionCard(daily: data.daily)
                  .animate()
                  .fadeIn(duration: 460.ms, delay: 80.ms)
                  .slideY(begin: 0.08, curve: Curves.easeOutCubic),
              const SizedBox(height: 14),
              _ByTypeCard(byType: data.byType)
                  .animate()
                  .fadeIn(duration: 500.ms, delay: 160.ms)
                  .slideY(begin: 0.08, curve: Curves.easeOutCubic),
              const SizedBox(height: 14),
              _ByStatusCard(byStatus: data.byStatus)
                  .animate()
                  .fadeIn(duration: 540.ms, delay: 240.ms)
                  .slideY(begin: 0.08, curve: Curves.easeOutCubic),
            ],
          ),
        ),
      ),
    );
  }
}

// ───────────────────────── helpers ─────────────────────────

double _pctChange({required double previous, required double current}) {
  if (previous == 0) return current > 0 ? 100 : 0;
  return ((current - previous) / previous) * 100;
}

String _signedPct(double value) {
  final sign = value > 0 ? '+' : (value < 0 ? '' : '');
  return '$sign${value.toStringAsFixed(1)}%';
}

const _weekdayLettersEs = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

String _monthNameEs(int month) {
  const months = [
    'enero',
    'febrero',
    'marzo',
    'abril',
    'mayo',
    'junio',
    'julio',
    'agosto',
    'septiembre',
    'octubre',
    'noviembre',
    'diciembre',
  ];
  return months[(month - 1).clamp(0, 11)];
}

/// Paleta estable para el desglose por tipo de comprobante.
const _typePalette = [
  Color(0xFF2B54E4),
  Color(0xFF6D5DF6),
  Color(0xFF12B76A),
  Color(0xFFF7931A),
  Color(0xFF12B5CB),
  Color(0xFFEC4899),
];

// ─────────────────────── Facturación del mes ───────────────────────

/// Tarjeta principal: monto facturado del mes, variación vs el mes anterior y
/// barras de los últimos 7 días (estilo "My Spending").
class _SpendingCard extends StatelessWidget {
  final ReportsViewData data;

  const _SpendingCard({required this.data});

  @override
  Widget build(BuildContext context) {
    final delta = _pctChange(
      previous: data.dashboard.revenueLastMonth,
      current: data.dashboard.revenueThisMonth,
    );
    final up = delta >= 0;

    // Últimos 7 puntos diarios (los más recientes al final).
    final last7 = data.daily.length <= 7
        ? data.daily
        : data.daily.sublist(data.daily.length - 7);
    final maxV = last7.fold<double>(
      0,
      (m, p) => p.total > m ? p.total : m,
    );

    return GlassPanel(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Facturado este mes',
            style: TextStyle(
              fontFamily: 'Avenir Next',
              color: AppColors.textSecondary,
              fontWeight: FontWeight.w600,
              fontSize: 13,
            ),
          ),
          const SizedBox(height: 8),
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      currency(data.dashboard.revenueThisMonth),
                      style: const TextStyle(
                        fontFamily: 'Avenir Next',
                        color: AppColors.textPrimary,
                        fontWeight: FontWeight.w800,
                        fontSize: 32,
                        letterSpacing: -1,
                        fontFeatures: [FontFeature.tabularFigures()],
                      ),
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 3,
                          ),
                          decoration: BoxDecoration(
                            color: (up ? AppColors.success : AppColors.error)
                                .withValues(alpha: 0.14),
                            borderRadius: BorderRadius.circular(999),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(
                                up
                                    ? Icons.arrow_upward_rounded
                                    : Icons.arrow_downward_rounded,
                                size: 13,
                                color:
                                    up ? AppColors.success : AppColors.error,
                              ),
                              const SizedBox(width: 2),
                              Text(
                                _signedPct(delta),
                                style: TextStyle(
                                  fontFamily: 'Avenir Next',
                                  color: up
                                      ? AppColors.success
                                      : AppColors.error,
                                  fontWeight: FontWeight.w800,
                                  fontSize: 12,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 6),
                        const Flexible(
                          child: Text(
                            'vs mes anterior',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontFamily: 'Avenir Next',
                              color: AppColors.textMuted,
                              fontWeight: FontWeight.w600,
                              fontSize: 12,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 12),
              _WeeklyBars(points: last7, maxValue: maxV),
            ],
          ),
        ],
      ),
    );
  }
}

/// Mini gráfico de barras de los últimos 7 días con la inicial del día.
class _WeeklyBars extends StatelessWidget {
  final List<ApiChartPoint> points;
  final double maxValue;

  const _WeeklyBars({required this.points, required this.maxValue});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 148,
      height: 76,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          for (final p in points)
            _Bar(
              factor: maxValue <= 0 ? 0 : (p.total / maxValue),
              letter: p.date == null
                  ? ''
                  : _weekdayLettersEs[(p.date!.weekday - 1).clamp(0, 6)],
              highlight: maxValue > 0 && p.total >= maxValue,
            ),
        ],
      ),
    );
  }
}

class _Bar extends StatelessWidget {
  final double factor;
  final String letter;
  final bool highlight;

  const _Bar({
    required this.factor,
    required this.letter,
    required this.highlight,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisAlignment: MainAxisAlignment.end,
      children: [
        Expanded(
          child: FractionallySizedBox(
            alignment: Alignment.bottomCenter,
            heightFactor: factor.clamp(0.06, 1.0),
            child: Container(
              width: 9,
              decoration: BoxDecoration(
                color: highlight
                    ? AppColors.primary
                    : AppColors.primary.withValues(alpha: 0.28),
                borderRadius: BorderRadius.circular(5),
              ),
            ),
          ),
        ),
        const SizedBox(height: 5),
        Text(
          letter,
          style: const TextStyle(
            fontFamily: 'Avenir Next',
            color: AppColors.textMuted,
            fontWeight: FontWeight.w600,
            fontSize: 10,
          ),
        ),
      ],
    );
  }
}

// ─────────────────────── Evolución (área) ───────────────────────

class _EvolutionCard extends StatelessWidget {
  final List<ApiChartPoint> daily;

  const _EvolutionCard({required this.daily});

  @override
  Widget build(BuildContext context) {
    final values = daily.map((p) => p.total).toList(growable: false);
    final total = values.fold<double>(0, (s, v) => s + v);
    final maxV = values.fold<double>(0, (m, v) => v > m ? v : m);
    final now = DateTime.now();

    return GlassPanel(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Expanded(
                child: Text(
                  'Evolución',
                  style: TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.textPrimary,
                    fontWeight: FontWeight.w800,
                    fontSize: 17,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: AppColors.surfaceDark,
                  borderRadius: BorderRadius.circular(999),
                  border: Border.all(color: AppColors.border),
                ),
                child: Text(
                  '${_monthNameEs(now.month)} ${now.year}',
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.textSecondary,
                    fontWeight: FontWeight.w700,
                    fontSize: 12,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            '${currency(total)}  ·  últimos 30 días',
            style: const TextStyle(
              fontFamily: 'Avenir Next',
              color: AppColors.textSecondary,
              fontWeight: FontWeight.w600,
              fontSize: 13,
            ),
          ),
          const SizedBox(height: 16),
          if (values.length < 2)
            const SizedBox(
              height: 60,
              child: Center(
                child: Text(
                  'Aún no hay suficientes datos para el gráfico.',
                  style: TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.textMuted,
                    fontSize: 13,
                  ),
                ),
              ),
            )
          else ...[
            SizedBox(
              height: 150,
              width: double.infinity,
              child: CustomPaint(painter: _AreaPainter(values)),
            ),
            const SizedBox(height: 8),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                _axisLabel(_fmtDate(daily.first.date)),
                Text(
                  'Máx ${currency(maxV)}',
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.primary,
                    fontWeight: FontWeight.w700,
                    fontSize: 11,
                  ),
                ),
                _axisLabel(_fmtDate(daily.last.date)),
              ],
            ),
          ],
        ],
      ),
    );
  }

  String _fmtDate(DateTime? d) => d == null ? '' : DateFormat('dd MMM').format(d);

  Widget _axisLabel(String text) => Text(
        text,
        style: const TextStyle(
          fontFamily: 'Avenir Next',
          color: AppColors.textMuted,
          fontWeight: FontWeight.w600,
          fontSize: 11,
        ),
      );
}

class _AreaPainter extends CustomPainter {
  final List<double> values;

  _AreaPainter(this.values);

  @override
  void paint(Canvas canvas, Size size) {
    if (values.length < 2) return;
    final maxV = values.reduce((a, b) => a > b ? a : b);
    final minV = values.reduce((a, b) => a < b ? a : b);
    final range = (maxV - minV).abs() < 1e-9 ? 1.0 : (maxV - minV);
    const topPad = 8.0;
    const bottomPad = 4.0;
    final h = size.height - topPad - bottomPad;
    final dx = size.width / (values.length - 1);

    final pts = <Offset>[
      for (var i = 0; i < values.length; i++)
        Offset(dx * i, topPad + h - ((values[i] - minV) / range) * h),
    ];

    final grid = Paint()
      ..color = AppColors.border
      ..strokeWidth = 1;
    for (var g = 0; g <= 3; g++) {
      final y = topPad + h * g / 3;
      canvas.drawLine(Offset(0, y), Offset(size.width, y), grid);
    }

    Path buildPath({required bool close}) {
      final p = Path()..moveTo(pts.first.dx, pts.first.dy);
      for (var i = 1; i < pts.length; i++) {
        final prev = pts[i - 1];
        final cur = pts[i];
        final mid = Offset((prev.dx + cur.dx) / 2, (prev.dy + cur.dy) / 2);
        p.quadraticBezierTo(prev.dx, prev.dy, mid.dx, mid.dy);
      }
      p.lineTo(pts.last.dx, pts.last.dy);
      if (close) {
        p
          ..lineTo(pts.last.dx, size.height)
          ..lineTo(pts.first.dx, size.height)
          ..close();
      }
      return p;
    }

    canvas.drawPath(
      buildPath(close: true),
      Paint()
        ..style = PaintingStyle.fill
        ..shader = LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [
            AppColors.primary.withValues(alpha: 0.28),
            AppColors.primary.withValues(alpha: 0.02),
          ],
        ).createShader(Offset.zero & size),
    );

    canvas.drawPath(
      buildPath(close: false),
      Paint()
        ..color = AppColors.primary
        ..strokeWidth = 2.6
        ..style = PaintingStyle.stroke
        ..strokeCap = StrokeCap.round
        ..strokeJoin = StrokeJoin.round,
    );

    // Marca en el pico (valor máximo).
    var peak = pts.first;
    for (final p in pts) {
      if (p.dy < peak.dy) peak = p;
    }
    canvas.drawCircle(peak, 4, Paint()..color = AppColors.primary);
    canvas.drawCircle(
      peak,
      4,
      Paint()
        ..color = AppColors.primary.withValues(alpha: 0.2)
        ..strokeWidth = 6
        ..style = PaintingStyle.stroke,
    );
  }

  @override
  bool shouldRepaint(covariant _AreaPainter old) => old.values != values;
}

// ─────────────────────── Por tipo de comprobante ───────────────────────

class _ByTypeCard extends StatelessWidget {
  final List<ApiTypeSummary> byType;

  const _ByTypeCard({required this.byType});

  @override
  Widget build(BuildContext context) {
    final items = [...byType]..sort((a, b) => b.total.compareTo(a.total));
    final grand = items.fold<double>(0, (s, e) => s + e.total);

    return GlassPanel(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Por tipo de comprobante',
            style: TextStyle(
              fontFamily: 'Avenir Next',
              color: AppColors.textPrimary,
              fontWeight: FontWeight.w800,
              fontSize: 17,
            ),
          ),
          const SizedBox(height: 4),
          const Text(
            'Últimos 30 días',
            style: TextStyle(
              fontFamily: 'Avenir Next',
              color: AppColors.textMuted,
              fontWeight: FontWeight.w600,
              fontSize: 12,
            ),
          ),
          const SizedBox(height: 14),
          if (items.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 10),
              child: Text(
                'Sin comprobantes en el período.',
                style: TextStyle(
                  fontFamily: 'Avenir Next',
                  color: AppColors.textSecondary,
                ),
              ),
            )
          else
            for (var i = 0; i < items.length; i++) ...[
              _TypeRow(
                item: items[i],
                color: _typePalette[i % _typePalette.length],
                share: grand <= 0 ? 0 : items[i].total / grand,
              ),
              if (i < items.length - 1) const SizedBox(height: 14),
            ],
        ],
      ),
    );
  }
}

class _TypeRow extends StatelessWidget {
  final ApiTypeSummary item;
  final Color color;
  final double share;

  const _TypeRow({
    required this.item,
    required this.color,
    required this.share,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Row(
          children: [
            Container(
              width: 10,
              height: 10,
              decoration: BoxDecoration(color: color, shape: BoxShape.circle),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                item.label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  fontFamily: 'Avenir Next',
                  color: AppColors.textPrimary,
                  fontWeight: FontWeight.w700,
                  fontSize: 15,
                ),
              ),
            ),
            const SizedBox(width: 8),
            Text(
              currency(item.total),
              style: const TextStyle(
                fontFamily: 'Avenir Next',
                color: AppColors.textPrimary,
                fontWeight: FontWeight.w800,
                fontSize: 15,
                fontFeatures: [FontFeature.tabularFigures()],
              ),
            ),
          ],
        ),
        const SizedBox(height: 8),
        Row(
          children: [
            Expanded(
              child: ClipRRect(
                borderRadius: BorderRadius.circular(999),
                child: LinearProgressIndicator(
                  value: share.clamp(0.0, 1.0),
                  minHeight: 7,
                  backgroundColor: AppColors.surfaceDark,
                  valueColor: AlwaysStoppedAnimation<Color>(color),
                ),
              ),
            ),
            const SizedBox(width: 10),
            Text(
              '${item.count} ${item.count == 1 ? 'doc' : 'docs'}',
              style: const TextStyle(
                fontFamily: 'Avenir Next',
                color: AppColors.textMuted,
                fontWeight: FontWeight.w600,
                fontSize: 12,
              ),
            ),
          ],
        ),
      ],
    );
  }
}

// ─────────────────────── Por estado ───────────────────────

class _ByStatusCard extends StatelessWidget {
  final Map<String, int> byStatus;

  const _ByStatusCard({required this.byStatus});

  @override
  Widget build(BuildContext context) {
    final entries = byStatus.entries.where((e) => e.value > 0).toList()
      ..sort((a, b) => b.value.compareTo(a.value));
    final total = entries.fold<int>(0, (s, e) => s + e.value);

    if (entries.isEmpty) return const SizedBox.shrink();

    return GlassPanel(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Expanded(
                child: Text(
                  'Por estado',
                  style: TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.textPrimary,
                    fontWeight: FontWeight.w800,
                    fontSize: 17,
                  ),
                ),
              ),
              Text(
                '$total ${total == 1 ? 'doc' : 'docs'}',
                style: const TextStyle(
                  fontFamily: 'Avenir Next',
                  color: AppColors.textMuted,
                  fontWeight: FontWeight.w700,
                  fontSize: 13,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              for (final e in entries)
                _StatusChip(
                  label: statusLabel(e.key),
                  color: statusColor(e.key),
                  count: e.value,
                ),
            ],
          ),
        ],
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  final String label;
  final Color color;
  final int count;

  const _StatusChip({
    required this.label,
    required this.color,
    required this.count,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.25)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 9,
            height: 9,
            decoration: BoxDecoration(color: color, shape: BoxShape.circle),
          ),
          const SizedBox(width: 8),
          Text(
            label,
            style: const TextStyle(
              fontFamily: 'Avenir Next',
              color: AppColors.textPrimary,
              fontWeight: FontWeight.w700,
              fontSize: 13,
            ),
          ),
          const SizedBox(width: 6),
          Text(
            '$count',
            style: TextStyle(
              fontFamily: 'Avenir Next',
              color: color,
              fontWeight: FontWeight.w800,
              fontSize: 13,
            ),
          ),
        ],
      ),
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
              title: 'Analítica',
              subtitle: 'Tu facturación de un vistazo',
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

/// Skeleton de Analítica: encabezado + tarjeta principal + gráfico + listas.
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
                      Skeleton(width: 150, height: 26, radius: 8),
                      SizedBox(height: 10),
                      Skeleton(width: 200, height: 12),
                    ],
                  ),
                ),
                Skeleton.circle(size: 44),
              ],
            ),
            SizedBox(height: 18),
            Skeleton(height: 132, radius: 20),
            SizedBox(height: 14),
            Skeleton(height: 230, radius: 20),
            SizedBox(height: 14),
            Skeleton(height: 200, radius: 20),
          ],
        ),
      ),
    );
  }
}
