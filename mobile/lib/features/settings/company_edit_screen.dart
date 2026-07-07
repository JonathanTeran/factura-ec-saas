import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';

import '../../core/api/v1_api_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/aurora_background.dart';
import '../../core/widgets/glass_panel.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/company_provider.dart';

/// Edición de los datos de la empresa activa: razón social, nombre comercial,
/// dirección, régimen, obligado a contabilidad, contacto y logo.
class CompanyEditScreen extends ConsumerStatefulWidget {
  final ApiCompany? company;

  const CompanyEditScreen({super.key, this.company});

  @override
  ConsumerState<CompanyEditScreen> createState() => _CompanyEditScreenState();
}

class _CompanyEditScreenState extends ConsumerState<CompanyEditScreen> {
  ApiCompany? _company;

  final _bnCtrl = TextEditingController();
  final _tnCtrl = TextEditingController();
  final _addrCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();

  String _taxpayerType = 'natural';
  String _rimpeType = 'none';
  bool _obligatedAccounting = false;
  bool _specialTaxpayer = false;

  String? _logoUrl;
  bool _busy = false;
  bool _uploadingLogo = false;
  bool _lookingUp = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    if (widget.company != null) _prefill(widget.company!);
  }

  void _prefill(ApiCompany c) {
    _company = c;
    _bnCtrl.text = c.businessName;
    _tnCtrl.text = c.tradeName;
    _addrCtrl.text = c.address;
    _emailCtrl.text = c.email;
    _phoneCtrl.text = c.phone;
    _taxpayerType = const {'natural', 'juridical', 'rise'}.contains(c.taxpayerType)
        ? c.taxpayerType
        : 'natural';
    _rimpeType =
        const {'none', 'emprendedor', 'negocio_popular'}.contains(c.rimpeType)
            ? c.rimpeType
            : 'none';
    _obligatedAccounting = c.obligatedAccounting;
    _specialTaxpayer = c.specialTaxpayer;
    _logoUrl = c.logoUrl;
  }

  @override
  void dispose() {
    _bnCtrl.dispose();
    _tnCtrl.dispose();
    _addrCtrl.dispose();
    _emailCtrl.dispose();
    _phoneCtrl.dispose();
    super.dispose();
  }

  void _toast(String msg) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(msg)));
  }

  /// Trae los datos de la empresa desde el catastro del SRI y autocompleta el
  /// formulario (razón social, dirección, régimen, obligado a contabilidad…).
  Future<void> _lookupFromSri() async {
    final company = _company;
    if (company == null || _lookingUp) return;
    setState(() => _lookingUp = true);
    try {
      final r = await ref.read(v1ApiServiceProvider).lookupRuc(company.ruc);
      RucEstablishment? main;
      for (final e in r.establishments) {
        if (e.isMain) {
          main = e;
          break;
        }
      }
      if (!mounted) return;
      setState(() {
        if (r.businessName.isNotEmpty) _bnCtrl.text = r.businessName;
        if ((main?.tradeName ?? '').isNotEmpty) _tnCtrl.text = main!.tradeName!;
        if ((main?.address ?? '').isNotEmpty) _addrCtrl.text = main!.address!;
        if (r.taxpayerType == 'natural' || r.taxpayerType == 'juridical') {
          _taxpayerType = r.taxpayerType;
        }
        _rimpeType = rimpeTypeFromRegime(r.regime);
        _obligatedAccounting = r.obligatedAccounting;
      });
      _toast('Datos traídos del SRI.');
    } catch (_) {
      _toast('No se pudo consultar el SRI. Completá los datos manualmente.');
    } finally {
      if (mounted) setState(() => _lookingUp = false);
    }
  }

  Future<void> _pickLogo() async {
    final company = _company;
    if (company == null || _uploadingLogo) return;
    final picker = ImagePicker();
    final file = await picker.pickImage(
      source: ImageSource.gallery,
      maxWidth: 1024,
      imageQuality: 85,
    );
    if (file == null) return;

    setState(() => _uploadingLogo = true);
    try {
      final url =
          await ref.read(v1ApiServiceProvider).uploadCompanyLogo(company.id, file.path);
      ref.invalidate(companiesProvider);
      if (!mounted) return;
      setState(() => _logoUrl = url);
      _toast('Logo actualizado.');
    } catch (e) {
      _toast(e is ApiException ? e.message : 'No se pudo subir el logo.');
    } finally {
      if (mounted) setState(() => _uploadingLogo = false);
    }
  }

  Future<void> _save() async {
    final company = _company;
    if (company == null) return;
    FocusScope.of(context).unfocus();

    if (_bnCtrl.text.trim().isEmpty ||
        _addrCtrl.text.trim().isEmpty ||
        _emailCtrl.text.trim().isEmpty) {
      setState(() => _error = 'Completá razón social, dirección y correo.');
      return;
    }
    if (!RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$')
        .hasMatch(_emailCtrl.text.trim())) {
      setState(() => _error = 'Ingresá un correo electrónico válido.');
      return;
    }

    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final updated = await ref.read(v1ApiServiceProvider).updateCompany(
        company.id,
        {
          'ruc': company.ruc,
          'business_name': _bnCtrl.text.trim(),
          'trade_name': _tnCtrl.text.trim(),
          'address': _addrCtrl.text.trim(),
          'email': _emailCtrl.text.trim(),
          'phone': _phoneCtrl.text.trim(),
          'taxpayer_type': _taxpayerType,
          'rimpe_type': _rimpeType,
          'obligated_accounting': _obligatedAccounting,
          'special_taxpayer': _specialTaxpayer,
          // El ambiente se mantiene (se cambia desde su propio control).
          'sri_environment': company.sriEnvironment,
        },
      );
      ref.invalidate(companiesProvider);
      ref.invalidate(meProvider);
      if (!mounted) return;
      _company = updated;
      _toast('Datos de la empresa guardados.');
      context.pop();
    } catch (e) {
      setState(() {
        _error = e is ApiException ? e.message : e.toString();
        _busy = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    // Si no llegó por `extra`, resolvemos la empresa activa desde los providers.
    if (_company == null) {
      final companies = ref.watch(companiesProvider).valueOrNull ?? const [];
      final currentId = ref.watch(meProvider).valueOrNull?.currentCompanyId;
      if (companies.isNotEmpty) {
        final c = companies.firstWhere(
          (e) => e.id == currentId,
          orElse: () => companies.first,
        );
        _prefill(c);
      }
    }

    if (_company == null) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      );
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Datos de la empresa')),
      body: Stack(
        children: [
          const Positioned.fill(child: AuroraBackground()),
          SafeArea(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(20, 12, 20, 28),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _LogoRow(
                    logoUrl: _logoUrl,
                    uploading: _uploadingLogo,
                    onTap: _pickLogo,
                  ),
                  const SizedBox(height: 14),
                  GlassPanel(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: _ReadOnlyRow(
                                label: 'RUC',
                                value: _company!.ruc,
                              ),
                            ),
                            OutlinedButton.icon(
                              onPressed: _lookingUp ? null : _lookupFromSri,
                              icon: _lookingUp
                                  ? const SizedBox(
                                      width: 14,
                                      height: 14,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                      ),
                                    )
                                  : const Icon(Icons.cloud_download_outlined,
                                      size: 18),
                              label: const Text('Traer del SRI'),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        TextField(
                          controller: _bnCtrl,
                          decoration: const InputDecoration(
                            labelText: 'Razón social *',
                          ),
                        ),
                        const SizedBox(height: 12),
                        TextField(
                          controller: _tnCtrl,
                          decoration: const InputDecoration(
                            labelText: 'Nombre comercial',
                          ),
                        ),
                        const SizedBox(height: 12),
                        TextField(
                          controller: _addrCtrl,
                          decoration: const InputDecoration(
                            labelText: 'Dirección matriz *',
                          ),
                        ),
                        const SizedBox(height: 12),
                        TextField(
                          controller: _emailCtrl,
                          keyboardType: TextInputType.emailAddress,
                          decoration: const InputDecoration(
                            labelText: 'Correo *',
                          ),
                        ),
                        const SizedBox(height: 12),
                        TextField(
                          controller: _phoneCtrl,
                          keyboardType: TextInputType.phone,
                          decoration: const InputDecoration(
                            labelText: 'Teléfono',
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),
                  GlassPanel(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        const Text(
                          'Régimen tributario',
                          style: TextStyle(
                            fontFamily: 'Avenir Next',
                            fontWeight: FontWeight.w800,
                            fontSize: 15,
                            color: AppColors.textPrimary,
                          ),
                        ),
                        const SizedBox(height: 12),
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
                            DropdownMenuItem(
                              value: 'rise',
                              child: Text('RISE'),
                            ),
                          ],
                          onChanged: (v) =>
                              setState(() => _taxpayerType = v ?? 'natural'),
                        ),
                        const SizedBox(height: 12),
                        DropdownButtonFormField<String>(
                          initialValue: _rimpeType,
                          decoration: const InputDecoration(
                            labelText: 'Régimen RIMPE',
                          ),
                          items: const [
                            DropdownMenuItem(
                              value: 'none',
                              child: Text('Régimen general'),
                            ),
                            DropdownMenuItem(
                              value: 'emprendedor',
                              child: Text('RIMPE Emprendedor'),
                            ),
                            DropdownMenuItem(
                              value: 'negocio_popular',
                              child: Text('RIMPE Negocio Popular'),
                            ),
                          ],
                          onChanged: (v) =>
                              setState(() => _rimpeType = v ?? 'none'),
                        ),
                        const SizedBox(height: 4),
                        SwitchListTile.adaptive(
                          contentPadding: EdgeInsets.zero,
                          title: const Text(
                            'Obligado a llevar contabilidad',
                            style: TextStyle(
                              fontFamily: 'Avenir Next',
                              fontWeight: FontWeight.w600,
                              fontSize: 14,
                              color: AppColors.textPrimary,
                            ),
                          ),
                          value: _obligatedAccounting,
                          onChanged: (v) =>
                              setState(() => _obligatedAccounting = v),
                        ),
                        SwitchListTile.adaptive(
                          contentPadding: EdgeInsets.zero,
                          title: const Text(
                            'Contribuyente especial',
                            style: TextStyle(
                              fontFamily: 'Avenir Next',
                              fontWeight: FontWeight.w600,
                              fontSize: 14,
                              color: AppColors.textPrimary,
                            ),
                          ),
                          value: _specialTaxpayer,
                          onChanged: (v) =>
                              setState(() => _specialTaxpayer = v),
                        ),
                      ],
                    ),
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
                  const SizedBox(height: 18),
                  ElevatedButton.icon(
                    onPressed: _busy ? null : _save,
                    icon: _busy
                        ? const SizedBox(
                            width: 16,
                            height: 16,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.save_rounded),
                    label: const Text('Guardar cambios'),
                    style: ElevatedButton.styleFrom(
                      minimumSize: const Size.fromHeight(52),
                    ),
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

class _LogoRow extends StatelessWidget {
  final String? logoUrl;
  final bool uploading;
  final VoidCallback onTap;

  const _LogoRow({
    required this.logoUrl,
    required this.uploading,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GlassPanel(
      child: Row(
        children: [
          Container(
            width: 64,
            height: 64,
            decoration: BoxDecoration(
              color: AppColors.surfaceDark,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: AppColors.border),
            ),
            clipBehavior: Clip.antiAlias,
            child: (logoUrl != null && logoUrl!.isNotEmpty)
                ? Image.network(
                    logoUrl!,
                    fit: BoxFit.cover,
                    errorBuilder: (_, _, _) => const Icon(
                      Icons.image_outlined,
                      color: AppColors.textMuted,
                    ),
                  )
                : const Icon(Icons.business_rounded,
                    color: AppColors.textMuted),
          ),
          const SizedBox(width: 14),
          const Expanded(
            child: Text(
              'Logo de la empresa\nAparece en el RIDE (PDF).',
              style: TextStyle(
                fontFamily: 'Avenir Next',
                fontWeight: FontWeight.w600,
                fontSize: 13,
                height: 1.3,
                color: AppColors.textSecondary,
              ),
            ),
          ),
          const SizedBox(width: 10),
          OutlinedButton(
            onPressed: uploading ? null : onTap,
            child: uploading
                ? const SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Text('Cambiar'),
          ),
        ],
      ),
    );
  }
}

class _ReadOnlyRow extends StatelessWidget {
  final String label;
  final String value;

  const _ReadOnlyRow({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Text(
          '$label: ',
          style: const TextStyle(
            fontFamily: 'Avenir Next',
            fontWeight: FontWeight.w600,
            color: AppColors.textMuted,
            fontSize: 13,
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: const TextStyle(
              fontFamily: 'Avenir Next',
              fontWeight: FontWeight.w800,
              color: AppColors.textPrimary,
              fontSize: 14,
            ),
          ),
        ),
      ],
    );
  }
}
