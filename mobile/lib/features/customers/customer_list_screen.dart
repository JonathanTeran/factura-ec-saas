import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_theme.dart';
import '../../core/widgets/entity_row.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/loading_widget.dart';
import '../../core/widgets/page_header.dart';
import '../../core/widgets/search_bar_widget.dart';
import '../../core/widgets/section_header.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/customer_provider.dart';

class CustomersScreen extends ConsumerStatefulWidget {
  const CustomersScreen({super.key});

  @override
  ConsumerState<CustomersScreen> createState() => _CustomersScreenState();
}

class _CustomersScreenState extends ConsumerState<CustomersScreen> {
  String _query = '';

  @override
  Widget build(BuildContext context) {
    final customersAsync = ref.watch(customersProvider);
    final state = customersAsync.when(
      data: (data) =>
          data.items.isEmpty ? AppDataState.empty : AppDataState.ready,
      loading: () => AppDataState.loading,
      error: (error, _) =>
          isOfflineError(error) ? AppDataState.offline : AppDataState.error,
    );

    if (state != AppDataState.ready) {
      return ModuleStateView(
        module: 'Clientes',
        state: state,
        onPrimaryAction: () => ref.invalidate(customersProvider),
      );
    }

    final q = _query.trim().toLowerCase();
    final items = customersAsync.value!.items.where((c) {
      if (q.isEmpty) return true;
      return c.name.toLowerCase().contains(q) ||
          c.identificationNumber.toLowerCase().contains(q);
    }).toList(growable: false);

    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: () async {
        ref.invalidate(customersProvider);
        await ref.read(customersProvider.future);
      },
      child: SafeArea(
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            PageHeader(
              title: 'Clientes',
              subtitle: 'Relación comercial y frecuencia',
              trailing: IconButton.filledTonal(
                tooltip: 'Nuevo cliente',
                onPressed: () => context.push('/customers/new'),
                icon: const Icon(Icons.person_add_alt_1_rounded),
              ),
            ),
            const SizedBox(height: 12),
            SearchInput(
              hintText: 'Buscar por nombre o identificación',
              onChanged: (v) => setState(() => _query = v),
            ),
            const SizedBox(height: 16),
            SectionHeader(
              title: q.isEmpty ? 'Clientes activos' : 'Resultados (${items.length})',
              actionText: '',
            ),
            const SizedBox(height: 10),
            if (items.isEmpty)
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
                  for (var i = 0; i < items.length; i++) ...[
                    InkWell(
                      onTap: () =>
                          context.push('/customers/edit', extra: items[i]),
                      borderRadius: BorderRadius.circular(12),
                      child: EntityRow(
                        icon: Icons.apartment_rounded,
                        title: items[i].name,
                        subtitle:
                            '${items[i].identificationType} ${items[i].identificationNumber}'
                            '${items[i].isActive ? '' : ' · Inactivo'}',
                        trailing: items[i].email ?? items[i].phone ?? '-',
                        color: items[i].isActive
                            ? (i.isEven
                                  ? AppColors.primary
                                  : AppColors.secondary)
                            : AppColors.textMuted,
                      ),
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
      ),
    );
  }
}
