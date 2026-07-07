import 'json_helpers.dart';

/// Mapea el régimen del catastro del SRI (p. ej. 'rimpe_emprendedor',
/// 'rimpe_popular', 'general') al valor de `rimpe_type` de la empresa.
String rimpeTypeFromRegime(String regime) {
  final r = regime.toLowerCase();
  if (r.contains('popular')) return 'negocio_popular';
  if (r.contains('emprendedor') || r.contains('rimpe')) return 'emprendedor';
  return 'none';
}

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
  final int branchId;
  final String code;
  final String description;
  final bool isActive;

  const ApiEmissionPoint({
    required this.id,
    required this.branchId,
    required this.code,
    required this.description,
    required this.isActive,
  });

  factory ApiEmissionPoint.fromJson(Map<String, dynamic> json) {
    return ApiEmissionPoint(
      id: intFrom(json['id']),
      branchId: intFrom(json['branch_id']),
      code: stringFrom(json['code'], fallback: '-'),
      description: stringFrom(
        json['description'],
        fallback: 'Punto de emisión',
      ),
      isActive: json['is_active'] == null ? true : json['is_active'] == true,
    );
  }
}

/// Establecimiento (sucursal) de una empresa, con sus puntos de emisión.
class ApiBranch {
  final int id;
  final int companyId;
  final String code;
  final String name;
  final String address;
  final bool isMain;
  final bool isActive;
  final List<ApiEmissionPoint> emissionPoints;

  const ApiBranch({
    required this.id,
    required this.companyId,
    required this.code,
    required this.name,
    required this.address,
    required this.isMain,
    required this.isActive,
    required this.emissionPoints,
  });

  factory ApiBranch.fromJson(Map<String, dynamic> json) {
    return ApiBranch(
      id: intFrom(json['id']),
      companyId: intFrom(json['company_id']),
      code: stringFrom(json['code'], fallback: '-'),
      name: stringFrom(json['name'], fallback: 'Establecimiento'),
      address: stringFrom(json['address']),
      isMain: json['is_main'] == true,
      isActive: json['is_active'] == null ? true : json['is_active'] == true,
      emissionPoints: listFrom(json['emission_points'])
          .map((e) => ApiEmissionPoint.fromJson(mapFrom(e)))
          .toList(growable: false),
    );
  }
}

/// Secuencial de un punto de emisión para un tipo de comprobante.
class ApiSequential {
  final String documentType;
  final String label;
  final int currentNumber;
  final int nextNumber;

  const ApiSequential({
    required this.documentType,
    required this.label,
    required this.currentNumber,
    required this.nextNumber,
  });

  factory ApiSequential.fromJson(Map<String, dynamic> json) {
    final current = intFrom(json['current_number']);
    return ApiSequential(
      documentType: stringFrom(json['document_type']),
      label: stringFrom(json['document_type_label'], fallback: 'Comprobante'),
      currentNumber: current,
      nextNumber: json['next_number'] == null
          ? current + 1
          : intFrom(json['next_number']),
    );
  }
}
