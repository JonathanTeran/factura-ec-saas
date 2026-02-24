import '../../core/api/v1_api_service.dart';
import '../models/user_model.dart';

/// Repository that wraps authentication-related API calls.
class AuthRepository {
  final V1ApiService _api;

  const AuthRepository(this._api);

  /// Check whether a stored session token exists.
  Future<bool> hasSession() => _api.hasSession();

  /// Clear the locally stored session token.
  Future<void> clearSession() => _api.clearSession();

  /// Authenticate with email and password.
  Future<AuthSession> login({
    required String email,
    required String password,
    String deviceName = 'macos-app',
  }) {
    return _api.login(
      email: email,
      password: password,
      deviceName: deviceName,
    );
  }

  /// Create a new account.
  Future<AuthSession> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String companyName,
    String deviceName = 'macos-app',
  }) {
    return _api.register(
      name: name,
      email: email,
      password: password,
      passwordConfirmation: passwordConfirmation,
      companyName: companyName,
      deviceName: deviceName,
    );
  }

  /// Log out and clear the session.
  Future<void> logout() => _api.logout();

  /// Fetch the currently authenticated user profile.
  Future<ApiUser> me() => _api.me();
}
