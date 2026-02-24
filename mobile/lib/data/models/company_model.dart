import 'json_helpers.dart';

/// Represents a company (emisor) from the API.
class ApiCompany {
  final int id;
  final String businessName;
  final String ruc;
  final bool hasValidSignature;

  const ApiCompany({
    required this.id,
    required this.businessName,
    required this.ruc,
    required this.hasValidSignature,
  });

  factory ApiCompany.fromJson(Map<String, dynamic> json) {
    return ApiCompany(
      id: intFrom(json['id']),
      businessName: stringFrom(json['business_name'], fallback: 'Empresa'),
      ruc: stringFrom(json['ruc'], fallback: '-'),
      hasValidSignature: json['has_valid_signature'] == true,
    );
  }
}

/// Represents an emission point within a company.
class ApiEmissionPoint {
  final int id;
  final String code;
  final String description;

  const ApiEmissionPoint({
    required this.id,
    required this.code,
    required this.description,
  });

  factory ApiEmissionPoint.fromJson(Map<String, dynamic> json) {
    return ApiEmissionPoint(
      id: intFrom(json['id']),
      code: stringFrom(json['code'], fallback: '-'),
      description: stringFrom(
        json['description'],
        fallback: 'Punto de emisión',
      ),
    );
  }
}
