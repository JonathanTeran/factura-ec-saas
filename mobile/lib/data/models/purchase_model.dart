import 'json_helpers.dart';

/// Represents a purchase (compra) from the API.
class ApiPurchase {
  final int id;
  final int supplierId;
  final String? supplierName;
  final String documentType;
  final String supplierDocumentNumber;
  final DateTime? issueDate;
  final double subtotal15;
  final double subtotal0;
  final double totalTax;
  final double total;
  final String status;

  const ApiPurchase({
    required this.id,
    required this.supplierId,
    this.supplierName,
    required this.documentType,
    required this.supplierDocumentNumber,
    this.issueDate,
    required this.subtotal15,
    required this.subtotal0,
    required this.totalTax,
    required this.total,
    required this.status,
  });

  factory ApiPurchase.fromJson(Map<String, dynamic> json) {
    return ApiPurchase(
      id: intFrom(json['id']),
      supplierId: intFrom(json['supplier_id']),
      supplierName: nullableStringFrom(json['supplier']?['business_name']),
      documentType: stringFrom(json['document_type']),
      supplierDocumentNumber: stringFrom(json['supplier_document_number']),
      issueDate: dateFrom(json['issue_date']),
      subtotal15: doubleFrom(json['subtotal_15']),
      subtotal0: doubleFrom(json['subtotal_0']),
      totalTax: doubleFrom(json['total_tax']),
      total: doubleFrom(json['total']),
      status: stringFrom(json['status']),
    );
  }
}
