import 'json_helpers.dart';

/// Represents a product or service from the API.
class ApiProduct {
  final int id;
  final String code;
  final String name;
  final String typeLabel;
  final double unitPrice;
  final double taxRate;
  final String taxCode;
  final String taxPercentageCode;
  final bool trackInventory;
  final int? stock;

  const ApiProduct({
    required this.id,
    required this.code,
    required this.name,
    required this.typeLabel,
    required this.unitPrice,
    required this.taxRate,
    required this.taxCode,
    required this.taxPercentageCode,
    required this.trackInventory,
    required this.stock,
  });

  factory ApiProduct.fromJson(Map<String, dynamic> json) {
    final track = json['track_inventory'] == true;
    return ApiProduct(
      id: intFrom(json['id']),
      code: stringFrom(json['code'], fallback: '-'),
      name: stringFrom(json['name'], fallback: 'Producto'),
      typeLabel: stringFrom(json['type_label'], fallback: 'Producto'),
      unitPrice: doubleFrom(json['unit_price']),
      taxRate: doubleFrom(json['tax_rate']),
      taxCode: stringFrom(json['tax_code'], fallback: '2'),
      taxPercentageCode: stringFrom(
        json['tax_percentage_code'],
        fallback: taxCodeFromRate(doubleFrom(json['tax_rate'])),
      ),
      trackInventory: track,
      stock: track ? nullableIntFrom(json['stock']) : null,
    );
  }
}
