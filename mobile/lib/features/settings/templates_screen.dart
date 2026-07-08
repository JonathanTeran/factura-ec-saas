import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/api/v1_api_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/page_header.dart';
import '../../data/providers/auth_provider.dart';

/// Plantillas del comprobante: correo al cliente (asunto/mensaje), envío
/// automático y pie de página del RIDE — como en la web.
class TemplatesScreen extends ConsumerStatefulWidget {
  const TemplatesScreen({super.key});

  @override
  ConsumerState<TemplatesScreen> createState() => _TemplatesScreenState();
}

class _TemplatesScreenState extends ConsumerState<TemplatesScreen> {
  final _subjectCtrl = TextEditingController();
  final _messageCtrl = TextEditingController();
  final _footerCtrl = TextEditingController();

  bool _autoSend = true;
  bool _loading = true;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _subjectCtrl.dispose();
    _messageCtrl.dispose();
    _footerCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final settings = await ref.read(v1ApiServiceProvider).documentSettings();
      if (!mounted) return;
      setState(() {
        _autoSend = settings.autoSendEmail;
        _subjectCtrl.text = settings.emailSubject;
        _messageCtrl.text = settings.emailMessage;
        _footerCtrl.text = settings.rideFooter;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = e is ApiException
          ? e.message
          : 'No se pudo cargar la configuración.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _save() async {
    if (_subjectCtrl.text.trim().isEmpty || _messageCtrl.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('El asunto y el mensaje del correo son obligatorios.'),
        ),
      );
      return;
    }
    setState(() => _saving = true);
    try {
      await ref.read(v1ApiServiceProvider).updateDocumentSettings(
            autoSendEmail: _autoSend,
            emailSubject: _subjectCtrl.text,
            emailMessage: _messageCtrl.text,
            rideFooter: _footerCtrl.text,
          );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Plantillas guardadas.')),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e is ApiException
              ? e.message
              : 'No se pudo guardar. Intenta de nuevo.'),
        ),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            PageHeader(
              title: 'Plantillas de documento',
              subtitle: 'Correo al cliente y pie de página del RIDE',
              trailing: IconButton.filledTonal(
                tooltip: 'Volver',
                onPressed: () => context.pop(),
                icon: const Icon(Icons.close_rounded),
              ),
            ),
            const SizedBox(height: 12),
            Expanded(
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : SingleChildScrollView(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          if (_error != null) ...[
                            GlassPanel(
                              child: Row(
                                children: [
                                  const Icon(Icons.error_outline_rounded,
                                      color: AppColors.warning),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Text(
                                      _error!,
                                      style: const TextStyle(
                                        fontFamily: 'Avenir Next',
                                        color: AppColors.textSecondary,
                                      ),
                                    ),
                                  ),
                                  TextButton(
                                    onPressed: _load,
                                    child: const Text('Reintentar'),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(height: 14),
                          ],
                          GlassPanel(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'Correo al cliente',
                                  style: TextStyle(
                                    fontFamily: 'Avenir Next',
                                    fontWeight: FontWeight.w700,
                                    fontSize: 16,
                                    color: AppColors.textPrimary,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                const Text(
                                  'Se usa al enviar el comprobante autorizado por correo.',
                                  style: TextStyle(
                                    fontFamily: 'Avenir Next',
                                    color: AppColors.textMuted,
                                    fontSize: 12,
                                  ),
                                ),
                                const SizedBox(height: 8),
                                SwitchListTile(
                                  contentPadding: EdgeInsets.zero,
                                  title: const Text(
                                    'Enviar automáticamente al autorizar',
                                    style: TextStyle(
                                      fontFamily: 'Avenir Next',
                                      fontSize: 14,
                                      color: AppColors.textPrimary,
                                    ),
                                  ),
                                  value: _autoSend,
                                  onChanged: (v) =>
                                      setState(() => _autoSend = v),
                                ),
                                const SizedBox(height: 4),
                                TextField(
                                  controller: _subjectCtrl,
                                  maxLength: 150,
                                  decoration: const InputDecoration(
                                    labelText: 'Asunto del correo *',
                                  ),
                                ),
                                const SizedBox(height: 12),
                                TextField(
                                  controller: _messageCtrl,
                                  maxLength: 1000,
                                  maxLines: 5,
                                  decoration: const InputDecoration(
                                    labelText: 'Mensaje del correo *',
                                    alignLabelWithHint: true,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 14),
                          GlassPanel(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'RIDE (PDF)',
                                  style: TextStyle(
                                    fontFamily: 'Avenir Next',
                                    fontWeight: FontWeight.w700,
                                    fontSize: 16,
                                    color: AppColors.textPrimary,
                                  ),
                                ),
                                const SizedBox(height: 8),
                                TextField(
                                  controller: _footerCtrl,
                                  maxLength: 300,
                                  maxLines: 2,
                                  decoration: const InputDecoration(
                                    labelText: 'Texto de pie de página',
                                    hintText:
                                        'Ej: Gracias por su compra. www.tuempresa.com',
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 16),
                          SizedBox(
                            width: double.infinity,
                            child: FilledButton.icon(
                              onPressed: _saving || _loading ? null : _save,
                              icon: _saving
                                  ? const SizedBox(
                                      width: 14,
                                      height: 14,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                        color: Colors.white,
                                      ),
                                    )
                                  : const Icon(Icons.save_outlined),
                              label: const Text('Guardar plantillas'),
                            ),
                          ),
                        ],
                      ),
                    ),
            ),
          ],
        ),
      ),
    );
  }
}
