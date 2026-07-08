import 'json_helpers.dart';

/// Línea de detalle de una proforma/cotización.
class ApiQuoteItem {
  final int? id;
  final int? productId;
  final String description;
  final double quantity;
  final double unitPrice;
  final double discount;
  final double taxRate;
  final double subtotal;
  final double taxValue;
  final double total;

  const ApiQuoteItem({
    this.id,
    this.productId,
    required this.description,
    required this.quantity,
    required this.unitPrice,
    this.discount = 0,
    this.taxRate = 0,
    required this.subtotal,
    required this.taxValue,
    required this.total,
  });

  factory ApiQuoteItem.fromJson(Map<String, dynamic> json) {
    return ApiQuoteItem(
      id: json['id'] == null ? null : intFrom(json['id']),
      productId: json['product_id'] == null ? null : intFrom(json['product_id']),
      description: stringFrom(json['description']),
      quantity: doubleFrom(json['quantity']),
      unitPrice: doubleFrom(json['unit_price']),
      discount: doubleFrom(json['discount']),
      taxRate: doubleFrom(json['tax_rate']),
      subtotal: doubleFrom(json['subtotal']),
      taxValue: doubleFrom(json['tax_value']),
      total: doubleFrom(json['total']),
    );
  }

  Map<String, dynamic> toPayload() {
    return {
      if (productId != null) 'product_id': productId,
      'description': description,
      'quantity': quantity,
      'unit_price': unitPrice,
      'discount': discount,
      'tax_rate': taxRate,
      'subtotal': subtotal,
      'tax_value': taxValue,
      'total': total,
    };
  }
}

/// Proforma/cotización (propuesta comercial antes de facturar).
class ApiQuote {
  final int id;
  final String quoteNumber;
  final String status; // draft | sent | accepted | rejected | invoiced
  final String? statusLabel;
  final DateTime? issueDate;
  final DateTime? expiryDate;
  final double subtotal;
  final double totalDiscount;
  final double totalTax;
  final double total;
  final String? notes;
  final String? paymentTerms;
  final int? convertedToDocumentId;
  final int? customerId;
  final String customerName;
  final List<ApiQuoteItem> items;

  const ApiQuote({
    required this.id,
    required this.quoteNumber,
    required this.status,
    this.statusLabel,
    this.issueDate,
    this.expiryDate,
    required this.subtotal,
    required this.totalDiscount,
    required this.totalTax,
    required this.total,
    this.notes,
    this.paymentTerms,
    this.convertedToDocumentId,
    this.customerId,
    required this.customerName,
    this.items = const [],
  });

  factory ApiQuote.fromJson(Map<String, dynamic> json) {
    final customer = json['customer'] is Map ? mapFrom(json['customer']) : const <String, dynamic>{};

    return ApiQuote(
      id: intFrom(json['id']),
      quoteNumber: stringFrom(json['quote_number']),
      status: stringFrom(json['status'], fallback: 'draft'),
      statusLabel: nullableStringFrom(json['status_label']),
      issueDate: dateFrom(json['issue_date']),
      expiryDate: dateFrom(json['expiry_date']),
      subtotal: doubleFrom(json['subtotal']),
      totalDiscount: doubleFrom(json['total_discount']),
      totalTax: doubleFrom(json['total_tax']),
      total: doubleFrom(json['total']),
      notes: nullableStringFrom(json['notes']),
      paymentTerms: nullableStringFrom(json['payment_terms']),
      convertedToDocumentId: json['converted_to_document_id'] == null
          ? null
          : intFrom(json['converted_to_document_id']),
      customerId: json['customer_id'] == null ? null : intFrom(json['customer_id']),
      customerName: stringFrom(
        customer['business_name'] ?? customer['name'],
        fallback: 'Sin cliente',
      ),
      items: listFrom(json['items'])
          .whereType<Map>()
          .map((e) => ApiQuoteItem.fromJson(mapFrom(e)))
          .toList(growable: false),
    );
  }

  bool get isDraft => status == 'draft';
  bool get isSent => status == 'sent';
  bool get isAccepted => status == 'accepted';
  bool get isConverted => convertedToDocumentId != null;
}
