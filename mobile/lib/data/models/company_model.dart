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

  // Datos editables de la empresa.
  final String tradeName;
  final String address;
  final String phone;
  final String email;
  final String taxpayerType; // natural | juridical | rise
  final String rimpeType; // none | emprendedor | negocio_popular
  final bool obligatedAccounting;
  final bool specialTaxpayer;
  final String? logoUrl;

  const ApiCompany({
    required this.id,
    required this.businessName,
    required this.ruc,
    required this.hasValidSignature,
    required this.sriEnvironment,
    required this.sriEnvironmentLabel,
    required this.tradeName,
    required this.address,
    required this.phone,
    required this.email,
    required this.taxpayerType,
    required this.rimpeType,
    required this.obligatedAccounting,
    required this.specialTaxpayer,
    required this.logoUrl,
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
      tradeName: stringFrom(json['trade_name']),
      address: stringFrom(json['address']),
      phone: stringFrom(json['phone']),
      email: stringFrom(json['email']),
      taxpayerType: stringFrom(json['taxpayer_type'], fallback: 'natural'),
      rimpeType: stringFrom(json['rimpe_type'], fallback: 'none'),
      obligatedAccounting: json['is_accounting_required'] == true ||
          json['obligated_accounting'] == true,
      specialTaxpayer: json['is_special_taxpayer'] == true ||
          json['special_taxpayer'] == true,
      logoUrl: nullableStringFrom(json['logo_url']),
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
