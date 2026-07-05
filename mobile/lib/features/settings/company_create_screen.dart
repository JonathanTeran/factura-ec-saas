import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/api/v1_api_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/aurora_background.dart';
import '../../core/widgets/glass_panel.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/company_provider.dart';

/// Alta de una empresa adicional (multi-empresa). El límite de RUCs por plan
/// lo hace cumplir el backend (responde con error si se alcanza el tope).
class CompanyCreateScreen extends ConsumerStatefulWidget {
  const CompanyCreateScreen({super.key});

  @override
  ConsumerState<CompanyCreateScreen> createState() =>
      _CompanyCreateScreenState();
}

class _CompanyCreateScreenState extends ConsumerState<CompanyCreateScreen> {
  final _rucCtrl = TextEditingController();
  final _bnCtrl = TextEditingController();
  final _tnCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _addrCtrl = TextEditingController();
  String _taxpayerType = 'natural';
  String _sriEnv = '1';
  bool _busy = false;
  bool _rucLoading = false;
  String? _error;

  @override
  void dispose() {
    _rucCtrl.dispose();
    _bnCtrl.dispose();
    _tnCtrl.dispose();
    _emailCtrl.dispose();
    _addrCtrl.dispose();
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
      setState(() {
        if (r.businessName.isNotEmpty) _bnCtrl.text = r.businessName;
        _taxpayerType = r.taxpayerType == 'natural' ? 'natural' : 'juridical';
        RucEstablishment? main;
        for (final e in r.establishments) {
          if (e.isMain) {
            main = e;
            break;
          }
        }
        if (_addrCtrl.text.isEmpty && (main?.address ?? '').isNotEmpty) {
          _addrCtrl.text = main!.address!;
        }
        if (_tnCtrl.text.isEmpty && (main?.tradeName ?? '').isNotEmpty) {
          _tnCtrl.text = main!.tradeName!;
        }
      });
      _toast('Datos cargados desde el SRI.');
    } catch (_) {
      _toast('No se pudo consultar el RUC. Ingresa los datos manualmente.');
    } finally {
      if (mounted) setState(() => _rucLoading = false);
    }
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    if (_rucCtrl.text.trim().isEmpty ||
        _bnCtrl.text.trim().isEmpty ||
        _addrCtrl.text.trim().isEmpty ||
        _emailCtrl.text.trim().isEmpty) {
      setState(() => _error = 'Completa los campos obligatorios.');
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref.read(v1ApiServiceProvider).createCompany({
        'ruc': _rucCtrl.text.trim(),
        'business_name': _bnCtrl.text.trim(),
        'trade_name': _tnCtrl.text.trim(),
        'address': _addrCtrl.text.trim(),
        'email': _emailCtrl.text.trim(),
        'taxpayer_type': _taxpayerType,
        'sri_environment': _sriEnv,
      });
      ref.invalidate(companiesProvider);
      if (!mounted) return;
      _toast('Empresa creada.');
      context.pop();
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
    return Scaffold(
      appBar: AppBar(title: const Text('Agregar empresa')),
      body: Stack(
        children: [
          const Positioned.fill(child: AuroraBackground()),
          SafeArea(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(
                    'Administra varios RUCs desde una sola cuenta.',
                    style: textTheme.bodyMedium,
                  ),
                  const SizedBox(height: 14),
                  GlassPanel(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
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
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                      ),
                                    )
                                  : const Text('SRI'),
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        TextField(
                          controller: _bnCtrl,
                          decoration: const InputDecoration(
                            labelText: 'Razón social *',
                          ),
                        ),
                        const SizedBox(height: 10),
                        TextField(
                          controller: _tnCtrl,
                          decoration: const InputDecoration(
                            labelText: 'Nombre comercial',
                          ),
                        ),
                        const SizedBox(height: 10),
                        TextField(
                          controller: _emailCtrl,
                          keyboardType: TextInputType.emailAddress,
                          decoration: const InputDecoration(
                            labelText: 'Correo *',
                          ),
                        ),
                        const SizedBox(height: 10),
                        TextField(
                          controller: _addrCtrl,
                          decoration: const InputDecoration(
                            labelText: 'Dirección matriz *',
                          ),
                        ),
                        const SizedBox(height: 10),
                        DropdownButtonFormField<String>(
                          initialValue: _taxpayerType,
                          decoration: const InputDecoration(
                            labelText: 'Tipo de contribuyente',
                          ),
                          items: const [
                            DropdownMenuItem(
                              value: 'natural',
                              child: Text('Persona natural'),
                            ),
                            DropdownMenuItem(
                              value: 'juridical',
                              child: Text('Sociedad'),
                            ),
                          ],
                          onChanged: (v) =>
                              setState(() => _taxpayerType = v ?? 'natural'),
                        ),
                        const SizedBox(height: 10),
                        DropdownButtonFormField<String>(
                          initialValue: _sriEnv,
                          decoration: const InputDecoration(
                            labelText: 'Ambiente SRI',
                          ),
                          items: const [
                            DropdownMenuItem(
                              value: '1',
                              child: Text('Pruebas'),
                            ),
                            DropdownMenuItem(
                              value: '2',
                              child: Text('Producción'),
                            ),
                          ],
                          onChanged: (v) =>
                              setState(() => _sriEnv = v ?? '1'),
                        ),
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
                  ElevatedButton.icon(
                    onPressed: _busy ? null : _submit,
                    icon: _busy
                        ? const SizedBox(
                            width: 16,
                            height: 16,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.add_business_rounded),
                    label: const Text('Crear empresa'),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
