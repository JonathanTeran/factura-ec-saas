import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../core/api/v1_api_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/money_text.dart';
import '../../core/widgets/page_header.dart';
import '../../core/widgets/search_bar_widget.dart';
import '../../data/providers/document_provider.dart';

enum DocumentStatus { validated, pending, rejected, draft }

class _DocumentItem {
  final String type;
  final String issuer;
  final String number;
  final double amount;
  final String date;
  final DocumentStatus status;

  _DocumentItem({
    required this.type,
    required this.issuer,
    required this.number,
    required this.amount,
    required this.date,
    required this.status,
  });
}

DocumentStatus _documentStatusFromApi(String status) {
  return switch (status) {
    'authorized' => DocumentStatus.validated,
    'rejected' => DocumentStatus.rejected,
    'draft' => DocumentStatus.draft,
    _ => DocumentStatus.pending,
  };
}

String _shortDate(DateTime? date) {
  if (date == null) return '-';
  return DateFormat('dd/MM/yyyy').format(date);
}

_DocumentItem _documentItemFromApi(ApiDocument document) {
  return _DocumentItem(
    type: document.documentTypeLabel.toUpperCase(),
    issuer: document.issuer,
    number: document.documentNumber,
    amount: document.total,
    date: _shortDate(document.issueDate),
    status: _documentStatusFromApi(document.status),
  );
}

class DocumentsScreen extends ConsumerWidget {
  const DocumentsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return DefaultTabController(
      length: 3,
      child: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
              child: PageHeader(
                title: 'Documentos',
                subtitle: 'Busca, filtra y valida en segundos',
                trailing: IconButton.filledTonal(
                  tooltip: 'Sincronizar documentos',
                  onPressed: () {
                    ref.invalidate(sentDocumentsProvider);
                    ref.invalidate(receivedDocumentsProvider);
                    ref.invalidate(draftDocumentsProvider);
                  },
                  icon: const Icon(Icons.sync_rounded),
                ),
              ),
            ),
            const SizedBox(height: 12),
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 20),
              child: SearchInput(),
            ),
            const SizedBox(height: 10),
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: Row(
                children: const [
                  _FilterChip(label: 'Últimos 30 días', icon: Icons.date_range),
                  SizedBox(width: 8),
                  _FilterChip(label: 'Todos los tipos', icon: Icons.tune),
                  SizedBox(width: 8),
                  _FilterChip(label: 'Monto > \$25', icon: Icons.attach_money),
                ],
              ),
            ),
            const SizedBox(height: 10),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: Container(
                height: 44,
                padding: const EdgeInsets.all(4),
                decoration: BoxDecoration(
                  color: AppColors.surfaceDark,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: AppColors.border),
                ),
                child: TabBar(
                  dividerColor: Colors.transparent,
                  indicatorSize: TabBarIndicatorSize.tab,
                  indicator: BoxDecoration(
                    color: AppColors.primary.withValues(alpha: 0.16),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  labelColor: AppColors.textPrimary,
                  unselectedLabelColor: AppColors.textMuted,
                  labelStyle: const TextStyle(
                    fontFamily: 'Avenir Next',
                    fontWeight: FontWeight.w700,
                  ),
                  tabs: const [
                    Tab(text: 'Enviados'),
                    Tab(text: 'Recibidos'),
                    Tab(text: 'Borradores'),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 12),
            Expanded(
              child: TabBarView(
                children: [
                  _DocumentsTab(
                    provider: sentDocumentsProvider,
                    emptyMessage: 'No tienes documentos emitidos.',
                  ),
                  _DocumentsTab(
                    provider: receivedDocumentsProvider,
                    emptyMessage:
                        'No hay documentos autorizados para este período.',
                  ),
                  _DocumentsTab(
                    provider: draftDocumentsProvider,
                    emptyMessage: 'No hay borradores activos.',
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _DocumentsTab extends ConsumerWidget {
  final FutureProvider<PaginatedResult<ApiDocument>> provider;
  final String emptyMessage;

  const _DocumentsTab({required this.provider, required this.emptyMessage});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final asyncValue = ref.watch(provider);

    return asyncValue.when(
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (error, _) => Center(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: GlassPanel(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  error.toString(),
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.textSecondary,
                  ),
                ),
                const SizedBox(height: 10),
                ElevatedButton(
                  onPressed: () => ref.invalidate(provider),
                  child: const Text('Reintentar'),
                ),
              ],
            ),
          ),
        ),
      ),
      data: (result) {
        if (result.items.isEmpty) {
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: GlassPanel(
                child: Text(
                  emptyMessage,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.textSecondary,
                  ),
                ),
              ),
            ),
          );
        }

        final items = result.items
            .map(_documentItemFromApi)
            .toList(growable: false);
        return _DocumentsList(items: items);
      },
    );
  }
}

class _DocumentsList extends StatelessWidget {
  final List<_DocumentItem> items;

  const _DocumentsList({required this.items});

  @override
  Widget build(BuildContext context) {
    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(20, 2, 20, 14),
      itemBuilder: (context, index) => _DocumentTile(item: items[index]),
      separatorBuilder: (_, _) => const SizedBox(height: 10),
      itemCount: items.length,
    );
  }
}

class _DocumentTile extends StatelessWidget {
  final _DocumentItem item;

  const _DocumentTile({required this.item});

  @override
  Widget build(BuildContext context) {
    final status = switch (item.status) {
      DocumentStatus.validated => ('VALIDADO', AppColors.success),
      DocumentStatus.pending => ('PENDIENTE', AppColors.warning),
      DocumentStatus.rejected => ('RECHAZADO', AppColors.error),
      DocumentStatus.draft => ('BORRADOR', AppColors.info),
    };

    final icon = switch (item.type) {
      'NOTA DE CRÉDITO' => Icons.layers_rounded,
      'RETENCIÓN' => Icons.shield_rounded,
      _ => Icons.request_page_rounded,
    };

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.surface.withValues(alpha: 0.94),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.14),
              borderRadius: BorderRadius.circular(13),
            ),
            child: Icon(icon, color: AppColors.primaryLight),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.type,
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.textMuted,
                    fontWeight: FontWeight.w700,
                    fontSize: 11,
                    letterSpacing: 0.2,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  item.issuer,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.textPrimary,
                    fontWeight: FontWeight.w700,
                    fontSize: 17,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  item.number,
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.textSecondary,
                    fontWeight: FontWeight.w600,
                    fontSize: 13,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                '\$${item.amount.toStringAsFixed(2)}',
                style: const TextStyle(
                  fontFamily: 'Avenir Next',
                  color: AppColors.textPrimary,
                  fontWeight: FontWeight.w800,
                  fontSize: 21,
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
              const SizedBox(height: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                decoration: BoxDecoration(
                  color: status.$2.withValues(alpha: 0.16),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  status.$1,
                  style: TextStyle(
                    fontFamily: 'Avenir Next',
                    fontWeight: FontWeight.w800,
                    color: status.$2,
                    fontSize: 10,
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
