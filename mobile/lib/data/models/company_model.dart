import 'json_helpers.dart';

/// Represents a company (emisor) from the API.
class ApiCompany {
  final int id;
  final String businessName;
  final String ruc;
  final bool hasValidSignature;

  /// Ambiente SRI: '2' = Producción, cualquier otro (típicamente '1') = Pruebas.
  final String sriEnvironment;
  final String sriEnvironmentLabel;

  const ApiCompany({
    required this.id,
    required this.businessName,
    required this.ruc,
    required this.hasValidSignature,
    required this.sriEnvironment,
    required this.sriEnvironmentLabel,
  });

  /// true cuando la empresa emite en Producción (comprobantes con validez
  /// tributaria); false en Pruebas/certificación.
  bool get isProduction => sriEnvironment == '2';

  factory ApiCompany.fromJson(Map<String, dynamic> json) {
    final env = stringFrom(json['sri_environment'], fallback: '1');
    return ApiCompany(
      id: intFrom(json['id']),
      businessName: stringFrom(json['business_name'], fallback: 'Empresa'),
      ruc: stringFrom(json['ruc'], fallback: '-'),
      hasValidSignature: json['has_valid_signature'] == true,
      sriEnvironment: env,
      sriEnvironmentLabel: stringFrom(
        json['sri_environment_label'],
        fallback: env == '2' ? 'Producción' : 'Pruebas',
      ),
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
