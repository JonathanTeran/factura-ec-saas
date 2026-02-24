import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

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
                onPressed: () {},
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
                        colors: [Color(0xFF20D6F6), Color(0xFF1A6EFF)],
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
                    onPressed: () {},
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
                      color: hasSignature
                          ? AppColors.success
                          : AppColors.warning,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 10),
                  OutlinedButton.icon(
                    onPressed: () {
                      ref.invalidate(meProvider);
                      ref.invalidate(companiesProvider);
                      ref.invalidate(backendAvailabilityProvider);
                    },
                    icon: const Icon(Icons.sync_rounded),
                    label: const Text('Actualizar estado'),
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
            const SectionHeader(
              title: 'Productividad',
              actionText: 'Personalizar',
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
                    icon: Icons.lock_outline_rounded,
                    title: 'Clave SRI',
                    subtitle: 'Sincronización automática y segura.',
                    onTap: () {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Proximamente')),
                      );
                    },
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
