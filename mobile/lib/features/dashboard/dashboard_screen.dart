import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../core/theme/app_theme.dart';
import '../../core/widgets/create_menu.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/loading_widget.dart';
import '../../core/widgets/ui_kit.dart';
import '../../core/widgets/metric_card.dart';
import '../../core/widgets/money_text.dart';
import '../../core/widgets/page_header.dart';
import '../../core/widgets/section_header.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/company_provider.dart';
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

    // Skeleton a medida del inicio: la silueta se parece al contenido real
    // (hero, accesos, métricas, gráfico), no filas genéricas.
    if (state == AppDataState.loading) {
      return const _DashboardSkeleton();
    }

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
    // Empresa activa en ambiente de Pruebas → cinta de aviso en el inicio.
    final companies = ref.watch(companiesProvider).valueOrNull ?? const [];
    final currentCompanyId = meAsync.valueOrNull?.currentCompanyId;
    final activeCompany = companies.isEmpty
        ? null
        : companies.firstWhere(
            (company) => company.id == currentCompanyId,
            orElse: () => companies.first,
          );
    final isTestMode = activeCompany != null && !activeCompany.isProduction;
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
            id: doc.id,
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
              trailing: FilledButton.icon(
                onPressed: () => showCreateMenu(context),
                icon: const Icon(Icons.add_rounded, size: 20),
                label: const Text('Crear'),
                style: FilledButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  foregroundColor: Colors.white,
                  elevation: 0,
                  padding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 12,
                  ),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
              ),
            ),
            if (isTestMode) ...[
              const SizedBox(height: 14),
              _TestModeRibbon(onTap: () => context.go('/settings')),
            ],
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
            // Chips compactos lado a lado (antes: tarjetas casi cuadradas con
            // mucho vacío vertical).
            Row(
                  children: [
                    for (var i = 0; i < metrics.length; i++) ...[
                      if (i > 0) const SizedBox(width: 10),
                      Expanded(child: MetricCard(item: metrics[i])),
                    ],
                  ],
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
            // Lista vertical compacta (antes: carrusel horizontal de tarjetas
            // grandes donde solo se veía una y media a la vez).
            (recentDocs.isEmpty
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
                    : GlassPanel(
                        child: Column(
                          children: [
                            for (var i = 0;
                                i < recentDocs.length && i < 5;
                                i++) ...[
                              if (i > 0) const Divider(height: 16),
                              _MiniDocumentRow(item: recentDocs[i]),
                            ],
                          ],
                        ),
                      ) as Widget)
                .animate()
                .fadeIn(duration: 460.ms)
                .slideY(begin: 0.06, duration: 460.ms),
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

/// Cinta delgada de aviso: la empresa activa emite en ambiente de PRUEBAS, así
/// que los comprobantes no tienen validez tributaria. Toca para ir a Ajustes.
class _TestModeRibbon extends StatelessWidget {
  final VoidCallback onTap;

  const _TestModeRibbon({required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
          decoration: BoxDecoration(
            color: AppColors.warning.withValues(alpha: 0.15),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: AppColors.warning.withValues(alpha: 0.6),
              width: 1.4,
            ),
          ),
          child: Row(
            children: [
              const Icon(
                Icons.science_rounded,
                color: AppColors.warning,
                size: 18,
              ),
              const SizedBox(width: 8),
              const Text(
                'AMBIENTE DE PRUEBAS',
                style: TextStyle(
                  fontFamily: 'Avenir Next',
                  color: AppColors.warning,
                  fontWeight: FontWeight.w800,
                  fontSize: 12,
                  letterSpacing: 0.4,
                ),
              ),
              const SizedBox(width: 8),
              const Expanded(
                child: Text(
                  'sin validez tributaria',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.textSecondary,
                    fontWeight: FontWeight.w600,
                    fontSize: 12,
                  ),
                ),
              ),
              Icon(
                Icons.chevron_right_rounded,
                color: AppColors.warning.withValues(alpha: 0.8),
                size: 20,
              ),
            ],
          ),
        ),
      ),
    );
  }
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
        borderRadius: BorderRadius.circular(22),
        child: Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(22),
            gradient: const LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [AppColors.heroGradientStart, AppColors.heroGradientEnd],
            ),
            boxShadow: [
              BoxShadow(
                color: AppColors.primary.withValues(alpha: 0.28),
                blurRadius: 22,
                offset: const Offset(0, 12),
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
                        color: Colors.white.withValues(alpha: 0.88),
                        fontWeight: FontWeight.w600,
                        fontSize: 14,
                      ),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 11,
                      vertical: 5,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.20),
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
              const SizedBox(height: 14),
              Text(
                currency(monthTotal),
                style: const TextStyle(
                  fontFamily: 'Avenir Next',
                  color: Colors.white,
                  fontWeight: FontWeight.w800,
                  fontSize: 40,
                  letterSpacing: -1.2,
                  fontFeatures: [FontFeature.tabularFigures()],
                ),
              ),
              const SizedBox(height: 10),
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
                        color: Colors.white.withValues(alpha: 0.92),
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
    ).animate().fadeIn(duration: 420.ms).slideY(begin: 0.06, duration: 420.ms);
  }
}

class _MiniDocItem {
  final int id;
  final String title;
  final String subtitle;
  final String amount;
  final String date;
  final String status;

  _MiniDocItem({
    required this.id,
    required this.title,
    required this.subtitle,
    required this.amount,
    required this.date,
    required this.status,
  });
}

/// Fila compacta de documento reciente: ícono con color de estado, cliente,
/// tipo · fecha, y monto + chip de estado a la derecha.
class _MiniDocumentRow extends StatelessWidget {
  final _MiniDocItem item;

  const _MiniDocumentRow({required this.item});

  @override
  Widget build(BuildContext context) {
    final normalized = item.status.toUpperCase();
    final isValid =
        normalized.contains('AUTORIZADO') || normalized.contains('VALIDADO');
    final statusColor = isValid ? AppColors.success : AppColors.warning;

    return InkWell(
      borderRadius: BorderRadius.circular(12),
      onTap: () => context.push('/documents/${item.id}'),
      child: Row(
        children: [
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              color: statusColor.withValues(alpha: 0.14),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(
              Icons.receipt_long_rounded,
              size: 19,
              color: statusColor,
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.subtitle,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    fontWeight: FontWeight.w700,
                    fontSize: 14,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 1),
                Text(
                  '${item.title} · ${item.date}',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.textMuted,
                    fontWeight: FontWeight.w600,
                    fontSize: 11.5,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                item.amount,
                style: const TextStyle(
                  fontFamily: 'Avenir Next',
                  fontWeight: FontWeight.w800,
                  fontSize: 15,
                  letterSpacing: -0.3,
                  color: AppColors.textPrimary,
                ),
              ),
              const SizedBox(height: 2),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 7,
                  vertical: 2,
                ),
                decoration: BoxDecoration(
                  color: statusColor.withValues(alpha: 0.16),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  item.status,
                  style: TextStyle(
                    fontFamily: 'Avenir Next',
                    fontWeight: FontWeight.w800,
                    fontSize: 9,
                    color: statusColor,
                  ),
                ),
              ),
            ],
          ),
        ],
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

/// Skeleton del inicio: imita la silueta real (encabezado + hero + accesos +
/// métricas + gráfico + recientes) para una transición fluida y premium.
class _DashboardSkeleton extends StatelessWidget {
  const _DashboardSkeleton();

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: SingleChildScrollView(
        physics: const NeverScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: const [
            // Encabezado: saludo + botón Crear.
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Skeleton(width: 210, height: 28, radius: 8),
                      SizedBox(height: 10),
                      Skeleton(width: 120, height: 12),
                    ],
                  ),
                ),
                Skeleton(width: 104, height: 46, radius: 14),
              ],
            ),
            SizedBox(height: 20),
            // Hero.
            Skeleton(height: 150, radius: 24),
            SizedBox(height: 20),
            // Accesos rápidos.
            Row(
              children: [
                Expanded(child: Skeleton(height: 84, radius: 20)),
                SizedBox(width: 10),
                Expanded(child: Skeleton(height: 84, radius: 20)),
                SizedBox(width: 10),
                Expanded(child: Skeleton(height: 84, radius: 20)),
              ],
            ),
            SizedBox(height: 22),
            // Métricas.
            Skeleton(width: 140, height: 16),
            SizedBox(height: 12),
            Row(
              children: [
                Expanded(child: Skeleton(height: 96, radius: 18)),
                SizedBox(width: 10),
                Expanded(child: Skeleton(height: 96, radius: 18)),
              ],
            ),
            SizedBox(height: 22),
            // Gráfico.
            Skeleton(width: 120, height: 16),
            SizedBox(height: 12),
            Skeleton(height: 200, radius: 20),
            SizedBox(height: 22),
            // Recientes.
            Skeleton(width: 170, height: 16),
            SizedBox(height: 12),
            Row(
              children: [
                Skeleton(width: 244, height: 214, radius: 18),
                SizedBox(width: 10),
                Skeleton(width: 120, height: 214, radius: 18),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
