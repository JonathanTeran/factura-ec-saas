import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/api/v1_api_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/aurora_background.dart';
import '../../core/widgets/glass_panel.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/company_provider.dart';

/// Asistente para migrar a Producción: reúne en una guía los pasos de
/// configuración (datos, firma, establecimientos, secuenciales) y el cambio
/// final de ambiente a Producción.
class MigrateProductionScreen extends ConsumerStatefulWidget {
  const MigrateProductionScreen({super.key});

  @override
  ConsumerState<MigrateProductionScreen> createState() =>
      _MigrateProductionScreenState();
}

class _MigrateProductionScreenState
    extends ConsumerState<MigrateProductionScreen> {
  ApiCompany? _company;
  int _emissionPoints = 0;
  bool _loading = true;
  bool _switching = false;
  Object? _error;

  V1ApiService get _api => ref.read(v1ApiServiceProvider);

  @override
  void initState() {
    super.initState();
    Future.microtask(_load);
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final companies = await ref.read(companiesProvider.future);
      final me = await ref.read(meProvider.future);
      if (companies.isEmpty) {
        throw ApiException('No hay empresas configuradas.');
      }
      final active = companies.firstWhere(
        (c) => c.id == me.currentCompanyId,
        orElse: () => companies.first,
      );
      final branches = await _api.branches(active.id);
      final eps = branches.fold<int>(0, (s, b) => s + b.emissionPoints.length);
      if (!mounted) return;
      setState(() {
        _company = active;
        _emissionPoints = eps;
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

  Future<void> _switchToProduction() async {
    final company = _company;
    if (company == null || _switching) return;

    final messenger = ScaffoldMessenger.of(context);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Pasar a Producción'),
        content: const Text(
          'A partir de ahora tus comprobantes se emitirán en PRODUCCIÓN y '
          'tendrán validez tributaria ante el SRI.\n\nAsegurate de tener tu '
          'RUC autorizado para producción, tu firma vigente y los secuenciales '
          'ajustados para no repetir números.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: AppColors.success),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Sí, pasar a Producción'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    setState(() => _switching = true);
    try {
      await _api.updateCompanyEnvironment(company.id, '2');
      ref.invalidate(companiesProvider);
      ref.invalidate(meProvider);
      await _load();
      if (!mounted) return;
      messenger.showSnackBar(
        const SnackBar(content: Text('¡Listo! Ya estás emitiendo en Producción.')),
      );
    } catch (e) {
      messenger.showSnackBar(
        SnackBar(
          content: Text(e is ApiException ? e.message : 'No se pudo cambiar.'),
        ),
      );
    } finally {
      if (mounted) setState(() => _switching = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Migrar a Producción')),
      body: Stack(
        children: [
          const Positioned.fill(child: AuroraBackground()),
          SafeArea(child: _body()),
        ],
      ),
    );
  }

  Widget _body() {
    if (_loading) return const Center(child: CircularProgressIndicator());
    final company = _company;
    if (company == null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Text(
            _error is ApiException
                ? (_error as ApiException).message
                : 'No se pudo cargar la empresa.',
            textAlign: TextAlign.center,
            style: const TextStyle(color: AppColors.textSecondary),
          ),
        ),
      );
    }

    final isProd = company.isProduction;
    final dataOk = company.businessName.isNotEmpty && company.address.isNotEmpty;
    final signatureOk = company.hasValidSignature;
    final pointsOk = _emissionPoints > 0;

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 28),
        children: [
          if (isProd)
            _ProdBanner()
          else
            const Text(
              'Seguí estos pasos para emitir con validez tributaria. Podés '
              'cambiar a Producción cuando quieras.',
              style: TextStyle(
                fontFamily: 'Avenir Next',
                color: AppColors.textSecondary,
                fontSize: 14,
                height: 1.35,
              ),
            ),
          const SizedBox(height: 14),
          _StepCard(
            number: 1,
            title: 'Datos de la empresa',
            subtitle: dataOk
                ? 'Razón social y dirección completas.'
                : 'Completá razón social y dirección (podés traerlos del SRI).',
            done: dataOk,
            actionLabel: 'Revisar',
            onAction: () =>
                context.push('/settings/company/edit', extra: company),
          ),
          const SizedBox(height: 10),
          _StepCard(
            number: 2,
            title: 'Firma electrónica',
            subtitle: signatureOk
                ? 'Certificado vigente.'
                : 'Subí tu certificado .p12 para poder firmar.',
            done: signatureOk,
            actionLabel: 'Configurar',
            onAction: () => context.push('/settings/certificate'),
          ),
          const SizedBox(height: 10),
          _StepCard(
            number: 3,
            title: 'Establecimientos y puntos de emisión',
            subtitle: pointsOk
                ? '$_emissionPoints punto(s) de emisión configurado(s).'
                : 'Agregá al menos un punto de emisión (o importalo del SRI).',
            done: pointsOk,
            actionLabel: 'Configurar',
            onAction: () => context.push('/settings/establishments'),
          ),
          const SizedBox(height: 10),
          _StepCard(
            number: 4,
            title: 'Secuenciales',
            subtitle:
                'Fijá el último número usado por tipo para no repetir números.',
            done: null,
            actionLabel: 'Ajustar',
            onAction: () => context.push('/settings/establishments'),
          ),
          const SizedBox(height: 20),
          if (!isProd) ...[
            if (!(dataOk && signatureOk && pointsOk))
              const Padding(
                padding: EdgeInsets.only(bottom: 10),
                child: Text(
                  'Sugerencia: completá los pasos marcados antes de pasar a '
                  'Producción.',
                  style: TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.warning,
                    fontWeight: FontWeight.w600,
                    fontSize: 13,
                  ),
                ),
              ),
            FilledButton.icon(
              onPressed: _switching ? null : _switchToProduction,
              icon: _switching
                  ? const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Icon(Icons.rocket_launch_rounded),
              label: const Text('Pasar a Producción'),
              style: FilledButton.styleFrom(
                backgroundColor: AppColors.success,
                foregroundColor: Colors.white,
                minimumSize: const Size.fromHeight(54),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _ProdBanner extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.success.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.success.withValues(alpha: 0.4)),
      ),
      child: const Row(
        children: [
          Icon(Icons.verified_rounded, color: AppColors.success),
          SizedBox(width: 10),
          Expanded(
            child: Text(
              'Ya estás emitiendo en Producción. Tus comprobantes tienen '
              'validez tributaria.',
              style: TextStyle(
                fontFamily: 'Avenir Next',
                fontWeight: FontWeight.w600,
                color: AppColors.textPrimary,
                fontSize: 13,
                height: 1.3,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _StepCard extends StatelessWidget {
  final int number;
  final String title;
  final String subtitle;

  /// true = hecho, false = pendiente, null = informativo (sin estado).
  final bool? done;
  final String actionLabel;
  final VoidCallback onAction;

  const _StepCard({
    required this.number,
    required this.title,
    required this.subtitle,
    required this.done,
    required this.actionLabel,
    required this.onAction,
  });

  @override
  Widget build(BuildContext context) {
    final (Color color, IconData icon) = switch (done) {
      true => (AppColors.success, Icons.check_rounded),
      false => (AppColors.warning, Icons.priority_high_rounded),
      null => (AppColors.primary, Icons.tune_rounded),
    };

    return GlassPanel(
      child: Row(
        children: [
          Container(
            width: 38,
            height: 38,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.15),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: color, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '$number. $title',
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    fontWeight: FontWeight.w800,
                    fontSize: 15,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  subtitle,
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.textSecondary,
                    fontSize: 13,
                    height: 1.3,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          TextButton(onPressed: onAction, child: Text(actionLabel)),
        ],
      ),
    );
  }
}
