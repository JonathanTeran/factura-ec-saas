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

class SuppliersScreen extends ConsumerWidget {
  const SuppliersScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final suppliersAsync = ref.watch(suppliersProvider);
    final state = suppliersAsync.when(
      data: (data) =>
          data.items.isEmpty ? AppDataState.empty : AppDataState.ready,
      loading: () => AppDataState.loading,
      error: (error, _) =>
          isOfflineError(error) ? AppDataState.offline : AppDataState.error,
    );

    if (state != AppDataState.ready) {
      return ModuleStateView(
        module: 'Proveedores',
        state: state,
        onPrimaryAction: () => ref.invalidate(suppliersProvider),
      );
    }

    final items = suppliersAsync.value!.items;

    return SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            PageHeader(
              title: 'Proveedores',
              subtitle: '${items.length} proveedores activos',
              trailing: IconButton.filledTonal(
                onPressed: () {},
                icon: const Icon(Icons.person_add_alt_1_rounded),
              ),
            ),
            const SizedBox(height: 12),
            const SearchInput(),
            const SizedBox(height: 16),
            const SectionHeader(title: 'Directorio', actionText: ''),
            const SizedBox(height: 10),
            GlassPanel(
              child: Column(
                children: [
                  for (var i = 0; i < items.length; i++) ...[
                    EntityRow(
                      icon: Icons.business_rounded,
                      title: items[i].businessName,
                      subtitle: '${items[i].identificationType} ${items[i].identification}',
                      trailing: items[i].email ?? items[i].phone ?? '-',
                      color: i.isEven ? AppColors.primary : AppColors.secondary,
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
