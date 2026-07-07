import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/api/v1_api_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/aurora_background.dart';
import '../../core/widgets/glass_panel.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/company_provider.dart';

/// Establecimientos y puntos de emisión de la empresa activa. Permite crear y
/// editar, y ajustar el secuencial de cada punto de emisión.
class EstablishmentsScreen extends ConsumerStatefulWidget {
  const EstablishmentsScreen({super.key});

  @override
  ConsumerState<EstablishmentsScreen> createState() =>
      _EstablishmentsScreenState();
}

class _EstablishmentsScreenState extends ConsumerState<EstablishmentsScreen> {
  ApiCompany? _company;
  int? _companyId;
  bool _loading = true;
  bool _importing = false;
  Object? _error;
  List<ApiBranch> _branches = const [];

  V1ApiService get _api => ref.read(v1ApiServiceProvider);

  @override
  void initState() {
    super.initState();
    Future.microtask(_resolveAndLoad);
  }

  Future<void> _resolveAndLoad() async {
    // Resolver empresa activa.
    final companies = await ref.read(companiesProvider.future);
    final me = await ref.read(meProvider.future);
    if (companies.isEmpty) {
      if (mounted) {
        setState(() {
          _loading = false;
          _error = 'No hay empresas configuradas.';
        });
      }
      return;
    }
    final active = companies.firstWhere(
      (c) => c.id == me.currentCompanyId,
      orElse: () => companies.first,
    );
    _company = active;
    _companyId = active.id;
    await _load();
  }

  /// Importa del catastro del SRI los establecimientos que aún no existen
  /// (los ubica por código). Facilita la configuración como en la web.
  Future<void> _importFromSri() async {
    final company = _company;
    if (company == null || _importing) return;
    setState(() => _importing = true);
    try {
      final r = await _api.lookupRuc(company.ruc);
      final existing = _branches.map((b) => b.code).toSet();
      var created = 0;
      for (final est in r.establishments) {
        if (est.code.isEmpty || existing.contains(est.code)) continue;
        await _api.createBranch(company.id, {
          'code': est.code,
          'name': (est.tradeName ?? '').isNotEmpty
              ? est.tradeName
              : (est.isMain ? 'Matriz' : 'Establecimiento ${est.code}'),
          'address': (est.address ?? '').isNotEmpty
              ? est.address
              : (company.address.isNotEmpty ? company.address : 'S/N'),
          'is_main': est.isMain,
          'is_active': true,
        });
        created++;
      }
      _toast(created == 0
          ? 'No hay establecimientos nuevos en el SRI.'
          : 'Se importaron $created establecimiento(s) del SRI.');
      await _load();
    } catch (e) {
      _toast(e is ApiException ? e.message : 'No se pudo consultar el SRI.');
    } finally {
      if (mounted) setState(() => _importing = false);
    }
  }

  Future<void> _load() async {
    if (_companyId == null) return;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final branches = await _api.branches(_companyId!);
      if (!mounted) return;
      setState(() {
        _branches = branches;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e;
        _loading = false;
      });
    }
  }

  void _toast(String msg) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(msg)));
  }

  Future<void> _branchForm({ApiBranch? branch}) async {
    final result = await showModalBottomSheet<Map<String, dynamic>>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _BranchFormSheet(branch: branch),
    );
    if (result == null || _companyId == null) return;
    try {
      if (result['__delete'] == true && branch != null) {
        if (!await _confirmDelete('¿Eliminar el establecimiento "${branch.name}"?')) {
          return;
        }
        await _api.deleteBranch(_companyId!, branch.id);
        _toast('Establecimiento eliminado.');
      } else if (branch == null) {
        await _api.createBranch(_companyId!, result);
        _toast('Establecimiento creado.');
      } else {
        await _api.updateBranch(_companyId!, branch.id, result);
        _toast('Cambios guardados.');
      }
      await _load();
    } catch (e) {
      _toast(e is ApiException ? e.message : 'No se pudo guardar.');
    }
  }

  Future<bool> _confirmDelete(String message) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Confirmar'),
        content: Text(message),
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
    return ok == true;
  }

  Future<void> _emissionPointForm(ApiBranch branch, {ApiEmissionPoint? ep}) async {
    final result = await showModalBottomSheet<Map<String, dynamic>>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _EmissionPointFormSheet(ep: ep),
    );
    if (result == null) return;
    try {
      if (result['__delete'] == true && ep != null) {
        if (!await _confirmDelete('¿Eliminar el punto de emisión ${ep.code}?')) {
          return;
        }
        await _api.deleteEmissionPoint(branch.id, ep.id);
        _toast('Punto de emisión eliminado.');
      } else if (ep == null) {
        await _api.createEmissionPoint(branch.id, result);
        _toast('Punto de emisión creado.');
      } else {
        await _api.updateEmissionPoint(branch.id, ep.id, result);
        _toast('Cambios guardados.');
      }
      await _load();
    } catch (e) {
      _toast(e is ApiException ? e.message : 'No se pudo guardar.');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Establecimientos'),
        actions: [
          IconButton(
            tooltip: 'Importar del SRI',
            onPressed: (_loading || _importing) ? null : _importFromSri,
            icon: _importing
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.cloud_download_outlined),
          ),
          IconButton(
            tooltip: 'Agregar establecimiento',
            onPressed: _loading ? null : () => _branchForm(),
            icon: const Icon(Icons.add_business_rounded),
          ),
        ],
      ),
      body: Stack(
        children: [
          const Positioned.fill(child: AuroraBackground()),
          SafeArea(child: _body()),
        ],
      ),
    );
  }

  Widget _body() {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null && _branches.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: GlassPanel(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  _error is ApiException
                      ? (_error as ApiException).message
                      : _error.toString(),
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.textSecondary,
                  ),
                ),
                const SizedBox(height: 10),
                ElevatedButton(
                  onPressed: _load,
                  child: const Text('Reintentar'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 28),
        children: [
          for (final branch in _branches) ...[
            _BranchCard(
              branch: branch,
              onEditBranch: () => _branchForm(branch: branch),
              onAddEmissionPoint: () => _emissionPointForm(branch),
              onEditEmissionPoint: (ep) =>
                  _emissionPointForm(branch, ep: ep),
              onSequentials: (ep) => context.push(
                '/settings/emission-point/sequentials',
                extra: ep,
              ),
            ),
            const SizedBox(height: 12),
          ],
          if (_branches.isEmpty)
            const Padding(
              padding: EdgeInsets.only(top: 40),
              child: Text(
                'No hay establecimientos. Agregá uno con el botón de arriba.',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontFamily: 'Avenir Next',
                  color: AppColors.textSecondary,
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _BranchCard extends StatelessWidget {
  final ApiBranch branch;
  final VoidCallback onEditBranch;
  final VoidCallback onAddEmissionPoint;
  final void Function(ApiEmissionPoint) onEditEmissionPoint;
  final void Function(ApiEmissionPoint) onSequentials;

  const _BranchCard({
    required this.branch,
    required this.onEditBranch,
    required this.onAddEmissionPoint,
    required this.onEditEmissionPoint,
    required this.onSequentials,
  });

  @override
  Widget build(BuildContext context) {
    return GlassPanel(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.14),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  branch.code,
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    fontWeight: FontWeight.w800,
                    color: AppColors.primary,
                    fontSize: 13,
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  branch.name,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    fontWeight: FontWeight.w800,
                    fontSize: 16,
                    color: AppColors.textPrimary,
                  ),
                ),
              ),
              if (branch.isMain)
                const Padding(
                  padding: EdgeInsets.only(right: 4),
                  child: Text(
                    'Matriz',
                    style: TextStyle(
                      fontFamily: 'Avenir Next',
                      fontWeight: FontWeight.w700,
                      fontSize: 11,
                      color: AppColors.textMuted,
                    ),
                  ),
                ),
              IconButton(
                visualDensity: VisualDensity.compact,
                onPressed: onEditBranch,
                icon: const Icon(Icons.edit_outlined, size: 20),
              ),
            ],
          ),
          if (branch.address.isNotEmpty) ...[
            const SizedBox(height: 2),
            Text(
              branch.address,
              style: const TextStyle(
                fontFamily: 'Avenir Next',
                color: AppColors.textSecondary,
                fontSize: 13,
              ),
            ),
          ],
          const Divider(height: 20),
          for (final ep in branch.emissionPoints)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Row(
                children: [
                  const Icon(Icons.point_of_sale_outlined,
                      size: 18, color: AppColors.textMuted),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      '${ep.code} · ${ep.description}'
                      '${ep.isActive ? '' : '  · Inactivo'}',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontFamily: 'Avenir Next',
                        fontWeight: FontWeight.w600,
                        fontSize: 14,
                        color: AppColors.textPrimary,
                      ),
                    ),
                  ),
                  TextButton(
                    style: TextButton.styleFrom(
                      visualDensity: VisualDensity.compact,
                      padding: const EdgeInsets.symmetric(horizontal: 8),
                    ),
                    onPressed: () => onSequentials(ep),
                    child: const Text('Secuencial'),
                  ),
                  IconButton(
                    visualDensity: VisualDensity.compact,
                    onPressed: () => onEditEmissionPoint(ep),
                    icon: const Icon(Icons.edit_outlined, size: 18),
                  ),
                ],
              ),
            ),
          Align(
            alignment: Alignment.centerLeft,
            child: TextButton.icon(
              onPressed: onAddEmissionPoint,
              icon: const Icon(Icons.add_rounded, size: 18),
              label: const Text('Agregar punto de emisión'),
            ),
          ),
        ],
      ),
    );
  }
}

class _BranchFormSheet extends StatefulWidget {
  final ApiBranch? branch;
  const _BranchFormSheet({this.branch});

  @override
  State<_BranchFormSheet> createState() => _BranchFormSheetState();
}

class _BranchFormSheetState extends State<_BranchFormSheet> {
  late final _code = TextEditingController(text: widget.branch?.code ?? '001');
  late final _name =
      TextEditingController(text: widget.branch?.name ?? 'Matriz');
  late final _addr = TextEditingController(text: widget.branch?.address ?? '');
  late bool _active = widget.branch?.isActive ?? true;

  @override
  void dispose() {
    _code.dispose();
    _name.dispose();
    _addr.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final editing = widget.branch != null;
    return _SheetShell(
      title: editing ? 'Editar establecimiento' : 'Nuevo establecimiento',
      children: [
        TextField(
          controller: _code,
          keyboardType: TextInputType.number,
          maxLength: 3,
          decoration: const InputDecoration(
            labelText: 'Código (3 dígitos) *',
            hintText: '001',
            counterText: '',
          ),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _name,
          decoration: const InputDecoration(labelText: 'Nombre *'),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _addr,
          decoration: const InputDecoration(labelText: 'Dirección *'),
        ),
        SwitchListTile.adaptive(
          contentPadding: EdgeInsets.zero,
          title: const Text(
            'Activo',
            style: TextStyle(
              fontFamily: 'Avenir Next',
              fontWeight: FontWeight.w600,
              color: AppColors.textPrimary,
            ),
          ),
          value: _active,
          onChanged: (v) => setState(() => _active = v),
        ),
        const SizedBox(height: 8),
        ElevatedButton(
          onPressed: () {
            final code = _code.text.trim();
            if (code.length != 3 ||
                _name.text.trim().isEmpty ||
                _addr.text.trim().isEmpty) {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Completá código (3 dígitos), nombre y dirección.'),
                ),
              );
              return;
            }
            Navigator.pop(context, {
              'code': code,
              'name': _name.text.trim(),
              'address': _addr.text.trim(),
              'is_active': _active,
            });
          },
          style: ElevatedButton.styleFrom(minimumSize: const Size.fromHeight(50)),
          child: Text(editing ? 'Guardar' : 'Crear'),
        ),
        if (editing) ...[
          const SizedBox(height: 8),
          OutlinedButton.icon(
            onPressed: () => Navigator.pop(context, {'__delete': true}),
            icon: const Icon(Icons.delete_outline_rounded, size: 18),
            label: const Text('Eliminar establecimiento'),
            style: OutlinedButton.styleFrom(
              foregroundColor: AppColors.error,
              side: BorderSide(color: AppColors.error.withValues(alpha: 0.5)),
              minimumSize: const Size.fromHeight(48),
            ),
          ),
        ],
      ],
    );
  }
}

class _EmissionPointFormSheet extends StatefulWidget {
  final ApiEmissionPoint? ep;
  const _EmissionPointFormSheet({this.ep});

  @override
  State<_EmissionPointFormSheet> createState() =>
      _EmissionPointFormSheetState();
}

class _EmissionPointFormSheetState extends State<_EmissionPointFormSheet> {
  late final _code = TextEditingController(text: widget.ep?.code ?? '001');
  late final _name = TextEditingController(
    text: widget.ep == null || widget.ep!.description == 'Punto de emisión'
        ? ''
        : widget.ep!.description,
  );
  late bool _active = widget.ep?.isActive ?? true;

  @override
  void dispose() {
    _code.dispose();
    _name.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final editing = widget.ep != null;
    return _SheetShell(
      title: editing ? 'Editar punto de emisión' : 'Nuevo punto de emisión',
      children: [
        TextField(
          controller: _code,
          keyboardType: TextInputType.number,
          maxLength: 3,
          decoration: const InputDecoration(
            labelText: 'Código (3 dígitos) *',
            hintText: '001',
            counterText: '',
          ),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _name,
          decoration: const InputDecoration(
            labelText: 'Descripción',
            hintText: 'Caja 1, Bodega, etc.',
          ),
        ),
        const SizedBox(height: 4),
        SwitchListTile.adaptive(
          contentPadding: EdgeInsets.zero,
          title: const Text(
            'Activo',
            style: TextStyle(
              fontFamily: 'Avenir Next',
              fontWeight: FontWeight.w600,
              color: AppColors.textPrimary,
            ),
          ),
          value: _active,
          onChanged: (v) => setState(() => _active = v),
        ),
        const SizedBox(height: 8),
        ElevatedButton(
          onPressed: () {
            final code = _code.text.trim();
            if (code.length != 3) {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('El código debe tener 3 dígitos.')),
              );
              return;
            }
            Navigator.pop(context, {
              'code': code,
              'name': _name.text.trim(),
              'is_active': _active,
            });
          },
          style: ElevatedButton.styleFrom(minimumSize: const Size.fromHeight(50)),
          child: Text(editing ? 'Guardar' : 'Crear'),
        ),
        if (editing) ...[
          const SizedBox(height: 8),
          OutlinedButton.icon(
            onPressed: () => Navigator.pop(context, {'__delete': true}),
            icon: const Icon(Icons.delete_outline_rounded, size: 18),
            label: const Text('Eliminar punto de emisión'),
            style: OutlinedButton.styleFrom(
              foregroundColor: AppColors.error,
              side: BorderSide(color: AppColors.error.withValues(alpha: 0.5)),
              minimumSize: const Size.fromHeight(48),
            ),
          ),
        ],
      ],
    );
  }
}

/// Contenedor común para los bottom sheets (con el teclado bien manejado).
class _SheetShell extends StatelessWidget {
  final String title;
  final List<Widget> children;

  const _SheetShell({required this.title, required this.children});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: Container(
        decoration: const BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: AppColors.textMuted.withValues(alpha: 0.4),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 16),
            Text(
              title,
              style: const TextStyle(
                fontFamily: 'Avenir Next',
                fontWeight: FontWeight.w800,
                fontSize: 18,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 16),
            ...children,
          ],
        ),
      ),
    );
  }
}
