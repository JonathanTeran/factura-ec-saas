import 'json_helpers.dart';

/// Represents a supplier (proveedor) from the API.
class ApiSupplier {
  final int id;
  final String identificationType;
  final String identification;
  final String businessName;
  final String? tradeName;
  final String? email;
  final String? phone;
  final String? address;
  final bool isActive;
  final double totalPurchased;

  const ApiSupplier({
    required this.id,
    required this.identificationType,
    required this.identification,
    required this.businessName,
    this.tradeName,
    this.email,
    this.phone,
    this.address,
    required this.isActive,
    required this.totalPurchased,
  });

  factory ApiSupplier.fromJson(Map<String, dynamic> json) {
    return ApiSupplier(
      id: intFrom(json['id']),
      identificationType: stringFrom(json['identification_type']),
      identification: stringFrom(json['identification']),
      businessName: stringFrom(json['business_name']),
      tradeName: nullableStringFrom(json['trade_name']),
      email: nullableStringFrom(json['email']),
      phone: nullableStringFrom(json['phone']),
      address: nullableStringFrom(json['address']),
      isActive: json['is_active'] == true,
      totalPurchased: doubleFrom(json['total_purchased']),
    );
  }
}
