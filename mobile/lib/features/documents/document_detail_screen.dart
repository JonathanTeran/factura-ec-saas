import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';

import '../../core/api/v1_api_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/ui_kit.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/document_provider.dart';

final _money = NumberFormat.currency(locale: 'es_EC', symbol: '\$');
final _dateFormat = DateFormat('dd/MM/yyyy HH:mm');

class DocumentDetailScreen extends ConsumerStatefulWidget {
  final String documentId;

  const DocumentDetailScreen({super.key, required this.documentId});

  @override
  ConsumerState<DocumentDetailScreen> createState() =>
      _DocumentDetailScreenState();
}

class _DocumentDetailScreenState extends ConsumerState<DocumentDetailScreen> {
  bool _sending = false;

  int get _id => int.tryParse(widget.documentId) ?? 0;

  Future<void> _sendToSri(ApiDocumentDetail document) async {
    final messenger = ScaffoldMessenger.of(context);
    final prod = document.environmentLabel.toLowerCase().contains('produc');
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Enviar al SRI'),
        content: Text(
          prod
              ? 'Se firmará y enviará el comprobante al SRI.\n\nSi lo autoriza '
                  'tendrá VALIDEZ TRIBUTARIA, ya no podrá editarse y consumirá '
                  'el número del secuencial.'
              : 'Estás en ambiente de PRUEBAS. Se enviará al SRI para validar '
                  'el flujo, pero el comprobante NO tendrá validez tributaria.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            style: prod
                ? FilledButton.styleFrom(backgroundColor: AppColors.success)
                : null,
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Enviar al SRI'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    setState(() => _sending = true);
    try {
      await ref.read(v1ApiServiceProvider).sendDocument(document.id);
      if (!mounted) return;
      ref.invalidate(documentDetailProvider(_id));
      ref.invalidate(sentDocumentsProvider);
      ref.invalidate(draftDocumentsProvider);
      messenger.showSnackBar(
        const SnackBar(content: Text('Documento enviado al SRI')),
      );
    } on ApiException catch (error) {
      messenger.showSnackBar(SnackBar(content: Text(error.message)));
    } catch (error) {
      messenger.showSnackBar(SnackBar(content: Text(error.toString())));
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final asyncDocument = ref.watch(documentDetailProvider(_id));

    return SafeArea(
      child: asyncDocument.when(
        loading: () => const _DocumentDetailSkeleton(),
        error: (error, _) => _ErrorState(
          message: error is ApiException ? error.message : error.toString(),
          onRetry: () => ref.invalidate(documentDetailProvider(_id)),
        ),
        data: (document) => _DocumentDetailBody(
          document: document,
          sending: _sending,
          onSend: () => _sendToSri(document),
          onRefresh: () async => ref.invalidate(documentDetailProvider(_id)),
        ),
      ),
    );
  }
}

class _DocumentDetailBody extends StatelessWidget {
  final ApiDocumentDetail document;
  final bool sending;
  final VoidCallback onSend;
  final Future<void> Function() onRefresh;

  const _DocumentDetailBody({
    required this.document,
    required this.sending,
    required this.onSend,
    required this.onRefresh,
  });

  bool get _canSend => document.status == 'draft';

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: onRefresh,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 28),
        children: [
          _HeaderCard(document: document),
          const SizedBox(height: 14),
          _SriCard(document: document),
          const SizedBox(height: 14),
          _InfoCard(document: document),
          if (document.status == 'authorized' || document.emailSent) ...[
            const SizedBox(height: 14),
            _EmailCard(document: document),
          ],
          const SizedBox(height: 14),
          _ItemsCard(items: document.items),
          const SizedBox(height: 14),
          _TotalsCard(document: document),
          const SizedBox(height: 14),
          _DocumentActions(document: document),
          const SizedBox(height: 20),
          if (_canSend)
            SizedBox(
              height: 50,
              child: ElevatedButton.icon(
                onPressed: sending ? null : onSend,
                icon: sending
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.send_rounded),
                label: Text(sending ? 'Enviando…' : 'Enviar al SRI'),
              ),
            ),
          if (_canSend) const SizedBox(height: 10),
          SizedBox(
            height: 50,
            child: OutlinedButton(
              onPressed: () => context.go('/documents'),
              child: const Text('Volver a documentos'),
            ),
          ),
        ],
      ),
    );
  }
}

({String label, Color color}) _statusStyle(String status) {
  return switch (status) {
    'authorized' => (label: 'AUTORIZADO', color: AppColors.success),
    'rejected' => (label: 'RECHAZADO', color: AppColors.error),
    'failed' => (label: 'FALLIDO', color: AppColors.error),
    'draft' => (label: 'BORRADOR', color: AppColors.info),
    'voided' => (label: 'ANULADO', color: AppColors.textMuted),
    _ => (label: 'EN PROCESO', color: AppColors.warning),
  };
}

class _HeaderCard extends StatelessWidget {
  final ApiDocumentDetail document;

  const _HeaderCard({required this.document});

  @override
  Widget build(BuildContext context) {
    final style = _statusStyle(document.status);
    return GlassPanel(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  document.documentTypeLabel.toUpperCase(),
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.textMuted,
                    fontWeight: FontWeight.w700,
                    fontSize: 12,
                    letterSpacing: 0.4,
                  ),
                ),
              ),
              _StatusBadge(label: style.label, color: style.color),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            document.documentNumber,
            style: const TextStyle(
              fontFamily: 'Avenir Next',
              color: AppColors.textPrimary,
              fontWeight: FontWeight.w800,
              fontSize: 24,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            _money.format(document.total),
            style: const TextStyle(
              fontFamily: 'Avenir Next',
              color: AppColors.primaryLight,
              fontWeight: FontWeight.w800,
              fontSize: 28,
            ),
          ),
          if (document.environmentLabel.isNotEmpty) ...[
            const SizedBox(height: 4),
            Text(
              'Ambiente: ${document.environmentLabel}',
              style: const TextStyle(
                fontFamily: 'Avenir Next',
                color: AppColors.textSecondary,
                fontSize: 12,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _SriCard extends StatelessWidget {
  final ApiDocumentDetail document;

  const _SriCard({required this.document});

  @override
  Widget build(BuildContext context) {
    return GlassPanel(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const _SectionTitle('Estado SRI'),
          const SizedBox(height: 10),
          if (document.contingencyActive && document.contingencyMessage != null)
            _Banner(
              color: AppColors.warning,
              icon: Icons.cloud_off_rounded,
              text: document.contingencyMessage!,
            ),
          if (document.authorizationNumber != null) ...[
            _InfoRow(
              label: 'Autorización',
              value: document.authorizationNumber!,
              selectable: true,
            ),
            if (document.authorizationDate != null)
              _InfoRow(
                label: 'Fecha autorización',
                value: _dateFormat.format(document.authorizationDate!),
              ),
          ],
          if (document.sriMessages.isNotEmpty) ...[
            const SizedBox(height: 8),
            ...document.sriMessages.map(
              (message) => _Banner(
                color: AppColors.error,
                icon: Icons.error_outline_rounded,
                text: message,
              ),
            ),
          ],
          if (document.authorizationNumber == null &&
              document.sriMessages.isEmpty &&
              !document.contingencyActive)
            Text(
              document.statusLabel,
              style: const TextStyle(
                fontFamily: 'Avenir Next',
                color: AppColors.textSecondary,
                fontSize: 14,
              ),
            ),
          if (document.accessKey != null) ...[
            const SizedBox(height: 8),
            const Text(
              'Clave de acceso',
              style: TextStyle(
                fontFamily: 'Avenir Next',
                color: AppColors.textMuted,
                fontWeight: FontWeight.w600,
                fontSize: 12,
              ),
            ),
            const SizedBox(height: 2),
            SelectableText(
              document.accessKey!,
              style: const TextStyle(
                fontFamily: 'monospace',
                color: AppColors.textSecondary,
                fontSize: 12,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _InfoCard extends StatelessWidget {
  final ApiDocumentDetail document;

  const _InfoCard({required this.document});

  @override
  Widget build(BuildContext context) {
    return GlassPanel(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const _SectionTitle('Detalle'),
          const SizedBox(height: 10),
          _InfoRow(label: 'Cliente', value: document.customerName),
          if (document.issueDate != null)
            _InfoRow(
              label: 'Fecha de emisión',
              value: DateFormat('dd/MM/yyyy').format(document.issueDate!),
            ),
          _InfoRow(label: 'Moneda', value: document.currency),
        ],
      ),
    );
  }
}

/// Historial de envío por correo: a quién se envió el comprobante y cuándo.
class _EmailCard extends StatelessWidget {
  final ApiDocumentDetail document;

  const _EmailCard({required this.document});

  @override
  Widget build(BuildContext context) {
    final sent = document.emailSent && document.emailSentAt != null;
    final to = document.emailSentTo ?? document.customerEmail;

    return GlassPanel(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const _SectionTitle('Envío por correo'),
          const SizedBox(height: 10),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(
                sent
                    ? Icons.mark_email_read_rounded
                    : Icons.mark_email_unread_outlined,
                color: sent ? AppColors.success : AppColors.textMuted,
                size: 20,
              ),
              const SizedBox(width: 10),
              Expanded(
                child: sent
                    ? Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Enviado a',
                            style: TextStyle(
                              fontFamily: 'Avenir Next',
                              color: AppColors.textMuted,
                              fontWeight: FontWeight.w600,
                              fontSize: 12,
                            ),
                          ),
                          const SizedBox(height: 2),
                          SelectableText(
                            to ?? '—',
                            style: const TextStyle(
                              fontFamily: 'Avenir Next',
                              color: AppColors.textPrimary,
                              fontWeight: FontWeight.w700,
                              fontSize: 15,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            _dateFormat.format(document.emailSentAt!),
                            style: const TextStyle(
                              fontFamily: 'Avenir Next',
                              color: AppColors.textSecondary,
                              fontWeight: FontWeight.w600,
                              fontSize: 13,
                            ),
                          ),
                        ],
                      )
                    : Padding(
                        padding: const EdgeInsets.only(top: 1),
                        child: Text(
                          to != null
                              ? 'Aún no se ha enviado. Se enviará a $to.'
                              : 'Aún no se ha enviado por correo.',
                          style: const TextStyle(
                            fontFamily: 'Avenir Next',
                            color: AppColors.textSecondary,
                            fontSize: 14,
                          ),
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

class _ItemsCard extends StatelessWidget {
  final List<ApiDocumentItem> items;

  const _ItemsCard({required this.items});

  @override
  Widget build(BuildContext context) {
    return GlassPanel(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _SectionTitle('Ítems (${items.length})'),
          const SizedBox(height: 6),
          if (items.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 8),
              child: Text(
                'Sin ítems registrados.',
                style: TextStyle(
                  fontFamily: 'Avenir Next',
                  color: AppColors.textSecondary,
                ),
              ),
            )
          else
            ...items.map((item) => _ItemRow(item: item)),
        ],
      ),
    );
  }
}

class _ItemRow extends StatelessWidget {
  final ApiDocumentItem item;

  const _ItemRow({required this.item});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.description,
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.textPrimary,
                    fontWeight: FontWeight.w700,
                    fontSize: 15,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  '${item.quantity.toStringAsFixed(2)} × ${_money.format(item.unitPrice)}'
                  '${item.taxRate > 0 ? '  ·  IVA ${item.taxRate.toStringAsFixed(0)}%' : ''}',
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.textSecondary,
                    fontSize: 12,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Text(
            _money.format(item.subtotal),
            style: const TextStyle(
              fontFamily: 'Avenir Next',
              color: AppColors.textPrimary,
              fontWeight: FontWeight.w700,
              fontSize: 15,
            ),
          ),
        ],
      ),
    );
  }
}

class _TotalsCard extends StatelessWidget {
  final ApiDocumentDetail document;

  const _TotalsCard({required this.document});

  @override
  Widget build(BuildContext context) {
    return GlassPanel(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const _SectionTitle('Totales'),
          const SizedBox(height: 10),
          if (document.subtotal0 > 0)
            _TotalRow(label: 'Subtotal 0%', value: document.subtotal0),
          if (document.subtotal12 > 0)
            _TotalRow(label: 'Subtotal 12%', value: document.subtotal12),
          if (document.subtotal15 > 0)
            _TotalRow(label: 'Subtotal 15%', value: document.subtotal15),
          if (document.subtotalNoTax > 0)
            _TotalRow(label: 'No objeto de IVA', value: document.subtotalNoTax),
          if (document.totalDiscount > 0)
            _TotalRow(label: 'Descuento', value: -document.totalDiscount),
          _TotalRow(label: 'IVA', value: document.totalTax),
          if (document.tip > 0)
            _TotalRow(label: 'Propina', value: document.tip),
          const Divider(color: AppColors.border, height: 22),
          _TotalRow(label: 'Total', value: document.total, emphasize: true),
        ],
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  final String text;

  const _SectionTitle(this.text);

  @override
  Widget build(BuildContext context) {
    return Text(
      text,
      style: const TextStyle(
        fontFamily: 'Avenir Next',
        color: AppColors.textPrimary,
        fontWeight: FontWeight.w800,
        fontSize: 16,
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;
  final bool selectable;

  const _InfoRow({
    required this.label,
    required this.value,
    this.selectable = false,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 5),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 130,
            child: Text(
              label,
              style: const TextStyle(
                fontFamily: 'Avenir Next',
                color: AppColors.textMuted,
                fontWeight: FontWeight.w600,
                fontSize: 13,
              ),
            ),
          ),
          Expanded(
            child: selectable
                ? SelectableText(
                    value,
                    style: const TextStyle(
                      fontFamily: 'Avenir Next',
                      color: AppColors.textPrimary,
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                  )
                : Text(
                    value,
                    style: const TextStyle(
                      fontFamily: 'Avenir Next',
                      color: AppColors.textPrimary,
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                  ),
          ),
        ],
      ),
    );
  }
}

class _TotalRow extends StatelessWidget {
  final String label;
  final double value;
  final bool emphasize;

  const _TotalRow({
    required this.label,
    required this.value,
    this.emphasize = false,
  });

  @override
  Widget build(BuildContext context) {
    final textStyle = TextStyle(
      fontFamily: 'Avenir Next',
      color: emphasize ? AppColors.textPrimary : AppColors.textSecondary,
      fontWeight: emphasize ? FontWeight.w800 : FontWeight.w600,
      fontSize: emphasize ? 18 : 14,
    );
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: textStyle),
          Text(_money.format(value), style: textStyle),
        ],
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  final String label;
  final Color color;

  const _StatusBadge({required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontFamily: 'Avenir Next',
          fontWeight: FontWeight.w800,
          color: color,
          fontSize: 11,
        ),
      ),
    );
  }
}

class _Banner extends StatelessWidget {
  final Color color;
  final IconData icon;
  final String text;

  const _Banner({required this.color, required this.icon, required this.text});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.4)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: color, size: 18),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              text,
              style: TextStyle(
                fontFamily: 'Avenir Next',
                color: color,
                fontWeight: FontWeight.w600,
                fontSize: 13,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ErrorState extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;

  const _ErrorState({required this.message, required this.onRetry});

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

/// Acciones sobre el documento (como en la web): ver/descargar el PDF (RIDE),
/// XML firmado, reenviar por correo, consultar estado en el SRI y anular.
class _DocumentActions extends ConsumerStatefulWidget {
  final ApiDocumentDetail document;

  const _DocumentActions({required this.document});

  @override
  ConsumerState<_DocumentActions> createState() => _DocumentActionsState();
}

class _DocumentActionsState extends ConsumerState<_DocumentActions> {
  // Identificador de la acción en curso, para mostrar el spinner en su botón.
  String? _busy;

  ApiDocumentDetail get _doc => widget.document;
  bool get _isDraft => _doc.status == 'draft';
  bool get _isAuthorized => _doc.status == 'authorized';
  bool get _isRejected => _doc.status == 'rejected';
  bool get _isVoided => _doc.status == 'voided';
  bool get _isFailed => _doc.status == 'failed';
  // "En proceso": enviado pero aún sin resolución del SRI.
  bool get _isProcessing =>
      !_isDraft && !_isAuthorized && !_isRejected && !_isVoided && !_isFailed;
  // Tipos con ítems que se pueden editar como borrador. (NC/ND requieren
  // reprecargar el documento de referencia; se abordan aparte.)
  static const _editableTypes = {'01', '03'};
  bool get _canEdit => _isDraft && _editableTypes.contains(_doc.documentType);

  V1ApiService get _api => ref.read(v1ApiServiceProvider);

  void _snack(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }

  Future<void> _openFile(
    String kind,
    Future<List<int>> Function() fetchBytes,
    String filename,
  ) async {
    if (_busy != null) return;
    setState(() => _busy = kind);
    _snack('Preparando archivo…');
    try {
      final bytes = await fetchBytes();
      if (bytes.isEmpty) {
        throw ApiException('No se recibió el archivo.');
      }
      // Guardamos en un temporal y lo abrimos con el visor nativo (Quick Look
      // en iOS), que permite ver, compartir y guardar.
      final dir = await getTemporaryDirectory();
      final safeName = filename.replaceAll(RegExp(r'[^A-Za-z0-9._-]'), '_');
      final file = File('${dir.path}/$safeName');
      await file.writeAsBytes(bytes, flush: true);
      final result = await OpenFilex.open(file.path);
      if (result.type != ResultType.done) {
        _snack('No se pudo abrir el archivo: ${result.message}');
      }
    } on ApiException catch (error) {
      _snack(error.message);
    } catch (error) {
      _snack(error.toString());
    } finally {
      if (mounted) setState(() => _busy = null);
    }
  }

  Future<void> _checkStatus() async {
    if (_busy != null) return;
    setState(() => _busy = 'status');
    try {
      final status = await _api.checkDocumentStatus(_doc.id);
      ref.invalidate(documentDetailProvider(_doc.id));
      ref.invalidate(sentDocumentsProvider);
      _snack('Estado en el SRI: ${status.statusLabel}');
    } on ApiException catch (error) {
      _snack(error.message);
    } catch (error) {
      _snack(error.toString());
    } finally {
      if (mounted) setState(() => _busy = null);
    }
  }

  Future<void> _resendEmail() async {
    final controller = TextEditingController(
      text: _doc.emailSentTo ?? _doc.customerEmail ?? '',
    );
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Reenviar por correo'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Se enviará el comprobante autorizado (PDF y XML). '
              'Dejá el campo vacío para usar el correo del cliente.',
              style: TextStyle(fontFamily: 'Avenir Next', fontSize: 13),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: controller,
              keyboardType: TextInputType.emailAddress,
              decoration: const InputDecoration(
                labelText: 'Correo (opcional)',
                hintText: 'cliente@correo.com',
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancelar'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Enviar'),
          ),
        ],
      ),
    );
    final email = controller.text.trim();
    controller.dispose();
    if (confirmed != true) return;

    setState(() => _busy = 'email');
    try {
      final message = await _api.resendDocumentEmail(
        _doc.id,
        email: email.isEmpty ? null : email,
      );
      // El envío es en cola: al refrescar se verá "Enviado a … el …".
      ref.invalidate(documentDetailProvider(_doc.id));
      _snack(message);
    } on ApiException catch (error) {
      _snack(error.message);
    } catch (error) {
      _snack(error.toString());
    } finally {
      if (mounted) setState(() => _busy = null);
    }
  }

  Future<void> _void() async {
    final controller = TextEditingController();
    final reason = await showDialog<String>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Anular documento'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'En Ecuador la anulación real se hace emitiendo una Nota de '
              'Crédito. Esto marca el documento como anulado en tu registro.',
              style: TextStyle(fontFamily: 'Avenir Next', fontSize: 13),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: controller,
              maxLength: 300,
              decoration: const InputDecoration(labelText: 'Motivo'),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancelar'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.error),
            onPressed: () => Navigator.pop(ctx, controller.text.trim()),
            child: const Text('Anular'),
          ),
        ],
      ),
    );
    controller.dispose();
    if (reason == null || reason.isEmpty) return;

    setState(() => _busy = 'void');
    try {
      await _api.voidDocument(_doc.id, reason);
      ref.invalidate(documentDetailProvider(_doc.id));
      ref.invalidate(sentDocumentsProvider);
      _snack('Documento marcado como anulado.');
    } on ApiException catch (error) {
      _snack(error.message);
    } catch (error) {
      _snack(error.toString());
    } finally {
      if (mounted) setState(() => _busy = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    return GlassPanel(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const _SectionTitle('Acciones'),
          const SizedBox(height: 12),
          if (_canEdit) ...[
            _ActionButton(
              icon: Icons.edit_rounded,
              label: 'Editar borrador',
              loading: false,
              onTap: () => context.push('/documents/edit/${_doc.id}'),
            ),
            const SizedBox(height: 10),
          ],
          // El RIDE (PDF) está siempre disponible: definitivo si ya está
          // autorizado/rechazado, o una vista previa con marca de agua si no.
          _ActionButton(
            icon: Icons.picture_as_pdf_rounded,
            label: _isDraft ? 'Ver PDF (borrador)' : 'Ver / descargar PDF',
            loading: _busy == 'pdf',
            primary: true,
            onTap: () => _openFile(
              'pdf',
              () => _api.documentRideBytes(_doc.id),
              '${_doc.documentNumber}.pdf',
            ),
          ),
          if (_doc.hasXml) ...[
            const SizedBox(height: 10),
            _ActionButton(
              icon: Icons.code_rounded,
              label: 'Descargar XML firmado',
              loading: _busy == 'xml',
              onTap: () => _openFile(
                'xml',
                () => _api.documentXmlBytes(_doc.id),
                '${_doc.documentNumber}.xml',
              ),
            ),
          ],
          if (_isProcessing) ...[
            const SizedBox(height: 10),
            _ActionButton(
              icon: Icons.sync_rounded,
              label: 'Consultar estado en el SRI',
              loading: _busy == 'status',
              onTap: _checkStatus,
            ),
          ],
          if (_isAuthorized) ...[
            const SizedBox(height: 10),
            _ActionButton(
              icon: Icons.mail_outline_rounded,
              label: 'Reenviar por correo',
              loading: _busy == 'email',
              onTap: _resendEmail,
            ),
            const SizedBox(height: 10),
            _ActionButton(
              icon: Icons.block_rounded,
              label: 'Anular documento',
              loading: _busy == 'void',
              danger: true,
              onTap: _void,
            ),
          ],
        ],
      ),
    );
  }
}

/// Botón de acción del detalle: full-width, con estado de carga por acción.
class _ActionButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool loading;
  final bool primary;
  final bool danger;
  final VoidCallback onTap;

  const _ActionButton({
    required this.icon,
    required this.label,
    required this.loading,
    required this.onTap,
    this.primary = false,
    this.danger = false,
  });

  @override
  Widget build(BuildContext context) {
    final color = danger ? AppColors.error : AppColors.primary;
    final spinner = SizedBox(
      width: 18,
      height: 18,
      child: CircularProgressIndicator(
        strokeWidth: 2,
        color: primary ? Colors.white : color,
      ),
    );

    if (primary) {
      return SizedBox(
        height: 50,
        width: double.infinity,
        child: ElevatedButton.icon(
          onPressed: loading ? null : onTap,
          icon: loading ? spinner : Icon(icon),
          label: Text(label),
        ),
      );
    }

    return SizedBox(
      height: 48,
      width: double.infinity,
      child: OutlinedButton.icon(
        onPressed: loading ? null : onTap,
        icon: loading ? spinner : Icon(icon, color: color),
        label: Text(label),
        style: OutlinedButton.styleFrom(
          foregroundColor: color,
          side: BorderSide(color: color.withValues(alpha: 0.5)),
        ),
      ),
    );
  }
}

/// Skeleton del detalle de documento: encabezado + total + datos + ítems.
class _DocumentDetailSkeleton extends StatelessWidget {
  const _DocumentDetailSkeleton();

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 28),
      children: const [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Skeleton(width: 120, height: 12),
                  SizedBox(height: 8),
                  Skeleton(width: 200, height: 24, radius: 8),
                ],
              ),
            ),
            Skeleton(width: 90, height: 28, radius: 14),
          ],
        ),
        SizedBox(height: 18),
        Skeleton(height: 110, radius: 20),
        SizedBox(height: 14),
        Skeleton(height: 130, radius: 20),
        SizedBox(height: 18),
        Skeleton(width: 140, height: 16),
        SizedBox(height: 10),
        Skeleton(height: 64, radius: 16),
        SizedBox(height: 10),
        Skeleton(height: 64, radius: 16),
        SizedBox(height: 18),
        Skeleton(height: 52, radius: 16),
      ],
    );
  }
}
