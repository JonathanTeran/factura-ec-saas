import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../core/api/v1_api_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/page_header.dart';
import '../../data/providers/auth_provider.dart';

/// Configuración de la firma electrónica (certificado .p12), como en la web.
class CertificateScreen extends ConsumerStatefulWidget {
  const CertificateScreen({super.key});

  @override
  ConsumerState<CertificateScreen> createState() => _CertificateScreenState();
}

class _CertificateScreenState extends ConsumerState<CertificateScreen> {
  final _pwdCtrl = TextEditingController();
  SignatureStatus? _status;
  bool _loading = true;
  bool _uploading = false;
  String? _certPath;
  String? _certName;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadStatus();
  }

  @override
  void dispose() {
    _pwdCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadStatus() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final status = await ref.read(v1ApiServiceProvider).signatureStatus();
      if (!mounted) return;
      setState(() => _status = status);
    } catch (_) {
      // Mostramos el bloque de carga igual; el usuario puede subir el .p12.
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _pickCert() async {
    try {
      final result = await FilePicker.pickFiles(
        type: FileType.custom,
        allowedExtensions: ['p12', 'pfx'],
      );
      final path = result?.files.single.path;
      if (path != null) {
        setState(() {
          _certPath = path;
          _certName = result!.files.single.name;
          _error = null;
        });
      }
    } catch (_) {
      setState(() => _error = 'No se pudo abrir el selector de archivos.');
    }
  }

  Future<void> _upload() async {
    if (_certPath == null || _pwdCtrl.text.isEmpty) {
      setState(
        () => _error = 'Elegí el archivo .p12 y escribí su contraseña.',
      );
      return;
    }
    setState(() {
      _uploading = true;
      _error = null;
    });
    try {
      await ref
          .read(v1ApiServiceProvider)
          .uploadOnboardingCertificate(
            filePath: _certPath!,
            password: _pwdCtrl.text,
          );
      if (!mounted) return;
      _pwdCtrl.clear();
      setState(() {
        _certPath = null;
        _certName = null;
      });
      await _loadStatus();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Firma electrónica actualizada.')),
        );
      }
    } catch (e) {
      setState(
        () => _error =
            'No se pudo validar el certificado. Revisá el archivo y la contraseña.',
      );
    } finally {
      if (mounted) setState(() => _uploading = false);
    }
  }

  ({String label, Color color, IconData icon}) _statusVisual(String s) {
    switch (s) {
      case 'valid':
        return (
          label: 'Firma vigente',
          color: AppColors.success,
          icon: Icons.verified_rounded,
        );
      case 'expiring_soon':
        return (
          label: 'Vence pronto',
          color: AppColors.warning,
          icon: Icons.schedule_rounded,
        );
      case 'expired':
        return (
          label: 'Firma vencida',
          color: AppColors.error,
          icon: Icons.error_outline_rounded,
        );
      case 'file_missing':
        return (
          label: 'Falta el archivo del certificado',
          color: AppColors.error,
          icon: Icons.report_problem_rounded,
        );
      default:
        return (
          label: 'Sin firma configurada',
          color: AppColors.textMuted,
          icon: Icons.gpp_maybe_outlined,
        );
    }
  }

  @override
  Widget build(BuildContext context) {
    final status = _status;
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            PageHeader(
              title: 'Firma electrónica',
              subtitle: 'Certificado .p12 para firmar los comprobantes',
              trailing: IconButton.filledTonal(
                tooltip: 'Volver',
                onPressed: () => context.pop(),
                icon: const Icon(Icons.close_rounded),
              ),
            ),
            const SizedBox(height: 12),
            Expanded(
              child: SingleChildScrollView(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Estado actual
                    if (_loading)
                      const GlassPanel(
                        child: Center(
                          child: Padding(
                            padding: EdgeInsets.all(16),
                            child: CircularProgressIndicator(strokeWidth: 2.4),
                          ),
                        ),
                      )
                    else
                      _statusCard(status),
                    const SizedBox(height: 18),
                    // Subir / actualizar
                    Text(
                      status != null && status.hasCertificate
                          ? 'Actualizar firma'
                          : 'Configurar firma',
                      style: const TextStyle(
                        fontFamily: 'Avenir Next',
                        fontWeight: FontWeight.w700,
                        fontSize: 16,
                        color: AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 10),
                    GlassPanel(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          OutlinedButton.icon(
                            onPressed: _uploading ? null : _pickCert,
                            icon: const Icon(Icons.attach_file_rounded, size: 18),
                            label: Text(
                              _certName ?? 'Elegir archivo .p12',
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          const SizedBox(height: 12),
                          TextField(
                            controller: _pwdCtrl,
                            obscureText: true,
                            decoration: const InputDecoration(
                              labelText: 'Contraseña del certificado',
                              prefixIcon: Icon(Icons.lock_outline_rounded),
                            ),
                          ),
                          if (_error != null) ...[
                            const SizedBox(height: 10),
                            Text(
                              _error!,
                              style: const TextStyle(
                                fontFamily: 'Avenir Next',
                                color: AppColors.error,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                          const SizedBox(height: 14),
                          SizedBox(
                            width: double.infinity,
                            child: ElevatedButton.icon(
                              onPressed: _uploading ? null : _upload,
                              icon: _uploading
                                  ? const SizedBox(
                                      width: 18,
                                      height: 18,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                      ),
                                    )
                                  : const Icon(Icons.cloud_upload_rounded),
                              label: Text(
                                _uploading ? 'Validando...' : 'Guardar firma',
                              ),
                              style: ElevatedButton.styleFrom(
                                minimumSize: const Size.fromHeight(52),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 14),
                    const Text(
                      'Tu certificado se guarda cifrado. El SRI lo exige para '
                      'firmar y autorizar cada comprobante.',
                      style: TextStyle(
                        fontFamily: 'Avenir Next',
                        color: AppColors.textMuted,
                        fontSize: 12,
                        height: 1.4,
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

  Widget _statusCard(SignatureStatus? status) {
    final v = _statusVisual(status?.status ?? 'missing');
    return GlassPanel(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(v.icon, color: v.color, size: 26),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  v.label,
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    fontWeight: FontWeight.w700,
                    fontSize: 16,
                    color: AppColors.textPrimary,
                  ),
                ),
              ),
            ],
          ),
          if ((status?.message ?? '').isNotEmpty) ...[
            const SizedBox(height: 10),
            Text(
              status!.message!,
              style: TextStyle(
                fontFamily: 'Avenir Next',
                color: v.color,
                fontWeight: FontWeight.w600,
                fontSize: 13,
                height: 1.35,
              ),
            ),
          ],
          if (status != null && status.hasCertificate) ...[
            const SizedBox(height: 12),
            _row('Titular', status.subject ?? '—'),
            if (status.expiresAt != null)
              _row('Válido hasta', DateFormat('dd/MM/yyyy').format(status.expiresAt!)),
            if (status.daysRemaining != null)
              _row('Días restantes', '${status.daysRemaining}'),
          ],
        ],
      ),
    );
  }

  Widget _row(String label, String value) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 5),
    child: Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: const TextStyle(
            fontFamily: 'Avenir Next',
            color: AppColors.textSecondary,
          ),
        ),
        Flexible(
          child: Text(
            value,
            textAlign: TextAlign.right,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              fontFamily: 'Avenir Next',
              fontWeight: FontWeight.w600,
              color: AppColors.textPrimary,
            ),
          ),
        ),
      ],
    ),
  );
}
