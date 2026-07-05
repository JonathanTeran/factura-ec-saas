import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/api/v1_api_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/aurora_background.dart';
import '../../core/widgets/glass_panel.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/company_provider.dart';

/// Configuración asistida móvil: empresa + establecimiento. La firma
/// electrónica (.p12) se sube desde el portal web (el móvil no la carga).
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
  bool _obligatedAccounting = false;
  bool _sriLookupDone = false;

  // Establecimiento
  final _brNameCtrl = TextEditingController(text: 'Matriz');
  final _brCodeCtrl = TextEditingController(text: '001');
  final _brAddrCtrl = TextEditingController();
  final _epNameCtrl = TextEditingController(text: 'Punto de emisión principal');
  final _epCodeCtrl = TextEditingController(text: '001');

  @override
  void dispose() {
    _rucCtrl.dispose();
    _bnCtrl.dispose();
    _tnCtrl.dispose();
    _emailCtrl.dispose();
    _addrCtrl.dispose();
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
        _obligatedAccounting = r.obligatedAccounting;
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
            'obligated_accounting': _obligatedAccounting,
          }));
    } else if (_step == 1) {
      if (_brAddrCtrl.text.trim().isEmpty) {
        setState(() => _error = 'Ingresa la dirección del establecimiento.');
        return;
      }
      await _run(() =>
          ref.read(v1ApiServiceProvider).saveOnboardingEstablishment({
            'name': _brNameCtrl.text.trim(),
            'code': _brCodeCtrl.text.trim(),
            'address': _brAddrCtrl.text.trim(),
            'ep_code': _epCodeCtrl.text.trim(),
            'ep_name': _epNameCtrl.text.trim(),
            'import_sri_establishments': _sriLookupDone,
          }));
    }
  }

  Future<void> _run(Future<void> Function() action) async {
    setState(() => _busy = true);
    try {
      await action();
      if (!mounted) return;
      setState(() => _step = (_step + 1).clamp(0, 2));
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
      'Establecimiento',
      '¡Todo listo!',
    ];
    const subtitles = [
      'Los datos que el SRI usa para identificar tus comprobantes.',
      'Tu matriz y el punto desde donde emitirás.',
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
                      Text('Paso ${_step + 1} de 3', style: textTheme.bodyMedium),
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
                        if (_step == 1) ..._establishmentFields(),
                        if (_step == 2) ..._doneContent(textTheme),
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
                      if (_step > 0 && _step < 2)
                        OutlinedButton(
                          onPressed: _busy
                              ? null
                              : () => setState(() => _step -= 1),
                          child: const Text('Atrás'),
                        ),
                      const Spacer(),
                      ElevatedButton.icon(
                        onPressed: _busy
                            ? null
                            : (_step == 2 ? _finish : _next),
                        icon: _busy
                            ? const SizedBox(
                                width: 16,
                                height: 16,
                                child:
                                    CircularProgressIndicator(strokeWidth: 2),
                              )
                            : const Icon(Icons.arrow_forward_rounded),
                        label: Text(_step == 2 ? 'Ir al panel' : 'Continuar'),
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

  List<Widget> _doneContent(TextTheme textTheme) {
    return [
      const Icon(Icons.check_circle_rounded, size: 56, color: AppColors.success),
      const SizedBox(height: 12),
      Text(
        'Tu empresa quedó configurada.',
        style: textTheme.titleMedium,
        textAlign: TextAlign.center,
      ),
      const SizedBox(height: 8),
      Text(
        'Para emitir tus comprobantes, sube tu firma electrónica (.p12) desde '
        'el portal web en facturacion.amephia.com. Luego podrás facturar '
        'desde la app.',
        style: textTheme.bodyMedium,
        textAlign: TextAlign.center,
      ),
    ];
  }
}

class _StepBar extends StatelessWidget {
  final int step;
  const _StepBar({required this.step});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: List.generate(3, (i) {
        final active = i <= step;
        return Expanded(
          child: Container(
            height: 4,
            margin: EdgeInsets.only(right: i < 2 ? 6 : 0),
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
