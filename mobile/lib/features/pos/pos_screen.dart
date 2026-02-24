import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/loading_widget.dart';
import '../../core/widgets/page_header.dart';
import '../../core/widgets/section_header.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/pos_provider.dart';

class PosScreen extends ConsumerWidget {
  const PosScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final sessionAsync = ref.watch(posActiveSessionProvider);
    final state = sessionAsync.when(
      data: (_) => AppDataState.ready,
      loading: () => AppDataState.loading,
      error: (error, _) =>
          isOfflineError(error) ? AppDataState.offline : AppDataState.error,
    );

    if (state == AppDataState.loading) {
      return ModuleStateView(
        module: 'Punto de Venta',
        state: AppDataState.loading,
        onPrimaryAction: () => ref.invalidate(posActiveSessionProvider),
      );
    }

    if (state == AppDataState.error || state == AppDataState.offline) {
      return ModuleStateView(
        module: 'Punto de Venta',
        state: state,
        onPrimaryAction: () => ref.invalidate(posActiveSessionProvider),
      );
    }

    final session = sessionAsync.value;

    return SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            PageHeader(
              title: 'Punto de Venta',
              subtitle: session != null && session.isOpen
                  ? 'Sesion activa'
                  : 'Sin sesion activa',
              trailing: IconButton.filledTonal(
                onPressed: () => ref.invalidate(posActiveSessionProvider),
                icon: const Icon(Icons.refresh_rounded),
              ),
            ),
            const SizedBox(height: 16),
            if (session != null && session.isOpen) ...[
              GlassPanel(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: AppColors.success.withValues(alpha: 0.2),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: const Text('ABIERTA',
                            style: TextStyle(color: AppColors.success, fontSize: 12, fontWeight: FontWeight.bold)),
                        ),
                        const Spacer(),
                        Text('Sesion #${session.id}',
                          style: const TextStyle(color: AppColors.textSecondary, fontSize: 13)),
                      ],
                    ),
                    const SizedBox(height: 16),
                    _PosInfoRow(label: 'Monto apertura', value: '\$${session.openingAmount.toStringAsFixed(2)}'),
                    const SizedBox(height: 8),
                    _PosInfoRow(label: 'Transacciones', value: '${session.totalTransactions}'),
                    const SizedBox(height: 8),
                    _PosInfoRow(label: 'Total ventas', value: '\$${session.totalSales.toStringAsFixed(2)}'),
                    const Divider(height: 24),
                    Row(
                      children: [
                        _MiniStat(label: 'Efectivo', value: '\$${session.totalCash.toStringAsFixed(2)}'),
                        const SizedBox(width: 12),
                        _MiniStat(label: 'Tarjeta', value: '\$${session.totalCard.toStringAsFixed(2)}'),
                        const SizedBox(width: 12),
                        _MiniStat(label: 'Transfer.', value: '\$${session.totalTransfer.toStringAsFixed(2)}'),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              const SectionHeader(title: 'Acciones rapidas', actionText: ''),
              const SizedBox(height: 10),
              Row(
                children: [
                  Expanded(
                    child: _ActionCard(
                      icon: Icons.add_shopping_cart_rounded,
                      title: 'Nueva venta',
                      color: AppColors.primary,
                      onTap: () {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Proximamente: Nueva venta POS')),
                        );
                      },
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _ActionCard(
                      icon: Icons.receipt_long_rounded,
                      title: 'Historial',
                      color: AppColors.secondary,
                      onTap: () => context.go('/pos'),
                    ),
                  ),
                ],
              ),
            ] else ...[
              GlassPanel(
                child: Column(
                  children: [
                    const Icon(Icons.point_of_sale_rounded, size: 48, color: AppColors.textSecondary),
                    const SizedBox(height: 12),
                    const Text('No hay sesion activa',
                      style: TextStyle(color: AppColors.textPrimary, fontSize: 16, fontWeight: FontWeight.w600)),
                    const SizedBox(height: 6),
                    const Text('Abre una sesion para comenzar a vender',
                      style: TextStyle(color: AppColors.textSecondary, fontSize: 13)),
                    const SizedBox(height: 16),
                    ElevatedButton.icon(
                      onPressed: () {},
                      icon: const Icon(Icons.play_arrow_rounded),
                      label: const Text('Abrir sesion'),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _PosInfoRow extends StatelessWidget {
  final String label;
  final String value;
  const _PosInfoRow({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(color: AppColors.textSecondary, fontSize: 13)),
        Text(value, style: const TextStyle(color: AppColors.textPrimary, fontSize: 14, fontWeight: FontWeight.w600)),
      ],
    );
  }
}

class _MiniStat extends StatelessWidget {
  final String label;
  final String value;
  const _MiniStat({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(color: AppColors.textSecondary, fontSize: 11)),
          const SizedBox(height: 2),
          Text(value, style: const TextStyle(color: AppColors.textPrimary, fontSize: 14, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}

class _ActionCard extends StatelessWidget {
  final IconData icon;
  final String title;
  final Color color;
  final VoidCallback onTap;

  const _ActionCard({
    required this.icon,
    required this.title,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: color.withValues(alpha: 0.2)),
        ),
        child: Column(
          children: [
            Icon(icon, color: color, size: 32),
            const SizedBox(height: 8),
            Text(title, style: TextStyle(color: color, fontSize: 13, fontWeight: FontWeight.w600)),
          ],
        ),
      ),
    );
  }
}
