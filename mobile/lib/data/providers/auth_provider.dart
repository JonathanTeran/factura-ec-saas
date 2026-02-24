import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_client.dart';
import '../../core/api/v1_api_service.dart';
import '../../core/security/biometric_auth_service.dart';

enum BackendAvailability { noSession, reachable, unreachable }

final apiClientProvider = Provider<ApiClient>((ref) => ApiClient());

final v1ApiServiceProvider = Provider<V1ApiService>(
  (ref) => V1ApiService(ref.read(apiClientProvider)),
);

final biometricAuthServiceProvider = Provider<BiometricAuthService>(
  (ref) => BiometricAuthService(),
);

final biometricStatusProvider = FutureProvider<BiometricAuthStatus>((
  ref,
) async {
  return ref.read(biometricAuthServiceProvider).status();
});

final backendAvailabilityProvider = FutureProvider<BackendAvailability>((
  ref,
) async {
  final service = ref.read(v1ApiServiceProvider);
  final hasSession = await service.hasSession();
  if (!hasSession) return BackendAvailability.noSession;
  final apiClient = ref.read(apiClientProvider);
  final reachable = await apiClient.pingBackend();
  return reachable
      ? BackendAvailability.reachable
      : BackendAvailability.unreachable;
});

final meProvider = FutureProvider<ApiUser>((ref) async {
  return ref.read(v1ApiServiceProvider).me();
});

bool isOfflineError(Object error) {
  if (error is DioException) {
    return error.type == DioExceptionType.connectionTimeout ||
        error.type == DioExceptionType.sendTimeout ||
        error.type == DioExceptionType.receiveTimeout ||
        error.type == DioExceptionType.connectionError;
  }
  if (error is ApiException) {
    return error.statusCode == null;
  }
  return false;
}
