import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/widgets/aurora_background.dart';
import '../../core/widgets/brand_mark.dart';
import '../../data/providers/auth_provider.dart';

class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen> {
  String _status = 'Preparando sesión...';

  @override
  void initState() {
    super.initState();
    unawaited(_bootstrap());
  }

  Future<void> _bootstrap() async {
    // Tiempo mínimo de marca corto para no demorar el arranque (era 900ms).
    await Future<void>.delayed(const Duration(milliseconds: 300));
    if (!mounted) return;

    try {
      final availability = await ref.read(backendAvailabilityProvider.future);
      if (!mounted) return;

      switch (availability) {
        case BackendAvailability.noSession:
          context.go('/login');
          return;
        case BackendAvailability.unreachable:
          setState(() => _status = 'No pudimos conectar con el backend');
          await Future<void>.delayed(const Duration(milliseconds: 350));
          if (!mounted) return;
          context.go('/login');
          return;
        case BackendAvailability.reachable:
          final biometricService = ref.read(biometricAuthServiceProvider);
          final requireBiometricUnlock = await biometricService
              .shouldRequireBiometricUnlock();
          if (requireBiometricUnlock) {
            if (mounted) {
              setState(
                () => _status = 'Confirma tu identidad con Face ID o huella...',
              );
            }

            final unlocked = await biometricService.authenticate(
              reason:
                  'Desbloquea Facturón EC para ingresar con tu sesión segura.',
            );
            if (!mounted) return;
            if (!unlocked) {
              context.go('/login');
              return;
            }
          }
          if (!mounted) return;
          context.go('/');
          return;
      }
    } catch (_) {
      if (!mounted) return;
      context.go('/login');
    }
  }

  @override
  Widget build(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;

    return Scaffold(
      body: Stack(
        children: [
          const Positioned.fill(child: AuroraBackground()),
          Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const BrandMark(size: 84),
                const SizedBox(height: 10),
                Text(_status, style: textTheme.bodyMedium),
                const SizedBox(height: 22),
                const SizedBox(
                  width: 26,
                  height: 26,
                  child: CircularProgressIndicator(strokeWidth: 2.8),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
