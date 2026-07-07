import 'json_helpers.dart';

/// Convierte un mensaje del SRI (mapa `{identificador, mensaje,
/// informacionAdicional}` o texto) en una línea legible para mostrar.
String sriMessageToText(dynamic item) {
  if (item is Map) {
    final id = stringFrom(item['identificador']);
    final msg = stringFrom(
      item['mensaje'],
      fallback: stringFrom(item['message']),
    );
    final extra = stringFrom(
      item['informacionAdicional'],
      fallback: stringFrom(item['info_adicional']),
    );
    final head = [
      if (id.isNotEmpty) '[$id]',
      if (msg.isNotEmpty) msg,
    ].join(' ').trim();
    if (head.isEmpty && extra.isEmpty) return item.toString();
    return extra.isEmpty ? head : '$head — $extra';
  }
  return item.toString();
}

/// Represents an electronic document (factura, nota de crédito, etc.).
class ApiDocument {
  final int id;
  final String documentType;
  final String documentTypeLabel;
  final String documentNumber;
  final String issuer;
  final double total;
  final DateTime? issueDate;
  final String status;
  final String statusLabel;
  final String? accessKey;

  const ApiDocument({
    required this.id,
    required this.documentType,
    required this.documentTypeLabel,
    required this.documentNumber,
    required this.issuer,
    required this.total,
    required this.issueDate,
    required this.status,
    required this.statusLabel,
    required this.accessKey,
  });

  factory ApiDocument.fromJson(Map<String, dynamic> json) {
    final customer = nullableMapFrom(json['customer']);
    final company = nullableMapFrom(json['company']);
    final issuer = stringFrom(
      customer?['name'],
      fallback: stringFrom(
        company?['business_name'],
        fallback: 'Sin cliente',
      ),
    );

    return ApiDocument(
      id: intFrom(json['id']),
      documentType: stringFrom(json['document_type']),
      documentTypeLabel: stringFrom(
        json['document_type_label'],
        fallback: 'Documento',
      ),
      documentNumber: stringFrom(
        json['document_number'],
        fallback: 'Sin número',
      ),
      issuer: issuer,
      total: doubleFrom(json['total']),
      issueDate: dateFrom(json['issue_date'] ?? json['issue_datetime']),
      status: stringFrom(json['status'], fallback: 'draft'),
      statusLabel: stringFrom(json['status_label'], fallback: 'Borrador'),
      accessKey: nullableStringFrom(json['access_key']),
    );
  }
}

/// A single line item inside an electronic document.
class ApiDocumentItem {
  final int id;
  final int? productId;
  final String description;
  final String? mainCode;
  final double quantity;
  final double unitPrice;
  final double discount;
  final double subtotal;
  final double taxRate;
  final double taxValue;
  final String taxCode;
  final String taxPercentageCode;

  const ApiDocumentItem({
    required this.id,
    required this.productId,
    required this.description,
    required this.mainCode,
    required this.quantity,
    required this.unitPrice,
    required this.discount,
    required this.subtotal,
    required this.taxRate,
    required this.taxValue,
    required this.taxCode,
    required this.taxPercentageCode,
  });

  factory ApiDocumentItem.fromJson(Map<String, dynamic> json) {
    return ApiDocumentItem(
      id: intFrom(json['id']),
      productId: nullableIntFrom(json['product_id']),
      description: stringFrom(json['description'], fallback: 'Ítem'),
      mainCode: nullableStringFrom(json['main_code']),
      quantity: doubleFrom(json['quantity']),
      unitPrice: doubleFrom(json['unit_price']),
      discount: doubleFrom(json['discount']),
      subtotal: doubleFrom(json['subtotal']),
      taxRate: doubleFrom(json['tax_rate']),
      taxValue: doubleFrom(json['tax_value']),
      taxCode: stringFrom(json['tax_code'], fallback: '2'),
      taxPercentageCode: stringFrom(json['tax_percentage_code'], fallback: ''),
    );
  }
}

/// Full detail of an electronic document, including line items and totals.
class ApiDocumentDetail {
  final int id;
  final String documentType;
  final String documentTypeLabel;
  final String documentNumber;
  final String? accessKey;
  final String environmentLabel;
  final String customerName;
  final DateTime? issueDate;
  final String currency;
  final double subtotalNoTax;
  final double subtotal0;
  final double subtotal12;
  final double subtotal15;
  final double totalDiscount;
  final double totalTax;
  final double tip;
  final double total;
  final String status;
  final String statusLabel;
  final String? authorizationNumber;
  final DateTime? authorizationDate;
  final List<String> sriMessages;
  final List<String> errorDetails;
  final bool contingencyActive;
  final String? contingencyMessage;
  final bool hasRide;
  final bool hasXml;
  final bool emailSent;
  final DateTime? emailSentAt;
  final String? emailSentTo;
  final String? customerEmail;
  final int? customerId;
  final List<({String code, double amount})> paymentMethods;
  final List<({String name, String value})> additionalInfoPairs;
  final List<ApiDocumentItem> items;

  const ApiDocumentDetail({
    required this.id,
    required this.documentType,
    required this.documentTypeLabel,
    required this.documentNumber,
    required this.accessKey,
    required this.environmentLabel,
    required this.customerName,
    required this.issueDate,
    required this.currency,
    required this.subtotalNoTax,
    required this.subtotal0,
    required this.subtotal12,
    required this.subtotal15,
    required this.totalDiscount,
    required this.totalTax,
    required this.tip,
    required this.total,
    required this.status,
    required this.statusLabel,
    required this.authorizationNumber,
    required this.authorizationDate,
    required this.sriMessages,
    required this.errorDetails,
    required this.contingencyActive,
    required this.contingencyMessage,
    required this.hasRide,
    required this.hasXml,
    required this.emailSent,
    required this.emailSentAt,
    required this.emailSentTo,
    required this.customerEmail,
    required this.customerId,
    required this.paymentMethods,
    required this.additionalInfoPairs,
    required this.items,
  });

  factory ApiDocumentDetail.fromJson(Map<String, dynamic> json) {
    final customer = nullableMapFrom(json['customer']);
    final company = nullableMapFrom(json['company']);
    final customerName = stringFrom(
      customer?['name'],
      fallback: stringFrom(
        company?['business_name'],
        fallback: 'Consumidor final',
      ),
    );

    final messages = listFrom(json['sri_messages'])
        .map(sriMessageToText)
        .where((s) => s.trim().isNotEmpty)
        .toList(growable: false);

    // error_details ya viene armado por el backend (fatal + mensajes SRI).
    final errors = listFrom(json['error_details'])
        .map((e) => stringFrom(e))
        .where((s) => s.trim().isNotEmpty)
        .toList(growable: false);

    final items = listFrom(json['items'])
        .map((item) => ApiDocumentItem.fromJson(mapFrom(item)))
        .toList(growable: false);

    final payments = listFrom(json['payment_methods'])
        .map((p) {
          final m = mapFrom(p);
          return (code: stringFrom(m['code'], fallback: '01'), amount: doubleFrom(m['amount']));
        })
        .toList(growable: false);

    // additional_info puede venir como lista [{name,value}] o como mapa {k:v}.
    final rawAdditional = json['additional_info'];
    final additional = <({String name, String value})>[];
    if (rawAdditional is List) {
      for (final e in rawAdditional) {
        final m = mapFrom(e);
        additional.add((
          name: stringFrom(m['name']),
          value: stringFrom(m['value']),
        ));
      }
    } else if (rawAdditional is Map) {
      rawAdditional.forEach((k, v) {
        additional.add((name: k.toString(), value: v?.toString() ?? ''));
      });
    }

    return ApiDocumentDetail(
      id: intFrom(json['id']),
      documentType: stringFrom(json['document_type']),
      documentTypeLabel: stringFrom(
        json['document_type_label'],
        fallback: 'Documento',
      ),
      documentNumber: stringFrom(
        json['document_number'],
        fallback: 'Sin número',
      ),
      accessKey: nullableStringFrom(json['access_key']),
      environmentLabel: stringFrom(json['environment_label']),
      customerName: customerName,
      issueDate: dateFrom(json['issue_date'] ?? json['issue_datetime']),
      currency: stringFrom(json['currency'], fallback: 'USD'),
      subtotalNoTax: doubleFrom(json['subtotal_no_tax']),
      subtotal0: doubleFrom(json['subtotal_0']),
      subtotal12: doubleFrom(json['subtotal_12']),
      subtotal15: doubleFrom(json['subtotal_15']),
      totalDiscount: doubleFrom(json['total_discount']),
      totalTax: doubleFrom(json['total_tax']),
      tip: doubleFrom(json['tip']),
      total: doubleFrom(json['total']),
      status: stringFrom(json['status'], fallback: 'draft'),
      statusLabel: stringFrom(json['status_label'], fallback: 'Borrador'),
      authorizationNumber: nullableStringFrom(json['authorization_number']),
      authorizationDate: dateFrom(json['authorization_date']),
      sriMessages: messages,
      errorDetails: errors,
      contingencyActive: json['contingency_active'] == true,
      contingencyMessage: nullableStringFrom(json['contingency_message']),
      hasRide: json['has_ride'] == true,
      hasXml: json['has_xml'] == true,
      emailSent: json['email_sent'] == true,
      emailSentAt: dateFrom(json['email_sent_at']),
      emailSentTo: nullableStringFrom(json['email_sent_to']),
      customerEmail: nullableStringFrom(customer?['email']),
      customerId: customer == null ? null : nullableIntFrom(customer['id']),
      paymentMethods: payments,
      additionalInfoPairs: additional,
      items: items,
    );
  }
}

/// Represents the SRI authorization status of a document.
class ApiDocumentStatus {
  final String status;
  final String statusLabel;
  final String? authorizationNumber;
  final DateTime? authorizationDate;
  final List<String> sriMessages;

  const ApiDocumentStatus({
    required this.status,
    required this.statusLabel,
    required this.authorizationNumber,
    required this.authorizationDate,
    required this.sriMessages,
  });

  factory ApiDocumentStatus.fromJson(Map<String, dynamic> json) {
    final messages = listFrom(json['sri_messages'])
        .map(sriMessageToText)
        .where((s) => s.trim().isNotEmpty)
        .toList(growable: false);

    return ApiDocumentStatus(
      status: stringFrom(json['status'], fallback: 'processing'),
      statusLabel: stringFrom(json['status_label'], fallback: 'Procesando'),
      authorizationNumber: nullableStringFrom(json['authorization_number']),
      authorizationDate: dateFrom(json['authorization_date']),
      sriMessages: messages,
    );
  }
}

/// Enlace temporal a un archivo del documento (RIDE/PDF o XML firmado).
class DocumentFileLink {
  final String url;
  final String filename;

  const DocumentFileLink({required this.url, required this.filename});

  factory DocumentFileLink.fromJson(
    Map<String, dynamic> json, {
    required String fallbackName,
  }) {
    return DocumentFileLink(
      url: stringFrom(json['url']),
      filename: stringFrom(json['filename'], fallback: fallbackName),
    );
  }
}
