import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../core/theme/app_theme.dart';
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

String _initialsFromName(String name) {
  final parts = name
      .trim()
      .split(RegExp(r'\s+'))
      .where((part) => part.isNotEmpty)
      .toList(growable: false);
  if (parts.isEmpty) return 'EC';
  if (parts.length == 1) {
    final first = parts.first;
    return first.substring(0, first.length > 1 ? 2 : 1).toUpperCase();
  }
  final first = parts[0];
  final second = parts[1];
  return '${first[0]}${second[0]}'.toUpperCase();
}

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dataAsync = ref.watch(dashboardViewDataProvider);
    final state = _stateFromAsyncValue(
      dataAsync,
      isEmpty: (data) =>
          data.recentDocuments.isEmpty && data.stats.currentMonthCount == 0,
    );

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

    final metrics = <MetricItem>[
      MetricItem(
        title: 'Emitidos',
        value: data.stats.currentMonthCount.toString(),
        delta: _signedPercent(trendDelta),
        color: AppColors.primary,
        icon: Icons.north_east_rounded,
      ),
      MetricItem(
        title: 'Monto mes',
        value: currency(data.stats.currentMonthTotal),
        delta: 'Mes actual',
        color: AppColors.secondary,
        icon: Icons.attach_money_rounded,
      ),
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
        delta: 'Control de calidad',
        color: AppColors.error,
        icon: Icons.report_problem_rounded,
      ),
    ];

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

    return SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            PageHeader(
              title: 'Panel inteligente',
              subtitle: DateFormat('dd MMM yyyy').format(DateTime.now()),
              trailing: IconButton.filledTonal(
                tooltip: 'Nuevo documento',
                onPressed: () => context.go('/documents/new'),
                icon: const Icon(Icons.add_rounded),
              ),
            ),
            const SizedBox(height: 16),
            _AccountHeroCard(
              name: meAsync.valueOrNull?.name ?? 'Cuenta activa',
              identifier: meAsync.valueOrNull?.email ?? 'Sin perfil cargado',
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
                            children: const [
                              Icon(
                                Icons.inbox_outlined,
                                color: AppColors.textMuted,
                              ),
                              SizedBox(height: 8),
                              Text(
                                'Sin documentos recientes',
                                style: TextStyle(
                                  fontFamily: 'Avenir Next',
                                  color: AppColors.textSecondary,
                                  fontWeight: FontWeight.w600,
                                ),
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
    );
  }
}

// ── Private widgets used only by DashboardScreen ──

class _AccountHeroCard extends StatelessWidget {
  final String name;
  final String identifier;

  const _AccountHeroCard({required this.name, required this.identifier});

  @override
  Widget build(BuildContext context) {
    final initials = _initialsFromName(name);

    return GlassPanel(
      child: Row(
        children: [
          Container(
            width: 54,
            height: 54,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(14),
              gradient: const LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [Color(0xFF1DDBFF), Color(0xFF2C86FF)],
              ),
            ),
            child: Center(
              child: Text(
                initials,
                style: const TextStyle(
                  fontFamily: 'Avenir Next',
                  fontWeight: FontWeight.w800,
                  color: Colors.white,
                  fontSize: 18,
                ),
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Cuenta activa',
                  style: TextStyle(
                    fontFamily: 'Avenir Next',
                    fontWeight: FontWeight.w600,
                    color: AppColors.textMuted,
                    fontSize: 13,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  name,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    fontWeight: FontWeight.w700,
                    color: AppColors.textPrimary,
                    fontSize: 20,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  identifier,
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    fontWeight: FontWeight.w500,
                    color: AppColors.textSecondary,
                    fontSize: 13,
                  ),
                ),
              ],
            ),
          ),
          IconButton(
            onPressed: () => context.push('/settings'),
            icon: const Icon(Icons.chevron_right_rounded),
          ),
        ],
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
