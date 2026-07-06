import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/api/v1_api_service.dart';
import '../../core/constants/api_constants.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/page_header.dart';
import '../../core/widgets/section_header.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/company_provider.dart';

class SettingsScreen extends ConsumerStatefulWidget {
  const SettingsScreen({super.key});

  @override
  ConsumerState<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends ConsumerState<SettingsScreen> {
  bool _updatingBiometrics = false;

  bool _switchingCompany = false;

  Future<void> _doSwitchCompany(ApiCompany company) async {
    if (_switchingCompany) return;
    setState(() => _switchingCompany = true);
    try {
      await ref.read(v1ApiServiceProvider).switchCompany(company.id);
      ref.invalidate(meProvider);
      ref.invalidate(companiesProvider);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Empresa activa: ${company.businessName}')),
      );
    } catch (error) {
      if (!mounted) return;
      final msg = error is ApiException
          ? error.message
          : 'No se pudo cambiar de empresa.';
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
    } finally {
      if (mounted) setState(() => _switchingCompany = false);
    }
  }

  Future<void> _showSwitchCompanySheet(
    List<ApiCompany> companies,
    int? currentCompanyId,
  ) async {
    await showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (sheetCtx) => _SwitchCompanySheet(
        companies: companies,
        currentCompanyId: currentCompanyId,
        onSelect: (company) {
          Navigator.of(sheetCtx).pop();
          _doSwitchCompany(company);
        },
      ),
    );
  }

  Future<void> _toggleBiometrics(bool enabled) async {
    if (_updatingBiometrics) return;

    setState(() => _updatingBiometrics = true);
    try {
      await ref.read(biometricAuthServiceProvider).setEnabled(enabled);
      ref.invalidate(biometricStatusProvider);

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            enabled
                ? 'Acceso biométrico activado.'
                : 'Acceso biométrico desactivado.',
          ),
        ),
      );
    } catch (error) {
      if (!mounted) return;
      final message = error is StateError
          ? error.message
          : 'No se pudo actualizar la biometría.';
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(message)));
    } finally {
      if (mounted) {
        setState(() => _updatingBiometrics = false);
      }
    }
  }

  Future<void> _openUrl(String url) async {
    final uri = Uri.parse(url);
    final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!ok && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No se pudo abrir el enlace.')),
      );
    }
  }

  Future<void> _confirmDeleteAccount() async {
    final passwordCtrl = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Eliminar cuenta'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Esta acción es permanente. Se cancela tu suscripción y NO se '
              'realizan devoluciones. Tus comprobantes fiscales se conservan el '
              'tiempo que exige la ley y luego se eliminan definitivamente.',
            ),
            const SizedBox(height: 14),
            TextField(
              controller: passwordCtrl,
              obscureText: true,
              decoration: const InputDecoration(
                labelText: 'Confirma tu contraseña',
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: AppColors.error),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Eliminar cuenta'),
          ),
        ],
      ),
    );

    if (confirmed != true) {
      passwordCtrl.dispose();
      return;
    }
    final password = passwordCtrl.text;
    passwordCtrl.dispose();

    try {
      await ref.read(v1ApiServiceProvider).deleteAccount(password);
      ref.invalidate(backendAvailabilityProvider);
      ref.invalidate(meProvider);
      ref.invalidate(companiesProvider);
      if (mounted) context.go('/login');
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString())),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final backendAvailability = ref.watch(backendAvailabilityProvider);
    final meAsync = ref.watch(meProvider);
    final companiesAsync = ref.watch(companiesProvider);
    final biometricAsync = ref.watch(biometricStatusProvider);
    final backendStatusLabel = backendAvailability.when(
      data: (value) => switch (value) {
        BackendAvailability.noSession => 'Backend activo sin sesión',
        BackendAvailability.reachable => 'Backend conectado',
        BackendAvailability.unreachable => 'Backend no alcanzable',
      },
      loading: () => 'Verificando backend...',
      error: (_, _) => 'Error verificando backend',
    );
    final displayName = meAsync.valueOrNull?.name ?? 'Sin sesión';
    final displaySubtitle =
        meAsync.valueOrNull?.email ?? 'Inicia sesión para continuar';
    final companies = companiesAsync.valueOrNull ?? const <ApiCompany>[];
    final hasSignature = companies.any((company) => company.hasValidSignature);

    return SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            PageHeader(
              title: 'Menú',
              subtitle: 'Cuenta, seguridad y automatizaciones',
              trailing: IconButton.filledTonal(
                tooltip: 'Ayuda',
                onPressed: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Próximamente disponible')),
                  );
                },
                icon: const Icon(Icons.help_outline_rounded),
              ),
            ),
            const SizedBox(height: 14),
            GlassPanel(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Modo de datos',
                    style: TextStyle(
                      fontFamily: 'Avenir Next',
                      fontWeight: FontWeight.w700,
                      color: AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    backendStatusLabel,
                    style: const TextStyle(
                      fontFamily: 'Avenir Next',
                      color: AppColors.textSecondary,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    ApiConstants.enableBackend
                        ? 'Base URL: ${ApiConstants.baseUrl}'
                        : 'Backend desactivado. Usa --dart-define=ENABLE_BACKEND=true',
                    style: const TextStyle(
                      fontFamily: 'Avenir Next',
                      color: AppColors.textMuted,
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 18),
            GlassPanel(
              child: Row(
                children: [
                  Container(
                    width: 56,
                    height: 56,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(16),
                      gradient: const LinearGradient(
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                        colors: [Color(0xFF3B82F6), Color(0xFF2563EB)],
                      ),
                    ),
                    child: const Center(
                      child: Text(
                        'EC',
                        style: TextStyle(
                          fontFamily: 'Avenir Next',
                          fontWeight: FontWeight.w800,
                          color: Colors.white,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          displayName,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontFamily: 'Avenir Next',
                            fontWeight: FontWeight.w700,
                            fontSize: 18,
                            color: AppColors.textPrimary,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          displaySubtitle,
                          style: const TextStyle(
                            fontFamily: 'Avenir Next',
                            color: AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    onPressed: () {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Edición de perfil disponible en la web')),
                      );
                    },
                    icon: const Icon(Icons.chevron_right_rounded),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 18),
            GlassPanel(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Empresas activas: ${companies.length}',
                    style: const TextStyle(
                      fontFamily: 'Avenir Next',
                      color: AppColors.textPrimary,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    hasSignature
                        ? 'Al menos una empresa tiene firma electrónica vigente.'
                        : 'Ninguna empresa tiene firma vigente.',
                    style: TextStyle(
                      fontFamily: 'Avenir Next',
                      color:
                          hasSignature ? AppColors.success : AppColors.warning,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      OutlinedButton.icon(
                        onPressed: () {
                          ref.invalidate(meProvider);
                          ref.invalidate(companiesProvider);
                          ref.invalidate(backendAvailabilityProvider);
                        },
                        icon: const Icon(Icons.sync_rounded),
                        label: const Text('Actualizar estado'),
                      ),
                      const SizedBox(width: 10),
                      if (companies.length > 1)
                        FilledButton.icon(
                          onPressed: _switchingCompany
                              ? null
                              : () => _showSwitchCompanySheet(
                                    companies,
                                    meAsync.valueOrNull?.currentCompanyId,
                                  ),
                          icon: _switchingCompany
                              ? const SizedBox(
                                  width: 14,
                                  height: 14,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : const Icon(Icons.swap_horiz_rounded),
                          label: const Text('Cambiar empresa'),
                        ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 18),
            GlassPanel(
              child: biometricAsync.when(
                loading: () => const Padding(
                  padding: EdgeInsets.symmetric(vertical: 8),
                  child: Row(
                    children: [
                      SizedBox(
                        width: 16,
                        height: 16,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      ),
                      SizedBox(width: 10),
                      Text(
                        'Verificando biometría...',
                        style: TextStyle(
                          fontFamily: 'Avenir Next',
                          color: AppColors.textSecondary,
                        ),
                      ),
                    ],
                  ),
                ),
                error: (_, _) => const Text(
                  'No pudimos leer la configuración biométrica.',
                  style: TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.warning,
                  ),
                ),
                data: (biometricStatus) {
                  final canUseBiometrics = biometricStatus.canUse;
                  final title = canUseBiometrics
                      ? 'Acceso con ${biometricStatus.typeLabel}'
                      : 'Acceso biométrico';
                  final subtitle = canUseBiometrics
                      ? biometricStatus.enabled
                          ? 'Desbloqueo activo para ingreso rápido y seguro.'
                          : 'Actívalo para entrar con biometría.'
                      : 'Configura Face ID o huella en tu dispositivo.';
                  final icon = biometricStatus.hasFace
                      ? Icons.face_unlock_rounded
                      : Icons.fingerprint_rounded;

                  return Row(
                    children: [
                      Container(
                        width: 40,
                        height: 40,
                        decoration: BoxDecoration(
                          color: AppColors.primary.withValues(alpha: 0.15),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Icon(icon, color: AppColors.primaryLight),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              title,
                              style: const TextStyle(
                                fontFamily: 'Avenir Next',
                                fontWeight: FontWeight.w700,
                                color: AppColors.textPrimary,
                                fontSize: 16,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              subtitle,
                              style: const TextStyle(
                                fontFamily: 'Avenir Next',
                                fontWeight: FontWeight.w500,
                                color: AppColors.textSecondary,
                                fontSize: 13,
                              ),
                            ),
                          ],
                        ),
                      ),
                      Switch.adaptive(
                        value: canUseBiometrics && biometricStatus.enabled,
                        onChanged: (!canUseBiometrics || _updatingBiometrics)
                            ? null
                            : _toggleBiometrics,
                      ),
                    ],
                  );
                },
              ),
            ),
            const SizedBox(height: 18),
            SectionHeader(
              title: 'Productividad',
              actionText: 'Personalizar',
              onAction: () {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Próximamente disponible')),
                );
              },
            ),
            const SizedBox(height: 10),
            GlassPanel(
              child: Column(
                children: [
                  _MenuTile(
                    icon: Icons.description_outlined,
                    title: 'Borradores',
                    subtitle: 'Finaliza documentos pendientes.',
                    onTap: () => context.go('/documents'),
                  ),
                  const Divider(height: 20),
                  _MenuTile(
                    icon: Icons.storefront_outlined,
                    title: 'Productos',
                    subtitle: 'Gestiona tu catálogo y precios.',
                    onTap: () => context.go('/products'),
                  ),
                  const Divider(height: 20),
                  _MenuTile(
                    icon: Icons.people_outline_rounded,
                    title: 'Clientes',
                    subtitle: 'Segmenta y activa recordatorios.',
                    onTap: () => context.go('/customers'),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 18),
            const SectionHeader(title: 'Configuración', actionText: ''),
            const SizedBox(height: 10),
            GlassPanel(
              child: Column(
                children: [
                  _MenuTile(
                    icon: Icons.credit_card_rounded,
                    title: 'Facturación',
                    subtitle: 'Plan, pagos y transferencia bancaria.',
                    onTap: () => context.push('/settings/billing'),
                  ),
                  const Divider(height: 20),
                  _MenuTile(
                    icon: Icons.verified_user_outlined,
                    title: 'Firma electrónica',
                    subtitle: 'Sube o actualiza tu certificado .p12.',
                    onTap: () => context.push('/settings/certificate'),
                  ),
                  const Divider(height: 20),
                  _MenuTile(
                    icon: Icons.stars_outlined,
                    title: 'Rewards',
                    subtitle: 'Programa de incentivos premium.',
                    onTap: () {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Proximamente')),
                      );
                    },
                  ),
                  const Divider(height: 20),
                  _MenuTile(
                    icon: Icons.support_agent_outlined,
                    title: 'Soporte prioritario',
                    subtitle: 'Resuelve tickets en menos tiempo.',
                    onTap: () {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Proximamente')),
                      );
                    },
                  ),
                  const Divider(height: 20),
                  _MenuTile(
                    icon: Icons.description_outlined,
                    title: 'Términos y condiciones',
                    subtitle: 'Suscripción, cancelación y sin devoluciones.',
                    onTap: () =>
                        _openUrl('https://facturacion.amephia.com/terms'),
                  ),
                  const Divider(height: 20),
                  _MenuTile(
                    icon: Icons.privacy_tip_outlined,
                    title: 'Política de privacidad',
                    subtitle: 'Cómo tratamos y protegemos tus datos.',
                    onTap: () =>
                        _openUrl('https://facturacion.amephia.com/privacy'),
                  ),
                  const Divider(height: 20),
                  _MenuTile(
                    icon: Icons.delete_forever_outlined,
                    title: 'Eliminar cuenta',
                    subtitle: 'Elimina tu cuenta de forma permanente.',
                    onTap: _confirmDeleteAccount,
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),
            GlassPanel(
              child: InkWell(
                borderRadius: BorderRadius.circular(18),
                onTap: () async {
                  await ref.read(v1ApiServiceProvider).logout();
                  ref.invalidate(backendAvailabilityProvider);
                  ref.invalidate(meProvider);
                  ref.invalidate(companiesProvider);
                  if (context.mounted) context.go('/login');
                },
                child: const Padding(
                  padding: EdgeInsets.all(14),
                  child: Row(
                    children: [
                      Icon(Icons.logout_rounded, color: AppColors.error),
                      SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          'Cerrar sesión',
                          style: TextStyle(
                            fontFamily: 'Avenir Next',
                            fontWeight: FontWeight.w700,
                            color: AppColors.error,
                          ),
                        ),
                      ),
                      Icon(Icons.chevron_right_rounded, color: AppColors.error),
                    ],
                  ),
                ),
              ),
            ),
            const SizedBox(height: 12),
            const Center(
              child: Text(
                'Versión 3.0.0 · Build 120',
                style: TextStyle(
                  fontFamily: 'Avenir Next',
                  color: AppColors.textMuted,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ──────────────────────────────────────────────────────────────
// Bottom sheet shown when the user taps "Cambiar empresa"
// ──────────────────────────────────────────────────────────────

class _SwitchCompanySheet extends StatelessWidget {
  final List<ApiCompany> companies;
  final int? currentCompanyId;
  final void Function(ApiCompany) onSelect;

  const _SwitchCompanySheet({
    required this.companies,
    required this.currentCompanyId,
    required this.onSelect,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
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
          const SizedBox(height: 20),
          const Text(
            'Seleccionar empresa activa',
            style: TextStyle(
              fontFamily: 'Avenir Next',
              fontWeight: FontWeight.w700,
              fontSize: 18,
              color: AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 6),
          const Text(
            'Los documentos y reportes se filtrarán por la empresa seleccionada.',
            style: TextStyle(
              fontFamily: 'Avenir Next',
              color: AppColors.textSecondary,
              fontSize: 13,
            ),
          ),
          const SizedBox(height: 16),
          ...companies.map((company) {
            final isActive = company.id == currentCompanyId;
            return Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: InkWell(
                borderRadius: BorderRadius.circular(14),
                onTap: isActive ? null : () => onSelect(company),
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 12,
                  ),
                  decoration: BoxDecoration(
                    color: isActive
                        ? AppColors.primary.withValues(alpha: 0.15)
                        : AppColors.primary.withValues(alpha: 0.05),
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(
                      color: isActive
                          ? AppColors.primary.withValues(alpha: 0.6)
                          : Colors.transparent,
                    ),
                  ),
                  child: Row(
                    children: [
                      Container(
                        width: 40,
                        height: 40,
                        decoration: BoxDecoration(
                          color: AppColors.primary.withValues(alpha: 0.2),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(
                          Icons.business_rounded,
                          color: AppColors.primaryLight,
                          size: 20,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              company.businessName,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                fontFamily: 'Avenir Next',
                                fontWeight: FontWeight.w700,
                                fontSize: 15,
                                color: isActive
                                    ? AppColors.primary
                                    : AppColors.textPrimary,
                              ),
                            ),
                            if (company.ruc.isNotEmpty)
                              Text(
                                'RUC ${company.ruc}',
                                style: const TextStyle(
                                  fontFamily: 'Avenir Next',
                                  color: AppColors.textSecondary,
                                  fontSize: 12,
                                ),
                              ),
                          ],
                        ),
                      ),
                      if (isActive)
                        const Icon(
                          Icons.check_circle_rounded,
                          color: AppColors.primary,
                          size: 20,
                        ),
                    ],
                  ),
                ),
              ),
            );
          }),
          const SizedBox(height: 4),
          InkWell(
            borderRadius: BorderRadius.circular(14),
            onTap: () {
              final router = GoRouter.of(context);
              Navigator.of(context).maybePop();
              router.push('/settings/company/new');
            },
            child: Container(
              padding: const EdgeInsets.symmetric(
                horizontal: 14,
                vertical: 12,
              ),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(14),
                border: Border.all(
                  color: AppColors.primary.withValues(alpha: 0.4),
                ),
              ),
              child: const Row(
                children: [
                  Icon(
                    Icons.add_business_rounded,
                    color: AppColors.primaryLight,
                    size: 20,
                  ),
                  SizedBox(width: 12),
                  Text(
                    'Agregar empresa',
                    style: TextStyle(
                      fontFamily: 'Avenir Next',
                      fontWeight: FontWeight.w700,
                      fontSize: 15,
                      color: AppColors.primary,
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

// ──────────────────────────────────────────────────────────────

class _MenuTile extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  const _MenuTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(14),
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: AppColors.primary.withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: AppColors.primaryLight),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(
                      fontFamily: 'Avenir Next',
                      fontWeight: FontWeight.w700,
                      color: AppColors.textPrimary,
                      fontSize: 16,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    subtitle,
                    style: const TextStyle(
                      fontFamily: 'Avenir Next',
                      fontWeight: FontWeight.w500,
                      color: AppColors.textSecondary,
                      fontSize: 13,
                    ),
                  ),
                ],
              ),
            ),
            const Icon(
              Icons.chevron_right_rounded,
              color: AppColors.textSecondary,
            ),
          ],
        ),
      ),
    );
  }
}
