import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../core/api/v1_api_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/loading_widget.dart';
import '../../core/widgets/page_header.dart';
import '../../core/widgets/search_bar_widget.dart';
import '../../core/widgets/section_header.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/quote_provider.dart';

/// Proformas / cotizaciones: propuestas comerciales antes de facturar.
class QuotesScreen extends ConsumerStatefulWidget {
  const QuotesScreen({super.key});

  @override
  ConsumerState<QuotesScreen> createState() => _QuotesScreenState();
}

class _QuotesScreenState extends ConsumerState<QuotesScreen> {
  String _query = '';
  int? _busyId;

  Future<void> _action(ApiQuote quote, String action, String okMsg) async {
    setState(() => _busyId = quote.id);
    try {
      await ref.read(v1ApiServiceProvider).quoteAction(quote.id, action);
      ref.read(quotesRefreshProvider.notifier).state++;
      _snack(okMsg);
    } on ApiException catch (e) {
      _snack(e.message);
    } catch (e) {
      _snack(e.toString());
    } finally {
      if (mounted) setState(() => _busyId = null);
    }
  }

  Future<void> _delete(ApiQuote quote) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('¿Eliminar ${quote.quoteNumber}?'),
        content: const Text('Esta acción no se puede deshacer.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: AppColors.error),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Eliminar'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    setState(() => _busyId = quote.id);
    try {
      await ref.read(v1ApiServiceProvider).deleteQuote(quote.id);
      ref.read(quotesRefreshProvider.notifier).state++;
      _snack('Proforma eliminada.');
    } on ApiException catch (e) {
      _snack(e.message);
    } catch (e) {
      _snack(e.toString());
    } finally {
      if (mounted) setState(() => _busyId = null);
    }
  }

  void _snack(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) {
    final quotesAsync = ref.watch(quotesProvider);
    final state = quotesAsync.when(
      data: (data) =>
          data.items.isEmpty ? AppDataState.empty : AppDataState.ready,
      loading: () => AppDataState.loading,
      error: (error, _) =>
          isOfflineError(error) ? AppDataState.offline : AppDataState.error,
    );

    if (state == AppDataState.empty) {
      // Vacío con acción de crear la primera proforma.
      return SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              PageHeader(
                title: 'Proformas',
                subtitle: 'Cotizaciones antes de facturar',
                trailing: IconButton.filledTonal(
                  tooltip: 'Nueva proforma',
                  onPressed: () => context.push('/quotes/new'),
                  icon: const Icon(Icons.note_add_rounded),
                ),
              ),
              const SizedBox(height: 24),
              GlassPanel(
                child: Column(
                  children: [
                    const Icon(Icons.request_quote_outlined,
                        size: 44, color: AppColors.textMuted),
                    const SizedBox(height: 10),
                    const Text(
                      'Aún no tienes proformas',
                      style: TextStyle(
                        fontFamily: 'Avenir Next',
                        fontWeight: FontWeight.w700,
                        fontSize: 17,
                        color: AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 6),
                    const Text(
                      'Crea una cotización, envíala a tu cliente y conviértela en factura cuando la acepte.',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontFamily: 'Avenir Next',
                        color: AppColors.textSecondary,
                      ),
                    ),
                    const SizedBox(height: 14),
                    FilledButton.icon(
                      onPressed: () => context.push('/quotes/new'),
                      icon: const Icon(Icons.add_rounded),
                      label: const Text('Nueva proforma'),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      );
    }

    if (state != AppDataState.ready) {
      return ModuleStateView(
        module: 'Proformas',
        state: state,
        onPrimaryAction: () => ref.invalidate(quotesProvider),
      );
    }

    final q = _query.trim().toLowerCase();
    final items = quotesAsync.value!.items.where((quote) {
      if (q.isEmpty) return true;
      return quote.quoteNumber.toLowerCase().contains(q) ||
          quote.customerName.toLowerCase().contains(q);
    }).toList(growable: false);

    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: () async {
        ref.invalidate(quotesProvider);
        await ref.read(quotesProvider.future);
      },
      child: SafeArea(
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              PageHeader(
                title: 'Proformas',
                subtitle: 'Cotizaciones antes de facturar',
                trailing: IconButton.filledTonal(
                  tooltip: 'Nueva proforma',
                  onPressed: () => context.push('/quotes/new'),
                  icon: const Icon(Icons.note_add_rounded),
                ),
              ),
              const SizedBox(height: 12),
              SearchInput(
                hintText: 'Buscar por número o cliente',
                onChanged: (v) => setState(() => _query = v),
              ),
              const SizedBox(height: 16),
              SectionHeader(
                title: q.isEmpty
                    ? 'Proformas (${items.length})'
                    : 'Resultados (${items.length})',
                actionText: '',
              ),
              const SizedBox(height: 10),
              for (final quote in items) ...[
                _QuoteCard(
                  quote: quote,
                  busy: _busyId == quote.id,
                  onSend: () => _action(
                      quote, 'send', 'Proforma marcada como enviada.'),
                  onAccept: () =>
                      _action(quote, 'accept', 'Proforma aceptada.'),
                  onReject: () =>
                      _action(quote, 'reject', 'Proforma rechazada.'),
                  onDelete: () => _delete(quote),
                ),
                const SizedBox(height: 12),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _QuoteCard extends StatelessWidget {
  final ApiQuote quote;
  final bool busy;
  final VoidCallback onSend;
  final VoidCallback onAccept;
  final VoidCallback onReject;
  final VoidCallback onDelete;

  const _QuoteCard({
    required this.quote,
    required this.busy,
    required this.onSend,
    required this.onAccept,
    required this.onReject,
    required this.onDelete,
  });

  ({String label, Color color}) get _statusStyle => switch (quote.status) {
        'accepted' => (label: 'ACEPTADA', color: AppColors.success),
        'invoiced' => (label: 'FACTURADA', color: AppColors.success),
        'rejected' => (label: 'RECHAZADA', color: AppColors.error),
        'sent' => (label: 'ENVIADA', color: AppColors.warning),
        'expired' => (label: 'VENCIDA', color: AppColors.textMuted),
        _ => (label: 'BORRADOR', color: AppColors.info),
      };

  @override
  Widget build(BuildContext context) {
    final style = _statusStyle;
    final money = NumberFormat.currency(locale: 'es_EC', symbol: r'$');
    final dates = DateFormat('dd/MM/yyyy');

    return GlassPanel(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  quote.quoteNumber,
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    fontWeight: FontWeight.w800,
                    fontSize: 16,
                    color: AppColors.textPrimary,
                  ),
                ),
              ),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: style.color.withValues(alpha: 0.16),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  style.label,
                  style: TextStyle(
                    fontFamily: 'Avenir Next',
                    fontWeight: FontWeight.w800,
                    fontSize: 11,
                    color: style.color,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            quote.customerName,
            style: const TextStyle(
              fontFamily: 'Avenir Next',
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            [
              if (quote.issueDate != null)
                'Emitida ${dates.format(quote.issueDate!)}',
              if (quote.expiryDate != null)
                'vence ${dates.format(quote.expiryDate!)}',
            ].join(' · '),
            style: const TextStyle(
              fontFamily: 'Avenir Next',
              color: AppColors.textMuted,
              fontSize: 12,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            money.format(quote.total),
            style: const TextStyle(
              fontFamily: 'Avenir Next',
              fontWeight: FontWeight.w800,
              fontSize: 18,
              color: AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 10),
          if (busy)
            const Center(
              child: Padding(
                padding: EdgeInsets.all(6),
                child: SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
              ),
            )
          else
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                if (quote.isDraft)
                  FilledButton.tonalIcon(
                    onPressed: onSend,
                    icon: const Icon(Icons.send_rounded, size: 16),
                    label: const Text('Marcar enviada'),
                  ),
                if (quote.isSent) ...[
                  FilledButton.tonalIcon(
                    onPressed: onAccept,
                    icon: const Icon(Icons.check_rounded, size: 16),
                    label: const Text('Aceptar'),
                  ),
                  OutlinedButton.icon(
                    onPressed: onReject,
                    icon: const Icon(Icons.close_rounded, size: 16),
                    label: const Text('Rechazar'),
                  ),
                ],
                if (quote.isAccepted && !quote.isConverted)
                  FilledButton.icon(
                    onPressed: () => context.push('/documents/new?type=01'),
                    icon: const Icon(Icons.receipt_long_rounded, size: 16),
                    label: const Text('Crear factura'),
                  ),
                if (!quote.isConverted)
                  IconButton(
                    tooltip: 'Eliminar',
                    onPressed: onDelete,
                    icon: const Icon(Icons.delete_outline_rounded,
                        color: AppColors.error),
                  ),
              ],
            ),
        ],
      ),
    );
  }
}
