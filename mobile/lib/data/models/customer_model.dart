import 'json_helpers.dart';

/// Represents a customer (cliente) from the API.
class ApiCustomer {
  final int id;
  final String name;
  final String identificationNumber;
  final String identificationType;
  final String? email;
  final String? phone;

  const ApiCustomer({
    required this.id,
    required this.name,
    required this.identificationNumber,
    required this.identificationType,
    required this.email,
    required this.phone,
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
      email: nullableStringFrom(json['email']),
      phone: nullableStringFrom(json['phone']),
    );
  }
}
