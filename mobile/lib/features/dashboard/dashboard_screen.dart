import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../core/theme/app_theme.dart';
import '../../core/widgets/create_menu.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/loading_widget.dart';
import '../../core/widgets/metric_card.dart';
import '../../core/widgets/money_text.dart';
import '../../core/widgets/page_header.dart';
import '../../core/widgets/section_header.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/dashboard_provider.dart';

AppDataState _stateFromAsyncValue<T>(
  AsyncValue<T> value, {
  required bool Function(T data) isEmpty,
}) {
  return value.when(
    data: (data) => isEmpty(data) ? AppDataState.empty : AppDataState.ready,
    loading: () => AppDataState.loading,
    error: (error, _) =>
        isOfflineError(error) ? AppDataState.offline : AppDataState.error,
  );
}

double _percentChange({required double previous, required double current}) {
  if (previous == 0) return current > 0 ? 100 : 0;
  return ((current - previous) / previous) * 100;
}

String _signedPercent(double value) {
  if (value == 0) return '0%';
  final sign = value > 0 ? '+' : '';
  return '$sign${value.toStringAsFixed(1)}%';
}

String _shortDate(DateTime? date) {
  if (date == null) return '-';
  return DateFormat('dd/MM/yyyy').format(date);
}


class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dataAsync = ref.watch(dashboardViewDataProvider);
    // El inicio siempre se muestra cuando hay datos cargados (aunque estén en
    // cero): así el usuario nuevo ve el home con sus accesos, no un cartel de
    // "todavía no hay nada aquí". Solo cargando/error/sin conexión interrumpen.
    final state = _stateFromAsyncValue(dataAsync, isEmpty: (_) => false);

    if (state != AppDataState.ready) {
      return ModuleStateView(
        module: 'Panel',
        state: state,
        onPrimaryAction: () {
          if (state == AppDataState.empty) {
            context.go('/documents/new');
            return;
          }
          ref.invalidate(dashboardViewDataProvider);
          ref.invalidate(meProvider);
        },
      );
    }

    final data = dataAsync.value!;
    final meAsync = ref.watch(meProvider);
    final trendDelta = _percentChange(
      previous: data.stats.lastMonthCount.toDouble(),
      current: data.stats.currentMonthCount.toDouble(),
    );

    // El monto y los emitidos del mes van en el hero; acá quedan los estados
    // operativos que requieren atención.
    final metrics = <MetricItem>[
      MetricItem(
        title: 'Pendientes',
        value: data.stats.pendingCount.toString(),
        delta: 'En proceso',
        color: AppColors.warning,
        icon: Icons.pending_actions_rounded,
      ),
      MetricItem(
        title: 'Rechazados',
        value: data.stats.rejectedCount.toString(),
        delta: 'Revisar',
        color: AppColors.error,
        icon: Icons.report_problem_rounded,
      ),
    ];

    final spark = data.chartPoints
        .map((p) => p.total.toDouble())
        .toList(growable: false);
    final chartDates = data.chartPoints
        .map((p) => p.date)
        .toList(growable: false);

    final recentDocs = data.recentDocuments
        .map(
          (doc) => _MiniDocItem(
            title: doc.documentTypeLabel.toUpperCase(),
            subtitle: doc.issuer,
            amount: currency(doc.total),
            date: _shortDate(doc.issueDate),
            status: doc.statusLabel.toUpperCase(),
          ),
        )
        .toList(growable: false);

    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: () async {
        // Recarga estilo Twitter: al soltar, refresca los datos del inicio.
        ref.invalidate(dashboardViewDataProvider);
        ref.invalidate(meProvider);
        await ref.read(dashboardViewDataProvider.future);
      },
      child: SafeArea(
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              PageHeader(
                title: 'Hola, ${_firstName(meAsync.valueOrNull?.name)}',
                subtitle: DateFormat('dd MMM yyyy').format(DateTime.now()),
              trailing: DecoratedBox(
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(14),
                  boxShadow: [
                    BoxShadow(
                      color: AppColors.primary.withValues(alpha: 0.35),
                      blurRadius: 12,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: FilledButton.icon(
                  onPressed: () => showCreateMenu(context),
                  icon: const Icon(Icons.add_rounded, size: 20),
                  label: const Text('Crear'),
                  style: FilledButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 12,
                    ),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 16),
            _BillingHeroCard(
              monthTotal: data.stats.currentMonthTotal,
              monthCount: data.stats.currentMonthCount,
              trendDelta: trendDelta,
              onTap: () => context.go('/reports'),
            ),
            const SizedBox(height: 20),
            // Accesos rápidos a lo que antes estaba escondido en el "Menú".
            Row(
              children: [
                Expanded(
                  child: _QuickAction(
                    icon: Icons.people_outline_rounded,
                    label: 'Clientes',
                    onTap: () => context.go('/customers'),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _QuickAction(
                    icon: Icons.storefront_outlined,
                    label: 'Productos',
                    onTap: () => context.go('/products'),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _QuickAction(
                    icon: Icons.pie_chart_outline_rounded,
                    label: 'Reportes',
                    onTap: () => context.go('/reports'),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),
            SectionHeader(
              title: 'Visión rápida',
              actionText: 'Ver más',
              onAction: () => context.go('/documents'),
            ),
            const SizedBox(height: 10),
            GridView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: metrics.length,
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    mainAxisSpacing: 10,
                    crossAxisSpacing: 10,
                    childAspectRatio: 1.12,
                  ),
                  itemBuilder: (context, index) =>
                      MetricCard(item: metrics[index]),
                )
                .animate()
                .fadeIn(duration: 420.ms)
                .slideY(begin: 0.12, duration: 420.ms),
            if (spark.length >= 2) ...[
              const SizedBox(height: 20),
              const SectionHeader(title: 'Evolución', actionText: ''),
              const SizedBox(height: 10),
              _EvolutionChart(values: spark, dates: chartDates),
            ],
            const SizedBox(height: 20),
            SectionHeader(
              title: 'Documentos recientes',
              actionText: 'Todos',
              onAction: () => context.go('/documents'),
            ),
            const SizedBox(height: 10),
            SizedBox(
                  height: 214,
                  child: recentDocs.isEmpty
                      ? GlassPanel(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(
                                Icons.inbox_outlined,
                                color: AppColors.textMuted,
                              ),
                              const SizedBox(height: 8),
                              const Text(
                                'Emití tu primer documento',
                                style: TextStyle(
                                  fontFamily: 'Avenir Next',
                                  color: AppColors.textPrimary,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              const SizedBox(height: 4),
                              const Text(
                                'Acá vas a ver tus facturas y comprobantes.',
                                textAlign: TextAlign.center,
                                style: TextStyle(
                                  fontFamily: 'Avenir Next',
                                  color: AppColors.textSecondary,
                                  fontSize: 13,
                                ),
                              ),
                              const SizedBox(height: 12),
                              FilledButton.icon(
                                onPressed: () => showCreateMenu(context),
                                icon: const Icon(Icons.add_rounded, size: 18),
                                label: const Text('Crear documento'),
                              ),
                            ],
                          ),
                        )
                      : ListView.separated(
                          scrollDirection: Axis.horizontal,
                          itemBuilder: (context, index) =>
                              _MiniDocumentCard(item: recentDocs[index]),
                          separatorBuilder: (_, _) => const SizedBox(width: 10),
                          itemCount: recentDocs.length,
                        ),
                )
                .animate()
                .fadeIn(duration: 460.ms)
                .slideX(begin: 0.06, duration: 460.ms),
            ],
          ),
        ),
      ),
    );
  }
}

// ── Private widgets used only by DashboardScreen ──

String _firstName(String? full) {
  final name = (full ?? '').trim();
  if (name.isEmpty) return 'Bienvenido';
  final first = name.split(RegExp(r'\s+')).first;
  return first[0].toUpperCase() + first.substring(1).toLowerCase();
}

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

/// Tarjeta principal del inicio: lo facturado en el mes, tendencia vs el mes
/// anterior y una mini-curva. El elemento "wow" de la pantalla.
class _BillingHeroCard extends StatelessWidget {
  final double monthTotal;
  final int monthCount;
  final double trendDelta;
  final VoidCallback onTap;

  const _BillingHeroCard({
    required this.monthTotal,
    required this.monthCount,
    required this.trendDelta,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final up = trendDelta >= 0;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(24),
        child: Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(24),
            gradient: const LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [Color(0xFF3B82F6), Color(0xFF1D4ED8)],
            ),
            boxShadow: [
              BoxShadow(
                color: AppColors.primary.withValues(alpha: 0.30),
                blurRadius: 24,
                offset: const Offset(0, 10),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      'Facturado en ${_monthNameEs(DateTime.now().month)}',
                      style: TextStyle(
                        fontFamily: 'Avenir Next',
                        color: Colors.white.withValues(alpha: 0.85),
                        fontWeight: FontWeight.w600,
                        fontSize: 14,
                      ),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 5,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.18),
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: Text(
                      '$monthCount emitidos',
                      style: const TextStyle(
                        fontFamily: 'Avenir Next',
                        color: Colors.white,
                        fontWeight: FontWeight.w700,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                currency(monthTotal),
                style: const TextStyle(
                  fontFamily: 'Avenir Next',
                  color: Colors.white,
                  fontWeight: FontWeight.w800,
                  fontSize: 36,
                  letterSpacing: -1,
                ),
              ),
              const SizedBox(height: 6),
              Row(
                children: [
                  Icon(
                    monthCount == 0
                        ? Icons.bolt_rounded
                        : (up
                              ? Icons.trending_up_rounded
                              : Icons.trending_down_rounded),
                    color: Colors.white,
                    size: 18,
                  ),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      monthCount == 0
                          ? 'Emití tu primera factura del mes'
                          : '${_signedPercent(trendDelta)} vs mes anterior',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontFamily: 'Avenir Next',
                        color: Colors.white.withValues(alpha: 0.9),
                        fontWeight: FontWeight.w600,
                        fontSize: 13,
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    ).animate().fadeIn(duration: 480.ms).slideY(begin: 0.1, duration: 480.ms);
  }
}

class _MiniDocItem {
  final String title;
  final String subtitle;
  final String amount;
  final String date;
  final String status;

  _MiniDocItem({
    required this.title,
    required this.subtitle,
    required this.amount,
    required this.date,
    required this.status,
  });
}

class _MiniDocumentCard extends StatelessWidget {
  final _MiniDocItem item;

  const _MiniDocumentCard({required this.item});

  @override
  Widget build(BuildContext context) {
    final normalized = item.status.toUpperCase();
    final isValid =
        normalized.contains('AUTORIZADO') || normalized.contains('VALIDADO');

    return SizedBox(
      width: 244,
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: AppColors.surface.withValues(alpha: 0.92),
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: AppColors.border),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    item.title,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontFamily: 'Avenir Next',
                      color: AppColors.textMuted,
                      fontWeight: FontWeight.w700,
                      fontSize: 11,
                    ),
                  ),
                ),
                const SizedBox(width: 6),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 9,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: (isValid ? AppColors.success : AppColors.warning)
                        .withValues(alpha: 0.18),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(
                    item.status,
                    style: TextStyle(
                      fontFamily: 'Avenir Next',
                      fontWeight: FontWeight.w800,
                      fontSize: 10,
                      color: isValid ? AppColors.success : AppColors.warning,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              item.subtitle,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                fontFamily: 'Avenir Next',
                fontWeight: FontWeight.w700,
                fontSize: 18,
                letterSpacing: -0.5,
                color: AppColors.textPrimary,
              ),
            ),
            const Spacer(),
            Text(
              item.amount,
              style: const TextStyle(
                fontFamily: 'Avenir Next',
                fontWeight: FontWeight.w800,
                fontSize: 24,
                letterSpacing: -0.8,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              item.date,
              style: const TextStyle(
                fontFamily: 'Avenir Next',
                color: AppColors.textSecondary,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Botón de acceso rápido en el inicio: ícono grande + etiqueta, para llegar en
/// un toque a secciones que antes estaban dentro del "Menú".
class _QuickAction extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  const _QuickAction({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(20),
        child: GlassPanel(
          child: Column(
            children: [
              Icon(icon, color: AppColors.primaryLight, size: 26),
              const SizedBox(height: 8),
              Text(
                label,
                style: const TextStyle(
                  fontFamily: 'Avenir Next',
                  fontWeight: FontWeight.w700,
                  fontSize: 13,
                  color: AppColors.textPrimary,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Gráfico "Evolución": área azul suave dibujada a mano (CustomPaint), con la
/// facturación diaria y etiquetas de fecha. Combina con el hero.
class _EvolutionChart extends StatelessWidget {
  final List<double> values;
  final List<DateTime?> dates;

  const _EvolutionChart({required this.values, required this.dates});

  String _fmt(DateTime? d) => d == null ? '' : DateFormat('dd MMM').format(d);

  @override
  Widget build(BuildContext context) {
    final maxV = values.reduce((a, b) => a > b ? a : b);
    final first = dates.isNotEmpty ? _fmt(dates.first) : '';
    final mid = dates.length > 2 ? _fmt(dates[dates.length ~/ 2]) : '';
    final last = dates.isNotEmpty ? _fmt(dates.last) : '';

    return GlassPanel(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Facturación diaria',
                style: TextStyle(
                  fontFamily: 'Avenir Next',
                  color: AppColors.textSecondary,
                  fontWeight: FontWeight.w600,
                  fontSize: 13,
                ),
              ),
              Text(
                'Máx ${currency(maxV)}',
                style: const TextStyle(
                  fontFamily: 'Avenir Next',
                  color: AppColors.primary,
                  fontWeight: FontWeight.w700,
                  fontSize: 13,
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          SizedBox(
            height: 150,
            width: double.infinity,
            child: CustomPaint(painter: _AreaChartPainter(values)),
          ),
          const SizedBox(height: 8),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              _axisLabel(first),
              _axisLabel(mid),
              _axisLabel(last),
            ],
          ),
        ],
      ),
    ).animate().fadeIn(duration: 460.ms).slideY(begin: 0.1, duration: 460.ms);
  }

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

class _AreaChartPainter extends CustomPainter {
  final List<double> values;

  _AreaChartPainter(this.values);

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

    // Líneas guía horizontales.
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
        final midPoint = Offset((prev.dx + cur.dx) / 2, (prev.dy + cur.dy) / 2);
        p.quadraticBezierTo(prev.dx, prev.dy, midPoint.dx, midPoint.dy);
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

    canvas.drawCircle(pts.last, 4, Paint()..color = AppColors.primary);
    canvas.drawCircle(
      pts.last,
      4,
      Paint()
        ..color = AppColors.primary.withValues(alpha: 0.2)
        ..strokeWidth = 6
        ..style = PaintingStyle.stroke,
    );
  }

  @override
  bool shouldRepaint(covariant _AreaChartPainter old) => old.values != values;
}
