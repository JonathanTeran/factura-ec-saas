import 'json_helpers.dart';

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
    final messages = listFrom(
      json['sri_messages'],
    ).map((item) => item.toString()).toList(growable: false);

    return ApiDocumentStatus(
      status: stringFrom(json['status'], fallback: 'processing'),
      statusLabel: stringFrom(json['status_label'], fallback: 'Procesando'),
      authorizationNumber: nullableStringFrom(json['authorization_number']),
      authorizationDate: dateFrom(json['authorization_date']),
      sriMessages: messages,
    );
  }
}
