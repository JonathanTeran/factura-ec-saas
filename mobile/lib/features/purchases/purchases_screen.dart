import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/theme/app_theme.dart';
import '../../core/widgets/entity_row.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/loading_widget.dart';
import '../../core/widgets/page_header.dart';
import '../../core/widgets/search_bar_widget.dart';
import '../../core/widgets/section_header.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/purchase_provider.dart';

class PurchasesScreen extends ConsumerStatefulWidget {
  const PurchasesScreen({super.key});

  @override
  ConsumerState<PurchasesScreen> createState() => _PurchasesScreenState();
}

class _PurchasesScreenState extends ConsumerState<PurchasesScreen> {
  String _query = '';

  @override
  Widget build(BuildContext context) {
    final purchasesAsync = ref.watch(purchasesProvider);
    final state = purchasesAsync.when(
      data: (data) =>
          data.items.isEmpty ? AppDataState.empty : AppDataState.ready,
      loading: () => AppDataState.loading,
      error: (error, _) =>
          isOfflineError(error) ? AppDataState.offline : AppDataState.error,
    );

    if (state != AppDataState.ready) {
      return ModuleStateView(
        module: 'Compras',
        state: state,
        onPrimaryAction: () => ref.invalidate(purchasesProvider),
      );
    }

    final q = _query.trim().toLowerCase();
    final items = purchasesAsync.value!.items.where((p) {
      if (q.isEmpty) return true;
      return (p.supplierName ?? '').toLowerCase().contains(q) ||
          p.supplierDocumentNumber.toLowerCase().contains(q);
    }).toList(growable: false);

    return SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            PageHeader(
              title: 'Compras',
              subtitle: '${items.length} registros',
              trailing: IconButton.filledTonal(
                tooltip: 'Registrar compra',
                onPressed: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text(
                        'El registro de compras electrónicas se hace desde la web por ahora.',
                      ),
                    ),
                  );
                },
                icon: const Icon(Icons.add_rounded),
              ),
            ),
            const SizedBox(height: 12),
            SearchInput(
              hintText: 'Buscar por proveedor o número',
              onChanged: (v) => setState(() => _query = v),
            ),
            const SizedBox(height: 16),
            SectionHeader(
              title: q.isEmpty ? 'Recientes' : 'Resultados (${items.length})',
              actionText: '',
            ),
            const SizedBox(height: 10),
            if (items.isEmpty)
              const GlassPanel(
                child: Padding(
                  padding: EdgeInsets.symmetric(vertical: 18),
                  child: Text(
                    'Sin resultados.',
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
                  for (var i = 0; i < items.length; i++) ...[
                    EntityRow(
                      icon: Icons.receipt_rounded,
                      title: items[i].supplierName ?? 'Proveedor #${items[i].supplierId}',
                      subtitle: items[i].supplierDocumentNumber,
                      trailing: '\$${items[i].total.toStringAsFixed(2)}',
                      color: items[i].status == 'voided'
                          ? AppColors.error
                          : AppColors.primary,
                    ),
                    if (i < items.length - 1) ...[
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
    );
  }
}
