import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_theme.dart';
import '../../core/widgets/entity_row.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/loading_widget.dart';
import '../../core/widgets/money_text.dart';
import '../../core/widgets/page_header.dart';
import '../../core/widgets/search_bar_widget.dart';
import '../../core/widgets/section_header.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/product_provider.dart';

class ProductsScreen extends ConsumerStatefulWidget {
  const ProductsScreen({super.key});

  @override
  ConsumerState<ProductsScreen> createState() => _ProductsScreenState();
}

class _ProductsScreenState extends ConsumerState<ProductsScreen> {
  String _query = '';

  @override
  Widget build(BuildContext context) {
    final productsAsync = ref.watch(productsProvider);
    final state = productsAsync.when(
      data: (data) =>
          data.items.isEmpty ? AppDataState.empty : AppDataState.ready,
      loading: () => AppDataState.loading,
      error: (error, _) =>
          isOfflineError(error) ? AppDataState.offline : AppDataState.error,
    );

    if (state != AppDataState.ready) {
      return ModuleStateView(
        module: 'Productos',
        state: state,
        onPrimaryAction: () => ref.invalidate(productsProvider),
      );
    }

    final q = _query.trim().toLowerCase();
    final products = productsAsync.value!.items.where((p) {
      if (q.isEmpty) return true;
      return p.name.toLowerCase().contains(q) ||
          p.code.toLowerCase().contains(q);
    }).toList(growable: false);

    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: () async {
        ref.invalidate(productsProvider);
        await ref.read(productsProvider.future);
      },
      child: SafeArea(
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            PageHeader(
              title: 'Productos',
              subtitle: 'Inventario y catálogo de venta',
              trailing: IconButton.filledTonal(
                tooltip: 'Nuevo producto',
                onPressed: () => context.push('/products/new'),
                icon: const Icon(Icons.add_box_rounded),
              ),
            ),
            const SizedBox(height: 12),
            SearchInput(
              hintText: 'Buscar por nombre o código',
              onChanged: (v) => setState(() => _query = v),
            ),
            const SizedBox(height: 16),
            SectionHeader(
              title:
                  q.isEmpty ? 'Catálogo activo' : 'Resultados (${products.length})',
              actionText: '',
            ),
            const SizedBox(height: 10),
            if (products.isEmpty)
              const GlassPanel(
                child: Padding(
                  padding: EdgeInsets.symmetric(vertical: 18),
                  child: Text(
                    'Sin resultados para tu búsqueda.',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontFamily: 'Avenir Next',
                      color: AppColors.textSecondary,
                    ),
                  ),
                ),
              )
            else
            GlassPanel(
              child: Column(
                children: [
                  for (var i = 0; i < products.length; i++) ...[
                    InkWell(
                      onTap: () => context.push(
                        '/products/edit',
                        extra: products[i],
                      ),
                      borderRadius: BorderRadius.circular(12),
                      child: EntityRow(
                        icon: products[i].trackInventory
                            ? Icons.verified_user_rounded
                            : Icons.inventory_2_rounded,
                        title: products[i].name,
                        subtitle:
                            '${products[i].typeLabel} · Código ${products[i].code}'
                            '${products[i].isActive ? '' : ' · Inactivo'}',
                        trailing: currency(products[i].unitPrice),
                        color: products[i].isActive
                            ? (i.isEven ? AppColors.warning : AppColors.info)
                            : AppColors.textMuted,
                      ),
                    ),
                    if (i < products.length - 1) ...[
                      const SizedBox(height: 12),
                      const Divider(height: 1),
                      const SizedBox(height: 12),
                    ],
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
      ),
    );
  }
}
