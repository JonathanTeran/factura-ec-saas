import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:local_auth/local_auth.dart';

class BiometricAuthStatus {
  final bool deviceSupported;
  final bool canCheckBiometrics;
  final bool hasEnrolledBiometrics;
  final bool enabled;
  final bool hasFace;
  final bool hasFingerprint;

  const BiometricAuthStatus({
    required this.deviceSupported,
    required this.canCheckBiometrics,
    required this.hasEnrolledBiometrics,
    required this.enabled,
    required this.hasFace,
    required this.hasFingerprint,
  });

  bool get canUse => deviceSupported && hasEnrolledBiometrics;

  String get typeLabel {
    if (hasFace && hasFingerprint) return 'Face ID / Huella';
    if (hasFace) return 'Face ID';
    if (hasFingerprint) return 'Huella';
    return 'Biometría';
  }
}

class BiometricAuthService {
  static const String _biometricEnabledKey = 'biometric_enabled';

  final LocalAuthentication _localAuth;
  final FlutterSecureStorage _storage;

  BiometricAuthService({
    LocalAuthentication? localAuth,
    FlutterSecureStorage? storage,
  }) : _localAuth = localAuth ?? LocalAuthentication(),
       _storage = storage ?? const FlutterSecureStorage();

  Future<BiometricAuthStatus> status() async {
    final enabled = await isEnabled();

    var deviceSupported = false;
    var canCheckBiometrics = false;
    List<BiometricType> availableBiometrics = const <BiometricType>[];

    try {
      deviceSupported = await _localAuth.isDeviceSupported();
      canCheckBiometrics = await _localAuth.canCheckBiometrics;
      availableBiometrics = await _localAuth.getAvailableBiometrics();
    } on LocalAuthException {
      deviceSupported = false;
      canCheckBiometrics = false;
      availableBiometrics = const <BiometricType>[];
    }

    final hasFace = availableBiometrics.contains(BiometricType.face);
    final hasFingerprint = availableBiometrics.contains(
      BiometricType.fingerprint,
    );

    return BiometricAuthStatus(
      deviceSupported: deviceSupported,
      canCheckBiometrics: canCheckBiometrics,
      hasEnrolledBiometrics: availableBiometrics.isNotEmpty,
      enabled: enabled,
      hasFace: hasFace,
      hasFingerprint: hasFingerprint,
    );
  }

  Future<bool> isEnabled() async {
    final value = await _storage.read(key: _biometricEnabledKey);
    return value == 'true';
  }

  Future<void> setEnabled(bool enabled) async {
    if (!enabled) {
      await _storage.delete(key: _biometricEnabledKey);
      return;
    }

    final current = await status();
    if (!current.canUse) {
      throw StateError(
        'No hay biometría disponible. Configura Face ID o huella en el dispositivo.',
      );
    }

    await _storage.write(key: _biometricEnabledKey, value: 'true');
  }

  Future<bool> shouldRequireBiometricUnlock() async {
    final current = await status();
    return current.enabled && current.canUse;
  }

  Future<bool> authenticate({required String reason}) async {
    final current = await status();
    if (!current.canUse) return false;

    try {
      return await _localAuth.authenticate(
        localizedReason: reason,
        biometricOnly: true,
        persistAcrossBackgrounding: true,
      );
    } on LocalAuthException {
      return false;
    }
  }
}
