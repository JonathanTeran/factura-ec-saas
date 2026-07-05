import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/api/v1_api_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/aurora_background.dart';
import '../../core/widgets/glass_panel.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/company_provider.dart';

const _docTypes = [
  {'code': '01', 'label': 'Factura'},
  {'code': '04', 'label': 'Nota de crédito'},
  {'code': '05', 'label': 'Nota de débito'},
  {'code': '06', 'label': 'Guía de remisión'},
  {'code': '07', 'label': 'Retención'},
  {'code': '03', 'label': 'Liquidación de compra'},
];

/// Configuración asistida móvil (paridad con la web): empresa, firma
/// electrónica (.p12), establecimiento y secuenciales de migración.
class OnboardingScreen extends ConsumerStatefulWidget {
  const OnboardingScreen({super.key});

  @override
  ConsumerState<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends ConsumerState<OnboardingScreen> {
  int _step = 0;
  bool _busy = false;
  bool _rucLoading = false;
  String? _error;

  // Empresa
  final _rucCtrl = TextEditingController();
  final _bnCtrl = TextEditingController();
  final _tnCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _addrCtrl = TextEditingController();
  String _taxpayerType = 'natural';
  String _sriEnv = '1';
  bool _sriLookupDone = false;

  // Firma
  String? _certPath;
  String? _certName;
  final _certPwdCtrl = TextEditingController();
  OnboardingCertInfo? _certInfo;

  // Establecimiento
  final _brNameCtrl = TextEditingController(text: 'Matriz');
  final _brCodeCtrl = TextEditingController(text: '001');
  final _brAddrCtrl = TextEditingController();
  final _epNameCtrl = TextEditingController(text: 'Punto de emisión principal');
  final _epCodeCtrl = TextEditingController(text: '001');
  int? _emissionPointId;

  // Secuenciales
  bool? _migrated;
  final Map<String, String> _seq = {};

  @override
  void dispose() {
    _rucCtrl.dispose();
    _bnCtrl.dispose();
    _tnCtrl.dispose();
    _emailCtrl.dispose();
    _addrCtrl.dispose();
    _certPwdCtrl.dispose();
    _brNameCtrl.dispose();
    _brCodeCtrl.dispose();
    _brAddrCtrl.dispose();
    _epNameCtrl.dispose();
    _epCodeCtrl.dispose();
    super.dispose();
  }

  void _toast(String msg) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(msg)));
  }

  Future<void> _lookupRuc() async {
    final ruc = _rucCtrl.text.trim();
    if (!RegExp(r'^[0-9]{13}$').hasMatch(ruc)) {
      _toast('El RUC debe tener 13 dígitos numéricos.');
      return;
    }
    setState(() => _rucLoading = true);
    try {
      final r = await ref.read(v1ApiServiceProvider).lookupRuc(ruc);
      RucEstablishment? main;
      for (final e in r.establishments) {
        if (e.isMain) {
          main = e;
          break;
        }
      }
      setState(() {
        if (r.businessName.isNotEmpty) _bnCtrl.text = r.businessName;
        _taxpayerType = r.taxpayerType == 'natural' ? 'natural' : 'sociedad';
        if (_tnCtrl.text.isEmpty && (main?.tradeName ?? '').isNotEmpty) {
          _tnCtrl.text = main!.tradeName!;
        }
        if (_addrCtrl.text.isEmpty && (main?.address ?? '').isNotEmpty) {
          _addrCtrl.text = main!.address!;
        }
        if (main != null) {
          _brCodeCtrl.text = main.code;
          if ((main.tradeName ?? '').isNotEmpty) {
            _brNameCtrl.text = main.tradeName!;
          }
          if ((main.address ?? '').isNotEmpty) {
            _brAddrCtrl.text = main.address!;
          }
        }
        _sriLookupDone = true;
      });
      _toast(
        r.status == 'ACTIVO'
            ? 'Datos cargados desde el SRI (RUC activo).'
            : 'El SRI reporta este RUC como ${r.status}.',
      );
    } catch (_) {
      _toast('No se pudo consultar el RUC. Ingresa los datos manualmente.');
    } finally {
      if (mounted) setState(() => _rucLoading = false);
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
        });
      }
    } catch (_) {
      _toast('No se pudo abrir el selector de archivos.');
    }
  }

  Future<void> _validateCert() async {
    if (_certPath == null || _certPwdCtrl.text.isEmpty) {
      setState(() => _error = 'Selecciona tu certificado e ingresa la contraseña.');
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final info = await ref.read(v1ApiServiceProvider).uploadOnboardingCertificate(
            filePath: _certPath!,
            password: _certPwdCtrl.text,
          );
      setState(() => _certInfo = info);
      _toast(
        info.daysUntilExpiry <= 30
            ? 'Firma válida. Vence en ${info.daysUntilExpiry} días.'
            : 'Certificado validado.',
      );
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _next() async {
    FocusScope.of(context).unfocus();
    setState(() => _error = null);

    if (_step == 0) {
      if (_rucCtrl.text.trim().isEmpty ||
          _bnCtrl.text.trim().isEmpty ||
          _addrCtrl.text.trim().isEmpty ||
          _emailCtrl.text.trim().isEmpty) {
        setState(() => _error = 'Completa los campos obligatorios.');
        return;
      }
      await _run(() => ref.read(v1ApiServiceProvider).saveOnboardingCompany({
            'ruc': _rucCtrl.text.trim(),
            'business_name': _bnCtrl.text.trim(),
            'trade_name': _tnCtrl.text.trim(),
            'address': _addrCtrl.text.trim(),
            'email': _emailCtrl.text.trim(),
            'taxpayer_type': _taxpayerType,
            'sri_environment': _sriEnv,
            'obligated_accounting': false,
          }));
    } else if (_step == 1) {
      // Firma: si no está validada aún y hay archivo+clave, valida y quédate.
      if (_certInfo == null && _certPath != null) {
        await _validateCert();
        return;
      }
      setState(() => _step = 2);
    } else if (_step == 2) {
      if (_brAddrCtrl.text.trim().isEmpty) {
        setState(() => _error = 'Ingresa la dirección del establecimiento.');
        return;
      }
      await _run(() async {
        _emissionPointId = await ref
            .read(v1ApiServiceProvider)
            .saveOnboardingEstablishment({
          'name': _brNameCtrl.text.trim(),
          'code': _brCodeCtrl.text.trim(),
          'address': _brAddrCtrl.text.trim(),
          'ep_code': _epCodeCtrl.text.trim(),
          'ep_name': _epNameCtrl.text.trim(),
          'import_sri_establishments': _sriLookupDone,
        });
      });
    } else if (_step == 3) {
      if (_migrated == true && _emissionPointId != null) {
        final items = <Map<String, dynamic>>[];
        for (final d in _docTypes) {
          final n = int.tryParse(_seq[d['code']] ?? '') ?? 0;
          if (n > 0) {
            items.add({'document_type': d['code'], 'last_number': n});
          }
        }
        await _run(() => ref.read(v1ApiServiceProvider).saveOnboardingSequentials(
              emissionPointId: _emissionPointId!,
              sequentials: items,
            ));
      } else {
        setState(() => _step = 4);
      }
    }
  }

  Future<void> _run(Future<void> Function() action) async {
    setState(() => _busy = true);
    try {
      await action();
      if (!mounted) return;
      setState(() => _step = (_step + 1).clamp(0, 4));
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _finish() async {
    setState(() => _busy = true);
    try {
      await ref.read(v1ApiServiceProvider).completeOnboarding();
      ref.invalidate(companiesProvider);
      ref.invalidate(meProvider);
      if (!mounted) return;
      context.go('/');
    } catch (e) {
      setState(() {
        _error = e.toString();
        _busy = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;
    const titles = [
      'Datos de tu empresa',
      'Firma electrónica',
      'Establecimiento',
      '¿Ya facturabas antes?',
      '¡Todo listo!',
    ];
    const subtitles = [
      'Los datos que el SRI usa para identificar tus comprobantes.',
      'Sube tu certificado .p12. Puedes hacerlo más tarde.',
      'Tu matriz y el punto desde donde emitirás.',
      'Si ya emitías en otro sistema, continuamos tu numeración.',
      'Tu cuenta quedó configurada.',
    ];

    return Scaffold(
      body: Stack(
        children: [
          const Positioned.fill(child: AuroraBackground()),
          SafeArea(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(20, 24, 20, 24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Text('Paso ${_step + 1} de 5', style: textTheme.bodyMedium),
                      const Spacer(),
                      TextButton(
                        onPressed: _busy ? null : () => context.go('/'),
                        child: const Text('Completar más tarde'),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  _StepBar(step: _step),
                  const SizedBox(height: 18),
                  Text(titles[_step], style: textTheme.displaySmall),
                  const SizedBox(height: 6),
                  Text(subtitles[_step], style: textTheme.bodyMedium),
                  const SizedBox(height: 16),
                  GlassPanel(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        if (_step == 0) ..._companyFields(),
                        if (_step == 1) ..._certFields(textTheme),
                        if (_step == 2) ..._establishmentFields(),
                        if (_step == 3) ..._sequentialFields(textTheme),
                        if (_step == 4) ..._doneContent(textTheme),
                        if (_error != null) ...[
                          const SizedBox(height: 12),
                          Text(
                            _error!,
                            style: const TextStyle(
                              color: AppColors.error,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      if (_step > 0 && _step < 4)
                        OutlinedButton(
                          onPressed: _busy
                              ? null
                              : () => setState(() => _step -= 1),
                          child: const Text('Atrás'),
                        ),
                      const Spacer(),
                      if (_step == 1 && _certInfo == null)
                        TextButton(
                          onPressed: _busy ? null : () => setState(() => _step = 2),
                          child: const Text('Configurar más tarde'),
                        ),
                      const SizedBox(width: 8),
                      ElevatedButton.icon(
                        onPressed:
                            _busy ? null : (_step == 4 ? _finish : _next),
                        icon: _busy
                            ? const SizedBox(
                                width: 16,
                                height: 16,
                                child:
                                    CircularProgressIndicator(strokeWidth: 2),
                              )
                            : const Icon(Icons.arrow_forward_rounded),
                        label: Text(
                          _step == 4
                              ? 'Ir al panel'
                              : (_step == 1 && _certInfo == null && _certPath != null
                                  ? 'Validar firma'
                                  : 'Continuar'),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  List<Widget> _companyFields() {
    return [
      Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          Expanded(
            child: TextField(
              controller: _rucCtrl,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'RUC *',
                hintText: '1790012345001',
              ),
            ),
          ),
          const SizedBox(width: 8),
          OutlinedButton(
            onPressed: _rucLoading ? null : _lookupRuc,
            child: _rucLoading
                ? const SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Text('SRI'),
          ),
        ],
      ),
      const SizedBox(height: 10),
      TextField(
        controller: _bnCtrl,
        decoration: const InputDecoration(labelText: 'Razón social *'),
      ),
      const SizedBox(height: 10),
      TextField(
        controller: _tnCtrl,
        decoration: const InputDecoration(labelText: 'Nombre comercial'),
      ),
      const SizedBox(height: 10),
      TextField(
        controller: _emailCtrl,
        keyboardType: TextInputType.emailAddress,
        decoration: const InputDecoration(labelText: 'Correo *'),
      ),
      const SizedBox(height: 10),
      TextField(
        controller: _addrCtrl,
        decoration: const InputDecoration(labelText: 'Dirección matriz *'),
      ),
      const SizedBox(height: 10),
      DropdownButtonFormField<String>(
        initialValue: _taxpayerType,
        decoration: const InputDecoration(labelText: 'Tipo de contribuyente'),
        items: const [
          DropdownMenuItem(value: 'natural', child: Text('Persona natural')),
          DropdownMenuItem(value: 'sociedad', child: Text('Sociedad')),
        ],
        onChanged: (v) => setState(() => _taxpayerType = v ?? 'natural'),
      ),
      const SizedBox(height: 10),
      DropdownButtonFormField<String>(
        initialValue: _sriEnv,
        decoration: const InputDecoration(labelText: 'Ambiente SRI'),
        items: const [
          DropdownMenuItem(value: '1', child: Text('Pruebas')),
          DropdownMenuItem(value: '2', child: Text('Producción')),
        ],
        onChanged: (v) => setState(() => _sriEnv = v ?? '1'),
      ),
    ];
  }

  List<Widget> _certFields(TextTheme textTheme) {
    if (_certInfo != null) {
      final warn = _certInfo!.daysUntilExpiry <= 30;
      return [
        Icon(
          warn ? Icons.warning_amber_rounded : Icons.verified_rounded,
          size: 44,
          color: warn ? AppColors.warning : AppColors.success,
        ),
        const SizedBox(height: 10),
        Text(
          warn
              ? 'Tu firma vence en ${_certInfo!.daysUntilExpiry} días'
              : 'Certificado válido',
          style: textTheme.titleMedium,
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 6),
        Text(
          _certInfo!.subject,
          style: textTheme.bodyMedium,
          textAlign: TextAlign.center,
        ),
      ];
    }
    return [
      OutlinedButton.icon(
        onPressed: _busy ? null : _pickCert,
        icon: const Icon(Icons.upload_file_rounded),
        label: Text(_certName ?? 'Seleccionar certificado (.p12)'),
      ),
      const SizedBox(height: 12),
      TextField(
        controller: _certPwdCtrl,
        obscureText: true,
        decoration: const InputDecoration(
          labelText: 'Contraseña del certificado',
        ),
      ),
    ];
  }

  List<Widget> _establishmentFields() {
    return [
      TextField(
        controller: _brNameCtrl,
        decoration:
            const InputDecoration(labelText: 'Nombre del establecimiento'),
      ),
      const SizedBox(height: 10),
      TextField(
        controller: _brCodeCtrl,
        decoration: const InputDecoration(labelText: 'Código (normalmente 001)'),
      ),
      const SizedBox(height: 10),
      TextField(
        controller: _brAddrCtrl,
        decoration: const InputDecoration(
          labelText: 'Dirección del establecimiento *',
        ),
      ),
      const SizedBox(height: 10),
      TextField(
        controller: _epNameCtrl,
        decoration: const InputDecoration(labelText: 'Punto de emisión'),
      ),
      const SizedBox(height: 10),
      TextField(
        controller: _epCodeCtrl,
        decoration:
            const InputDecoration(labelText: 'Código punto (normalmente 001)'),
      ),
    ];
  }

  List<Widget> _sequentialFields(TextTheme textTheme) {
    return [
      Row(
        children: [
          Expanded(
            child: _ChoiceCard(
              title: 'Es mi primera vez',
              subtitle: 'Empezamos en 000000001.',
              selected: _migrated == false,
              onTap: () => setState(() => _migrated = false),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: _ChoiceCard(
              title: 'Ya facturaba antes',
              subtitle: 'Vengo de otro sistema.',
              selected: _migrated == true,
              onTap: () => setState(() => _migrated = true),
            ),
          ),
        ],
      ),
      if (_migrated == true) ...[
        const SizedBox(height: 12),
        Text(
          'Ingresa el último número emitido por tipo (deja vacío si no lo usabas).',
          style: textTheme.bodyMedium,
        ),
        const SizedBox(height: 8),
        ..._docTypes.map((d) {
          return Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: Row(
              children: [
                Expanded(
                  flex: 2,
                  child: Text(d['label']!, style: textTheme.bodyMedium),
                ),
                Expanded(
                  flex: 3,
                  child: TextField(
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(hintText: 'Ej. 1234'),
                    onChanged: (v) => _seq[d['code']!] = v,
                  ),
                ),
              ],
            ),
          );
        }),
      ],
    ];
  }

  List<Widget> _doneContent(TextTheme textTheme) {
    return [
      const Icon(Icons.check_circle_rounded, size: 56, color: AppColors.success),
      const SizedBox(height: 12),
      Text(
        'Tu cuenta quedó configurada.',
        style: textTheme.titleMedium,
        textAlign: TextAlign.center,
      ),
      const SizedBox(height: 8),
      Text(
        'Ya puedes emitir tu primera factura electrónica desde la app.',
        style: textTheme.bodyMedium,
        textAlign: TextAlign.center,
      ),
    ];
  }
}

class _ChoiceCard extends StatelessWidget {
  final String title;
  final String subtitle;
  final bool selected;
  final VoidCallback onTap;

  const _ChoiceCard({
    required this.title,
    required this.subtitle,
    required this.selected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(14),
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(14),
          color: selected
              ? AppColors.primary.withValues(alpha: 0.12)
              : Colors.white.withValues(alpha: 0.03),
          border: Border.all(
            color: selected
                ? AppColors.primary.withValues(alpha: 0.6)
                : Colors.white.withValues(alpha: 0.1),
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: const TextStyle(
                fontFamily: 'Avenir Next',
                fontWeight: FontWeight.w700,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              subtitle,
              style: const TextStyle(
                fontFamily: 'Avenir Next',
                fontSize: 12,
                color: AppColors.textSecondary,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _StepBar extends StatelessWidget {
  final int step;
  const _StepBar({required this.step});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: List.generate(5, (i) {
        final active = i <= step;
        return Expanded(
          child: Container(
            height: 4,
            margin: EdgeInsets.only(right: i < 4 ? 5 : 0),
            decoration: BoxDecoration(
              color: active
                  ? AppColors.primary
                  : Colors.white.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(2),
            ),
          ),
        );
      }),
    );
  }
}
