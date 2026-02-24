import 'json_helpers.dart';

/// Represents the authenticated user returned by the API.
class ApiUser {
  final int id;
  final String name;
  final String email;
  final String? phone;
  final int? currentCompanyId;

  const ApiUser({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    this.currentCompanyId,
  });

  factory ApiUser.fromJson(Map<String, dynamic> json) {
    return ApiUser(
      id: intFrom(json['id']),
      name: stringFrom(json['name'], fallback: 'Usuario'),
      email: stringFrom(json['email']),
      phone: nullableStringFrom(json['phone']),
      currentCompanyId: nullableIntFrom(json['current_company_id']),
    );
  }
}

/// Holds the user, token, and optional expiration returned after login/register.
class AuthSession {
  final ApiUser user;
  final String token;
  final DateTime? expiresAt;

  const AuthSession({
    required this.user,
    required this.token,
    required this.expiresAt,
  });
}
