import 'json_helpers.dart';

/// Represents a customer (cliente) from the API.
class ApiCustomer {
  final int id;
  final String name;
  final String identificationNumber;
  final String identificationType; // etiqueta (para mostrar)
  final String identificationTypeCode; // código SRI (para editar)
  final String? email;
  final String? phone;
  final String? address;
  final bool isActive;

  const ApiCustomer({
    required this.id,
    required this.name,
    required this.identificationNumber,
    required this.identificationType,
    required this.identificationTypeCode,
    required this.email,
    required this.phone,
    required this.address,
    required this.isActive,
  });

  factory ApiCustomer.fromJson(Map<String, dynamic> json) {
    return ApiCustomer(
      id: intFrom(json['id']),
      name: stringFrom(json['name'], fallback: 'Cliente'),
      identificationNumber: stringFrom(
        json['identification_number'],
        fallback: '-',
      ),
      identificationType: stringFrom(
        json['identification_type_label'],
        fallback: 'ID',
      ),
      identificationTypeCode: stringFrom(
        json['identification_type'],
        fallback: '05',
      ),
      email: nullableStringFrom(json['email']),
      phone: nullableStringFrom(json['phone']),
      address: nullableStringFrom(json['address']),
      isActive: json['is_active'] == null ? true : json['is_active'] == true,
    );
  }
}
