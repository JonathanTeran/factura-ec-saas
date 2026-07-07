import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../core/api/v1_api_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/page_header.dart';
import '../../core/widgets/ui_kit.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/document_provider.dart';

enum DocumentStatus { validated, pending, rejected, draft }

class _DocumentItem {
  final int id;
  final String type;
  final String issuer;
  final String number;
  final double amount;
  final String date;
  final DocumentStatus status;

  _DocumentItem({
    required this.id,
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
    id: document.id,
    type: document.documentTypeLabel.toUpperCase(),
    issuer: document.issuer,
    number: document.documentNumber,
    amount: document.total,
    date: _shortDate(document.issueDate),
    status: _documentStatusFromApi(document.status),
  );
}

class DocumentsScreen extends ConsumerStatefulWidget {
  const DocumentsScreen({super.key});

  @override
  ConsumerState<DocumentsScreen> createState() => _DocumentsScreenState();
}

class _DocumentsScreenState extends ConsumerState<DocumentsScreen> {
  final _searchController = TextEditingController();
  String _search = '';
  // Cambia con el botón "Actualizar"; forzará a las pestañas a recargar.
  int _refreshTick = 0;
  Timer? _debounce;

  @override
  void dispose() {
    _debounce?.cancel();
    _searchController.dispose();
    super.dispose();
  }

  void _onSearchChanged(String value) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () {
      if (!mounted) return;
      final next = value.trim();
      if (next != _search) setState(() => _search = next);
    });
  }

  void _clearSearch() {
    _searchController.clear();
    _debounce?.cancel();
    setState(() => _search = '');
  }

  @override
  Widget build(BuildContext context) {
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
                subtitle: 'Todos tus comprobantes, siempre a mano',
                trailing: IconButton.filledTonal(
                  tooltip: 'Actualizar',
                  onPressed: () => setState(() => _refreshTick++),
                  icon: const Icon(Icons.refresh_rounded),
                ),
              ),
            ),
            const SizedBox(height: 12),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: TextField(
                controller: _searchController,
                onChanged: _onSearchChanged,
                textInputAction: TextInputAction.search,
                decoration: InputDecoration(
                  hintText: 'Buscar por cliente, RUC o número',
                  hintStyle: const TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.textMuted,
                    fontWeight: FontWeight.w500,
                  ),
                  prefixIcon: const Icon(Icons.search_rounded),
                  suffixIcon: _search.isEmpty
                      ? null
                      : IconButton(
                          onPressed: _clearSearch,
                          icon: const Icon(Icons.close_rounded),
                        ),
                ),
              ),
            ),
            const SizedBox(height: 12),
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
                    Tab(text: 'Autorizados'),
                    Tab(text: 'Borradores'),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 12),
            Expanded(
              child: TabBarView(
                children: [
                  _PaginatedDocumentsTab(
                    status: null,
                    search: _search,
                    refreshTick: _refreshTick,
                    emptyMessage: 'No tienes documentos emitidos.',
                  ),
                  _PaginatedDocumentsTab(
                    status: 'authorized',
                    search: _search,
                    refreshTick: _refreshTick,
                    emptyMessage:
                        'No hay documentos autorizados para este período.',
                  ),
                  _PaginatedDocumentsTab(
                    status: 'draft',
                    search: _search,
                    refreshTick: _refreshTick,
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

/// Lista paginada con scroll infinito ("ver todos"), pull-to-refresh y
/// búsqueda. Mantiene su propio estado por pestaña y sobrevive el cambio de tab.
class _PaginatedDocumentsTab extends ConsumerStatefulWidget {
  final String? status;
  final String search;
  final int refreshTick;
  final String emptyMessage;

  const _PaginatedDocumentsTab({
    required this.status,
    required this.search,
    required this.refreshTick,
    required this.emptyMessage,
  });

  @override
  ConsumerState<_PaginatedDocumentsTab> createState() =>
      _PaginatedDocumentsTabState();
}

class _PaginatedDocumentsTabState extends ConsumerState<_PaginatedDocumentsTab>
    with AutomaticKeepAliveClientMixin {
  final _scroll = ScrollController();
  final List<ApiDocument> _items = [];

  int _page = 1;
  int _lastPage = 1;
  bool _loading = false;
  bool _initial = true;
  int _total = 0;
  Object? _error;

  bool get _hasMore => _page < _lastPage;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    _scroll.addListener(_onScroll);
    _load(reset: true);
  }

  @override
  void didUpdateWidget(covariant _PaginatedDocumentsTab old) {
    super.didUpdateWidget(old);
    // La búsqueda o el botón "Actualizar" cambiaron → recargar desde cero.
    if (old.search != widget.search || old.refreshTick != widget.refreshTick) {
      _load(reset: true);
    }
  }

  @override
  void dispose() {
    _scroll.removeListener(_onScroll);
    _scroll.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (!_scroll.hasClients) return;
    final threshold = _scroll.position.maxScrollExtent - 320;
    if (_scroll.position.pixels >= threshold && _hasMore && !_loading) {
      _load();
    }
  }

  Future<void> _load({bool reset = false}) async {
    if (_loading) return;
    final page = reset ? 1 : _page + 1;
    setState(() {
      _loading = true;
      if (reset) _error = null;
    });
    try {
      final result = await ref.read(v1ApiServiceProvider).documents(
            status: widget.status,
            search: widget.search.isEmpty ? null : widget.search,
            perPage: 20,
            page: page,
          );
      if (!mounted) return;
      setState(() {
        if (reset) _items.clear();
        _items.addAll(result.items);
        _page = result.currentPage;
        _lastPage = result.lastPage;
        _total = result.total;
        _initial = false;
        _loading = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _error = error;
        _initial = false;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);

    // Al crear/editar/enviar/eliminar un documento, se recarga esta pestaña.
    ref.listen(documentsRefreshProvider, (_, _) => _load(reset: true));

    if (_initial && _loading) return const _DocumentsSkeleton();

    if (_error != null && _items.isEmpty) {
      return _ErrorPanel(
        message: _error is ApiException
            ? (_error as ApiException).message
            : _error.toString(),
        onRetry: () => _load(reset: true),
      );
    }

    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: () => _load(reset: true),
      child: _items.isEmpty
          ? ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              children: [
                SizedBox(
                  height: MediaQuery.of(context).size.height * 0.6,
                  child: EmptyState(
                    icon: Icons.receipt_long_rounded,
                    title: widget.search.isEmpty
                        ? 'Nada por aquí todavía'
                        : 'Sin resultados',
                    message: widget.search.isEmpty
                        ? widget.emptyMessage
                        : 'No encontramos documentos para "${widget.search}".',
                  ),
                ),
              ],
            )
          : ListView.separated(
              controller: _scroll,
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(20, 2, 20, 16),
              itemCount: _items.length + 1,
              separatorBuilder: (_, index) =>
                  index < _items.length - 1 ? const SizedBox(height: 10) : const SizedBox.shrink(),
              itemBuilder: (context, index) {
                if (index >= _items.length) {
                  return _ListFooter(hasMore: _hasMore, total: _total);
                }
                final item = _documentItemFromApi(_items[index]);
                return FadeInUp(
                  index: index < 12 ? index : 12,
                  child: _DocumentTile(item: item),
                );
              },
            ),
    );
  }
}

/// Pie de la lista: spinner mientras carga más, o un cierre con el total.
class _ListFooter extends StatelessWidget {
  final bool hasMore;
  final int total;

  const _ListFooter({required this.hasMore, required this.total});

  @override
  Widget build(BuildContext context) {
    if (hasMore) {
      return const Padding(
        padding: EdgeInsets.only(top: 14, bottom: 4),
        child: Center(
          child: SizedBox(
            width: 22,
            height: 22,
            child: CircularProgressIndicator(strokeWidth: 2),
          ),
        ),
      );
    }
    return Padding(
      padding: const EdgeInsets.only(top: 16, bottom: 4),
      child: Center(
        child: Text(
          '$total ${total == 1 ? 'documento' : 'documentos'}',
          style: const TextStyle(
            fontFamily: 'Avenir Next',
            color: AppColors.textMuted,
            fontWeight: FontWeight.w600,
            fontSize: 12,
          ),
        ),
      ),
    );
  }
}

class _ErrorPanel extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;

  const _ErrorPanel({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: GlassPanel(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                message,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontFamily: 'Avenir Next',
                  color: AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 10),
              ElevatedButton(
                onPressed: onRetry,
                child: const Text('Reintentar'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _DocumentsSkeleton extends StatelessWidget {
  const _DocumentsSkeleton();

  @override
  Widget build(BuildContext context) {
    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(20, 2, 20, 14),
      itemCount: 7,
      separatorBuilder: (_, _) => const SizedBox(height: 10),
      itemBuilder: (context, index) => Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: AppColors.surface.withValues(alpha: 0.6),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppColors.border),
        ),
        child: Row(
          children: [
            const Skeleton.circle(size: 40),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: const [
                  Skeleton(width: 150, height: 13),
                  SizedBox(height: 8),
                  Skeleton(width: 90, height: 11),
                ],
              ),
            ),
            const SizedBox(width: 12),
            const Skeleton(width: 54, height: 22, radius: 11),
          ],
        ),
      ),
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

    return Material(
      color: AppColors.surface.withValues(alpha: 0.94),
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
        borderRadius: BorderRadius.circular(18),
        onTap: () => context.push('/documents/${item.id}'),
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
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
                    padding:
                        const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
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
        ),
      ),
    );
  }
}
