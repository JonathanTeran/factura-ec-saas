import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../../data/models/api_exception.dart';
import '../../data/models/company_model.dart';
import '../../data/models/customer_model.dart';
import '../../data/models/dashboard_model.dart';
import '../../data/models/document_model.dart';
import '../../data/models/json_helpers.dart';
import '../../data/models/paginated_result.dart';
import '../../data/models/pos_model.dart';
import '../../data/models/product_model.dart';
import '../../data/models/purchase_model.dart';
import '../../data/models/subscription_model.dart';
import '../../data/models/supplier_model.dart';
import '../../data/models/user_model.dart';
import '../constants/api_constants.dart';
import 'api_client.dart';

// Re-export all model classes so existing `import 'v1_api_service.dart'`
// statements continue to compile without changes.
export '../../data/models/api_exception.dart';
export '../../data/models/company_model.dart';
export '../../data/models/customer_model.dart';
export '../../data/models/dashboard_model.dart';
export '../../data/models/document_model.dart';
export '../../data/models/paginated_result.dart';
export '../../data/models/pos_model.dart';
export '../../data/models/product_model.dart';
export '../../data/models/purchase_model.dart';
export '../../data/models/subscription_model.dart';
export '../../data/models/supplier_model.dart';
export '../../data/models/user_model.dart';

class CreateDocumentInput {
  final int companyId;
  final int customerId;
  final int emissionPointId;
  final String documentType;
  final ApiProduct product;
  final double quantity;

  const CreateDocumentInput({
    required this.companyId,
    required this.customerId,
    required this.emissionPointId,
    required this.documentType,
    required this.product,
    required this.quantity,
  });
}

/// Una línea (ítem) de una factura: producto + precio (editable) + cantidad +
/// descuento por línea.
class InvoiceLine {
  final ApiProduct product;
  final double quantity;
  final double discount;

  /// Precio unitario de esta línea. Por defecto el del producto, pero se puede
  /// modificar al agregarlo.
  final double unitPrice;

  const InvoiceLine({
    required this.product,
    required this.quantity,
    required this.unitPrice,
    this.discount = 0,
  });

  double get _qty => quantity <= 0 ? 1 : quantity;
  double get gross => unitPrice * _qty;
  double get lineDiscount => discount.clamp(0, gross).toDouble();
  double get base => gross - lineDiscount;
  double get taxRate => product.taxRate;
  double get taxValue => base * taxRate / 100;
  double get total => base + taxValue;
}

/// Una forma de pago SRI (código + monto).
class InvoicePayment {
  final String code;
  final double amount;

  const InvoicePayment({required this.code, required this.amount});
}

/// Formas de pago del SRI — el mismo catálogo y orden que usa el panel web.
const List<({String code, String label})> kSriPaymentMethods = [
  (code: '01', label: 'Sin utilización del sistema financiero'),
  (code: '15', label: 'Compensación de deudas'),
  (code: '16', label: 'Tarjeta de débito'),
  (code: '17', label: 'Dinero electrónico'),
  (code: '18', label: 'Tarjeta prepago'),
  (code: '19', label: 'Tarjeta de crédito'),
  (code: '20', label: 'Otros con utilización del sistema financiero'),
  (code: '21', label: 'Endoso de títulos'),
];

/// Entrada para crear una factura con varios ítems, formas de pago,
/// información adicional y propina — el "builder" completo.
class CreateInvoiceInput {
  final int companyId;
  final int customerId;
  final int emissionPointId;
  final String documentType;
  final List<InvoiceLine> lines;
  final List<InvoicePayment> payments;
  final int paymentTerm;
  final double tip;
  final List<({String name, String value})> additionalInfo;

  /// Solo para Nota de Crédito (04) y Nota de Débito (05): documento que se
  /// modifica y el motivo.
  final int? referenceDocumentId;
  final String? modificationReason;

  const CreateInvoiceInput({
    required this.companyId,
    required this.customerId,
    required this.emissionPointId,
    required this.documentType,
    required this.lines,
    this.payments = const [],
    this.paymentTerm = 0,
    this.tip = 0,
    this.additionalInfo = const [],
    this.referenceDocumentId,
    this.modificationReason,
  });
}

class OnboardingStatus {
  final bool completed;
  final bool hasCompany;
  final bool hasCertificate;
  final bool hasEstablishment;
  final bool hasSequentials;

  const OnboardingStatus({
    required this.completed,
    required this.hasCompany,
    required this.hasCertificate,
    required this.hasEstablishment,
    required this.hasSequentials,
  });
}

class RucEstablishment {
  final String code;
  final String? tradeName;
  final String? address;
  final bool isMain;

  const RucEstablishment({
    required this.code,
    this.tradeName,
    this.address,
    required this.isMain,
  });
}

class RucLookupResult {
  final String businessName;
  final String taxpayerType;
  final bool obligatedAccounting;
  final String regime;
  final String status;
  final List<RucEstablishment> establishments;

  const RucLookupResult({
    required this.businessName,
    required this.taxpayerType,
    required this.obligatedAccounting,
    required this.regime,
    required this.status,
    required this.establishments,
  });
}

/// Resultado de consultar el catastro del SRI por cédula (10) o RUC (13),
/// usado para autocompletar clientes/proveedores.
class SriIdentificationResult {
  final String businessName;
  final String? address;
  final String? tradeName;

  const SriIdentificationResult({
    required this.businessName,
    this.address,
    this.tradeName,
  });
}

class OnboardingCertInfo {
  final String subject;
  final int daysUntilExpiry;
  final DateTime? expiresAt;

  const OnboardingCertInfo({
    required this.subject,
    required this.daysUntilExpiry,
    this.expiresAt,
  });
}

class V1ApiService {
  static const String _accessTokenKey = 'access_token';

  final ApiClient _apiClient;
  final FlutterSecureStorage _storage = const FlutterSecureStorage();

  V1ApiService(this._apiClient);

  Future<bool> hasSession() async {
    final token = await _storage.read(key: _accessTokenKey);
    return token != null && token.isNotEmpty;
  }

  Future<void> clearSession() async {
    await _storage.delete(key: _accessTokenKey);
  }

  Future<AuthSession> login({
    required String email,
    required String password,
    String deviceName = 'macos-app',
  }) async {
    return _guard(() async {
      final response = await _apiClient.post<Map<String, dynamic>>(
        ApiConstants.login,
        data: {
          'email': email.trim(),
          'password': password,
          'device_name': deviceName,
        },
      );

      final data = _payloadMapFromResponse(response);
      final token = stringFrom(data['token']);
      if (token.isEmpty) {
        throw const ApiException('No se pudo iniciar sesión: token inválido.');
      }

      await _storage.write(key: _accessTokenKey, value: token);
      return AuthSession(
        user: ApiUser.fromJson(mapFrom(data['user'])),
        token: token,
        expiresAt: dateFrom(data['expires_at']),
      );
    });
  }

  Future<AuthSession> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String companyName,
    String deviceName = 'macos-app',
  }) async {
    return _guard(() async {
      final response = await _apiClient.post<Map<String, dynamic>>(
        ApiConstants.register,
        data: {
          'name': name.trim(),
          'email': email.trim(),
          'password': password,
          'password_confirmation': passwordConfirmation,
          'company_name': companyName.trim(),
          'device_name': deviceName,
        },
      );

      final data = _payloadMapFromResponse(response);
      final token = stringFrom(data['token']);
      if (token.isEmpty) {
        throw const ApiException('No se pudo crear la cuenta: token inválido.');
      }

      await _storage.write(key: _accessTokenKey, value: token);
      return AuthSession(
        user: ApiUser.fromJson(mapFrom(data['user'])),
        token: token,
        expiresAt: dateFrom(data['expires_at']),
      );
    });
  }

  /// Solicita el correo de recuperación de contraseña.
  Future<void> forgotPassword(String email) async {
    return _guard(() async {
      await _apiClient.post<Map<String, dynamic>>(
        '/auth/forgot-password',
        data: {'email': email.trim()},
      );
    });
  }

  /// Elimina la cuenta del usuario (requiere confirmar la contraseña).
  Future<void> deleteAccount(String password) async {
    return _guard(() async {
      await _apiClient.delete<Map<String, dynamic>>(
        '/auth/account',
        data: {'password': password},
      );
      await clearSession();
    });
  }

  Future<void> logout() async {
    try {
      await _apiClient.post<Map<String, dynamic>>(ApiConstants.logout);
    } catch (_) {
      // Si falla el backend, de todas formas limpiamos sesión local.
    } finally {
      await clearSession();
    }
  }

  Future<ApiUser> me() async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>('/auth/me');
      final data = _payloadMapFromResponse(response);
      return ApiUser.fromJson(mapFrom(data['user']));
    });
  }

  Future<List<ApiCompany>> companies() async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiConstants.companies,
      );
      final data = _payloadMapFromResponse(response);
      return listFrom(data['companies'])
          .map((item) => ApiCompany.fromJson(mapFrom(item)))
          .toList(growable: false);
    });
  }

  Future<List<ApiEmissionPoint>> companyEmissionPoints(int companyId) async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '${ApiConstants.companies}/$companyId${ApiConstants.emissionPoints}',
      );
      final data = _payloadMapFromResponse(response);
      return listFrom(data['emission_points'])
          .map((item) => ApiEmissionPoint.fromJson(mapFrom(item)))
          .toList(growable: false);
    });
  }

  Future<ApiCompany> createCompany(Map<String, dynamic> data) async {
    return _guard(() async {
      final response = await _apiClient.post<Map<String, dynamic>>(
        ApiConstants.companies,
        data: data,
      );
      final d = _payloadMapFromResponse(response);
      return ApiCompany.fromJson(mapFrom(d['company']));
    });
  }

  Future<ApiCompany> switchCompany(int companyId) async {
    return _guard(() async {
      final response = await _apiClient.post<Map<String, dynamic>>(
        '${ApiConstants.companies}/$companyId/switch',
      );
      final data = _payloadMapFromResponse(response);
      return ApiCompany.fromJson(mapFrom(data['company']));
    });
  }

  Future<ApiDashboardStats> dashboardStats() async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '${ApiConstants.dashboard}/stats',
      );
      final data = _payloadMapFromResponse(response);
      return ApiDashboardStats.fromJson(data);
    });
  }

  Future<List<ApiDocument>> recentDocuments() async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '${ApiConstants.dashboard}/recent-documents',
      );
      final data = _payloadMapFromResponse(response);
      return listFrom(data['documents'])
          .map((item) => ApiDocument.fromJson(mapFrom(item)))
          .toList(growable: false);
    });
  }

  Future<List<ApiChartPoint>> chartData({int days = 30}) async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '${ApiConstants.dashboard}/chart-data',
        queryParameters: {'days': days},
      );
      final data = _payloadMapFromResponse(response);
      return listFrom(data['daily'])
          .map((item) => ApiChartPoint.fromJson(mapFrom(item)))
          .toList(growable: false);
    });
  }

  Future<List<ApiTypeSummary>> chartByType({int days = 30}) async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '${ApiConstants.dashboard}/chart-data',
        queryParameters: {'days': days},
      );
      final data = _payloadMapFromResponse(response);
      return listFrom(data['by_type'])
          .map((item) => ApiTypeSummary.fromJson(mapFrom(item)))
          .toList(growable: false);
    });
  }

  Future<PaginatedResult<ApiDocument>> documents({
    String? status,
    String? search,
    String? documentType,
    int perPage = 15,
    int page = 1,
  }) async {
    return _guard(() async {
      final query = <String, dynamic>{'per_page': perPage, 'page': page};
      if (status != null && status.isNotEmpty) query['status'] = status;
      if (documentType != null && documentType.isNotEmpty) {
        query['document_type'] = documentType;
      }
      if (search != null && search.trim().isNotEmpty) {
        query['search'] = search.trim();
      }

      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiConstants.documents,
        queryParameters: query,
      );
      final body = _bodyFromResponse(response);
      final meta = mapFrom(body['meta']);
      final items = listFrom(body['data'])
          .map((item) => ApiDocument.fromJson(mapFrom(item)))
          .toList(growable: false);

      return PaginatedResult<ApiDocument>(
        items: items,
        currentPage: intFrom(meta['current_page']),
        lastPage: intFrom(meta['last_page']),
        total: intFrom(meta['total']),
        perPage: intFrom(meta['per_page']),
      );
    });
  }

  Future<PaginatedResult<ApiCustomer>> customers({
    String? search,
    int perPage = 15,
    int page = 1,
  }) async {
    return _guard(() async {
      final query = <String, dynamic>{'per_page': perPage, 'page': page};
      if (search != null && search.trim().isNotEmpty) {
        query['search'] = search.trim();
      }

      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiConstants.customers,
        queryParameters: query,
      );
      final body = _bodyFromResponse(response);
      final meta = mapFrom(body['meta']);
      final items = listFrom(body['data'])
          .map((item) => ApiCustomer.fromJson(mapFrom(item)))
          .toList(growable: false);

      return PaginatedResult<ApiCustomer>(
        items: items,
        currentPage: intFrom(meta['current_page']),
        lastPage: intFrom(meta['last_page']),
        total: intFrom(meta['total']),
        perPage: intFrom(meta['per_page']),
      );
    });
  }

  Future<PaginatedResult<ApiProduct>> products({
    String? search,
    String? type,
    bool activeOnly = true,
    int perPage = 15,
    int page = 1,
  }) async {
    return _guard(() async {
      final query = <String, dynamic>{
        'per_page': perPage,
        'page': page,
        'active_only': activeOnly ? 1 : 0,
      };
      if (search != null && search.trim().isNotEmpty) {
        query['search'] = search.trim();
      }
      if (type != null && type.isNotEmpty) query['type'] = type;

      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiConstants.products,
        queryParameters: query,
      );
      final body = _bodyFromResponse(response);
      final meta = mapFrom(body['meta']);
      final items = listFrom(body['data'])
          .map((item) => ApiProduct.fromJson(mapFrom(item)))
          .toList(growable: false);

      return PaginatedResult<ApiProduct>(
        items: items,
        currentPage: intFrom(meta['current_page']),
        lastPage: intFrom(meta['last_page']),
        total: intFrom(meta['total']),
        perPage: intFrom(meta['per_page']),
      );
    });
  }

  Future<ApiCustomer> createCustomer(Map<String, dynamic> data) async {
    return _guard(() async {
      final response = await _apiClient.post<Map<String, dynamic>>(
        ApiConstants.customers,
        data: data,
      );
      final payload = _payloadMapFromResponse(response);
      return ApiCustomer.fromJson(mapFrom(payload['customer']));
    });
  }

  Future<ApiProduct> createProduct(Map<String, dynamic> data) async {
    return _guard(() async {
      final response = await _apiClient.post<Map<String, dynamic>>(
        ApiConstants.products,
        data: data,
      );
      final payload = _payloadMapFromResponse(response);
      return ApiProduct.fromJson(mapFrom(payload['product']));
    });
  }

  Future<ReportsDashboardStats> reportsDashboard() async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '${ApiConstants.reports}/dashboard',
      );
      final data = _payloadMapFromResponse(response);
      return ReportsDashboardStats.fromJson(data);
    });
  }

  Future<Map<String, int>> documentsByStatus({
    required DateTime from,
    required DateTime to,
  }) async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '${ApiConstants.reports}/documents-by-status',
        queryParameters: {'from': dateOnly(from), 'to': dateOnly(to)},
      );
      final data = _payloadMapFromResponse(response);
      final statuses = mapFrom(data['statuses']);
      return statuses.map((key, value) => MapEntry(key, intFrom(value)));
    });
  }

  Future<ApiDocument> createDocument(CreateDocumentInput input) async {
    return _guard(() async {
      final quantity = input.quantity <= 0 ? 1.0 : input.quantity;
      final unitPrice = input.product.unitPrice;
      final subtotal = quantity * unitPrice;
      final taxRate = input.product.taxRate;
      final taxValue = subtotal * taxRate / 100;
      final total = subtotal + taxValue;
      final isNoTax = taxRate <= 0;
      final is12 = taxRate > 0 && taxRate <= 12.5;
      final is15 = taxRate > 12.5;

      final payload = <String, dynamic>{
        'company_id': input.companyId,
        'customer_id': input.customerId,
        'emission_point_id': input.emissionPointId,
        'document_type': input.documentType,
        'issue_date': dateOnly(DateTime.now()),
        'subtotal_no_tax': 0,
        'subtotal_0': isNoTax ? subtotal : 0,
        'subtotal_12': is12 ? subtotal : 0,
        'subtotal_15': is15 ? subtotal : 0,
        'tax_12': is12 ? taxValue : 0,
        'tax_15': is15 ? taxValue : 0,
        'discount': 0,
        'tip': 0,
        'total': total,
        'payment_method': '01',
        'payment_methods': [
          {'code': '01', 'amount': total},
        ],
        'payment_term': 0,
        'additional_info': <String>[],
        'items': [
          {
            'product_id': input.product.id,
            'main_code': input.product.code,
            'aux_code': null,
            'description': input.product.name,
            'quantity': quantity,
            'unit_price': unitPrice,
            'discount': 0,
            'subtotal': subtotal,
            'tax_code': input.product.taxCode,
            'tax_percentage_code': input.product.taxPercentageCode,
            'tax_rate': taxRate,
            'tax_base': subtotal,
            'tax_value': taxValue,
          },
        ],
      };

      final response = await _apiClient.post<Map<String, dynamic>>(
        ApiConstants.documents,
        data: payload,
      );
      final data = _payloadMapFromResponse(response);
      return ApiDocument.fromJson(mapFrom(data['document']));
    });
  }

  /// Crea una factura con varios ítems. Calcula los subtotales por tarifa de
  /// IVA, el IVA total, el descuento y el total, y arma el payload que el
  /// backend ya soporta (items[], payment_methods[], additional_info[]).
  Future<ApiDocument> createInvoice(CreateInvoiceInput input) async {
    return _guard(() async {
      double subtotal0 = 0, subtotal12 = 0, subtotal15 = 0;
      double tax12 = 0, tax15 = 0, totalDiscount = 0;
      final items = <Map<String, dynamic>>[];

      for (final line in input.lines) {
        final rate = line.taxRate;
        final base = line.base;
        final taxValue = line.taxValue;
        final isNoTax = rate <= 0;
        final is12 = rate > 0 && rate <= 12.5;
        final is15 = rate > 12.5;

        if (isNoTax) subtotal0 += base;
        if (is12) {
          subtotal12 += base;
          tax12 += taxValue;
        }
        if (is15) {
          subtotal15 += base;
          tax15 += taxValue;
        }
        totalDiscount += line.lineDiscount;

        items.add({
          'product_id': line.product.id,
          'main_code': line.product.code,
          'aux_code': null,
          'description': line.product.name,
          'quantity': line._qty,
          'unit_price': line.unitPrice,
          'discount': line.lineDiscount,
          'subtotal': base,
          'tax_code': line.product.taxCode,
          'tax_percentage_code': line.product.taxPercentageCode,
          'tax_rate': rate,
          'tax_base': base,
          'tax_value': taxValue,
        });
      }

      final totalTax = tax12 + tax15;
      final tip = input.tip < 0 ? 0.0 : input.tip;
      final total = subtotal0 + subtotal12 + subtotal15 + totalTax + tip;

      final payments = input.payments.isEmpty
          ? [
              {'code': '01', 'amount': total},
            ]
          : input.payments
                .map((p) => {'code': p.code, 'amount': p.amount})
                .toList();

      final payload = <String, dynamic>{
        'company_id': input.companyId,
        'customer_id': input.customerId,
        'emission_point_id': input.emissionPointId,
        'document_type': input.documentType,
        'issue_date': dateOnly(DateTime.now()),
        'subtotal_no_tax': 0,
        'subtotal_0': subtotal0,
        'subtotal_12': subtotal12,
        'subtotal_15': subtotal15,
        'total_tax': totalTax,
        'total_discount': totalDiscount,
        'tax_12': tax12,
        'tax_15': tax15,
        'discount': totalDiscount,
        'tip': tip,
        'total': total,
        'payment_method': payments.first['code'],
        'payment_methods': payments,
        'payment_term': input.paymentTerm,
        'additional_info': input.additionalInfo
            .map((e) => {'name': e.name, 'value': e.value})
            .toList(),
        'items': items,
        if (input.referenceDocumentId != null)
          'reference_document_id': input.referenceDocumentId,
        if (input.modificationReason != null &&
            input.modificationReason!.trim().isNotEmpty)
          'modification_reason': input.modificationReason!.trim(),
      };

      final response = await _apiClient.post<Map<String, dynamic>>(
        ApiConstants.documents,
        data: payload,
      );
      final data = _payloadMapFromResponse(response);
      return ApiDocument.fromJson(mapFrom(data['document']));
    });
  }

  Future<ApiDocumentDetail> getDocument(int documentId) async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '${ApiConstants.documents}/$documentId',
      );
      final data = _payloadMapFromResponse(response);
      return ApiDocumentDetail.fromJson(mapFrom(data['document']));
    });
  }

  Future<ApiDocument> sendDocument(int documentId) async {
    return _guard(() async {
      final response = await _apiClient.post<Map<String, dynamic>>(
        '${ApiConstants.documents}/$documentId/send',
      );
      final data = _payloadMapFromResponse(response);
      return ApiDocument.fromJson(mapFrom(data['document']));
    });
  }

  Future<ApiDocumentStatus> checkDocumentStatus(int documentId) async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '${ApiConstants.documents}/$documentId/status',
      );
      final data = _payloadMapFromResponse(response);
      return ApiDocumentStatus.fromJson(data);
    });
  }

  // ───────── SUPPLIERS ─────────

  Future<PaginatedResult<ApiSupplier>> suppliers({
    String? search,
    int perPage = 25,
    int page = 1,
  }) async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiConstants.suppliers,
        queryParameters: {
          'per_page': perPage,
          'page': page,
          if (search != null && search.isNotEmpty) 'search': search,
        },
      );
      final body = _bodyFromResponse(response);
      final items = listFrom(body['data'])
          .map((e) => ApiSupplier.fromJson(mapFrom(e)))
          .toList();
      final meta = mapFrom(body['meta']);
      return PaginatedResult<ApiSupplier>(
        items: items,
        currentPage: intFrom(meta['current_page']),
        lastPage: intFrom(meta['last_page']),
        total: intFrom(meta['total']),
        perPage: intFrom(meta['per_page']),
      );
    });
  }

  Future<ApiSupplier> createSupplier(Map<String, dynamic> data) async {
    return _guard(() async {
      final response = await _apiClient.post<Map<String, dynamic>>(
        ApiConstants.suppliers,
        data: data,
      );
      final payload = _payloadMapFromResponse(response);
      return ApiSupplier.fromJson(mapFrom(payload['supplier']));
    });
  }

  // ───────── PURCHASES ─────────

  Future<PaginatedResult<ApiPurchase>> purchases({
    String? status,
    int perPage = 25,
    int page = 1,
  }) async {
    return _guard(() async {
      final queryParameters = <String, dynamic>{
        'per_page': perPage,
        'page': page,
      };
      if (status != null && status.isNotEmpty) {
        queryParameters['status'] = status;
      }

      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiConstants.purchases,
        queryParameters: queryParameters,
      );
      final body = _bodyFromResponse(response);
      final items = listFrom(body['data'])
          .map((e) => ApiPurchase.fromJson(mapFrom(e)))
          .toList();
      final meta = mapFrom(body['meta']);
      return PaginatedResult<ApiPurchase>(
        items: items,
        currentPage: intFrom(meta['current_page']),
        lastPage: intFrom(meta['last_page']),
        total: intFrom(meta['total']),
        perPage: intFrom(meta['per_page']),
      );
    });
  }

  Future<ApiPurchase> createPurchase(Map<String, dynamic> data) async {
    return _guard(() async {
      final response = await _apiClient.post<Map<String, dynamic>>(
        ApiConstants.purchases,
        data: data,
      );
      final payload = _payloadMapFromResponse(response);
      return ApiPurchase.fromJson(payload);
    });
  }

  // ───────── POS ─────────

  Future<ApiPosSession?> posActiveSession() async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiConstants.posActiveSession,
      );
      final payload = _payloadMapFromResponse(response);
      if (payload.isEmpty || payload['session'] == null) return null;
      return ApiPosSession.fromJson(mapFrom(payload['session']));
    });
  }

  Future<ApiPosSession> posOpenSession({
    required int companyId,
    required int branchId,
    required int emissionPointId,
    double openingAmount = 0,
  }) async {
    return _guard(() async {
      final response = await _apiClient.post<Map<String, dynamic>>(
        ApiConstants.posOpenSession,
        data: {
          'company_id': companyId,
          'branch_id': branchId,
          'emission_point_id': emissionPointId,
          'opening_amount': openingAmount,
        },
      );
      final payload = _payloadMapFromResponse(response);
      return ApiPosSession.fromJson(mapFrom(payload['session']));
    });
  }

  Future<ApiPosSession> posCloseSession(
    int sessionId, {
    required double closingAmount,
    String? notes,
  }) async {
    return _guard(() async {
      final data = <String, dynamic>{
        'closing_amount': closingAmount,
      };
      if (notes != null && notes.isNotEmpty) {
        data['closing_notes'] = notes;
      }

      final response = await _apiClient.post<Map<String, dynamic>>(
        '${ApiConstants.posSessions}/$sessionId/close',
        data: data,
      );
      final payload = _payloadMapFromResponse(response);
      return ApiPosSession.fromJson(mapFrom(payload['session']));
    });
  }

  Future<ApiPosTransaction> posCreateTransaction(
    int sessionId, {
    required String paymentMethod,
    required List<Map<String, dynamic>> items,
    double amountReceived = 0,
    int? customerId,
  }) async {
    return _guard(() async {
      final data = <String, dynamic>{
        'payment_method': paymentMethod,
        'items': items,
        'amount_received': amountReceived,
      };
      if (customerId != null) {
        data['customer_id'] = customerId;
      }

      final response = await _apiClient.post<Map<String, dynamic>>(
        '${ApiConstants.posSessions}/$sessionId/transactions',
        data: data,
      );
      final payload = _payloadMapFromResponse(response);
      return ApiPosTransaction.fromJson(mapFrom(payload['transaction']));
    });
  }

  Future<List<ApiPosTransaction>> posTransactions(int sessionId) async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '${ApiConstants.posSessions}/$sessionId/transactions',
      );
      final body = _bodyFromResponse(response);
      return listFrom(body['data'])
          .map((e) => ApiPosTransaction.fromJson(mapFrom(e)))
          .toList();
    });
  }

  Future<void> posVoidTransaction(int transactionId) async {
    return _guard(() async {
      await _apiClient.post<Map<String, dynamic>>(
        '${ApiConstants.pos}/transactions/$transactionId/void',
      );
    });
  }

  Future<List<ApiPosSession>> posSessions({int perPage = 25}) async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiConstants.posSessions,
        queryParameters: {'per_page': perPage},
      );
      final body = _bodyFromResponse(response);
      return listFrom(body['data'])
          .map((e) => ApiPosSession.fromJson(mapFrom(e)))
          .toList();
    });
  }

  // ───────── SUBSCRIPTIONS & BILLING ─────────

  /// Fetch all available subscription plans.
  Future<List<ApiPlan>> plans() async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiConstants.subscriptionPlans,
      );
      final data = _payloadMapFromResponse(response);
      return listFrom(data['plans'])
          .map((item) => ApiPlan.fromJson(mapFrom(item)))
          .toList(growable: false);
    });
  }

  /// Fetch the current user's active subscription.
  Future<ApiSubscription?> currentSubscription() async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiConstants.subscriptionCurrent,
      );
      final data = _payloadMapFromResponse(response);
      if (data.isEmpty || data['subscription'] == null) return null;
      return ApiSubscription.fromJson(mapFrom(data['subscription']));
    });
  }

  /// Subscribe via bank transfer, uploading a receipt image.
  Future<ApiSubscription> subscribeBankTransfer({
    required int planId,
    required String billingCycle,
    required String receiptFilePath,
    required String transferReference,
    required String billingName,
    required String billingEmail,
  }) async {
    return _guard(() async {
      final formData = FormData.fromMap({
        'plan_id': planId,
        'billing_cycle': billingCycle,
        'transfer_reference': transferReference,
        'billing_name': billingName,
        'billing_email': billingEmail,
        'transfer_receipt': await MultipartFile.fromFile(receiptFilePath),
      });

      final response = await _apiClient.post<Map<String, dynamic>>(
        ApiConstants.subscriptionBankTransfer,
        data: formData,
      );
      final data = _payloadMapFromResponse(response);
      return ApiSubscription.fromJson(mapFrom(data['subscription']));
    });
  }

  /// Cancel the current subscription.
  Future<void> cancelSubscription() async {
    return _guard(() async {
      await _apiClient.post<Map<String, dynamic>>(
        ApiConstants.subscriptionCancel,
      );
    });
  }

  /// Resume a cancelled subscription.
  Future<void> resumeSubscription() async {
    return _guard(() async {
      await _apiClient.post<Map<String, dynamic>>(
        ApiConstants.subscriptionResume,
      );
    });
  }

  /// Fetch the list of payments.
  Future<List<ApiPayment>> payments() async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiConstants.subscriptionPayments,
      );
      final body = _bodyFromResponse(response);
      return listFrom(body['data'])
          .map((item) => ApiPayment.fromJson(mapFrom(item)))
          .toList(growable: false);
    });
  }

  /// Fetch the status of a specific payment.
  Future<ApiPayment> paymentStatus(int paymentId) async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '${ApiConstants.subscriptionPaymentStatus}/$paymentId',
      );
      final data = _payloadMapFromResponse(response);
      return ApiPayment.fromJson(mapFrom(data['payment']));
    });
  }

  /// Fetch the list of bank accounts for transfer payments.
  Future<List<ApiBankAccount>> bankAccounts() async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiConstants.subscriptionBankAccounts,
      );
      final data = _payloadMapFromResponse(response);
      return listFrom(data['bank_accounts'])
          .map((item) => ApiBankAccount.fromJson(mapFrom(item)))
          .toList(growable: false);
    });
  }

  /// Validate a coupon code for a discount.
  Future<Map<String, dynamic>> validateCoupon(String code) async {
    return _guard(() async {
      final response = await _apiClient.post<Map<String, dynamic>>(
        ApiConstants.subscriptionValidateCoupon,
        data: {'code': code},
      );
      return _payloadMapFromResponse(response);
    });
  }

  // ───────── INTERNAL HELPERS ─────────

  // ==================== ONBOARDING ====================

  Future<OnboardingStatus> onboardingStatus() async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '/onboarding/status',
      );
      final data = _payloadMapFromResponse(response);
      bool b(dynamic v) => v == true || v == 1 || v == '1';
      return OnboardingStatus(
        completed: b(data['completed']),
        hasCompany: b(data['has_company']),
        hasCertificate: b(data['has_certificate']),
        hasEstablishment: b(data['has_establishment']),
        hasSequentials: b(data['has_sequentials']),
      );
    });
  }

  Future<RucLookupResult> lookupRuc(String ruc) async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '/sri/ruc/$ruc',
      );
      final data = _payloadMapFromResponse(response);
      bool b(dynamic v) => v == true || v == 1 || v == '1';
      String? orNull(dynamic v) {
        final s = stringFrom(v);
        return s.isEmpty ? null : s;
      }

      final establishments = listFrom(data['establishments']).map((item) {
        final m = mapFrom(item);
        return RucEstablishment(
          code: stringFrom(m['code']),
          tradeName: orNull(m['trade_name']),
          address: orNull(m['address']),
          isMain: b(m['is_main']),
        );
      }).toList(growable: false);

      return RucLookupResult(
        businessName: stringFrom(data['business_name']),
        taxpayerType: stringFrom(data['taxpayer_type']),
        obligatedAccounting: b(data['obligated_accounting']),
        regime: stringFrom(data['regime']),
        status: stringFrom(data['status']),
        establishments: establishments,
      );
    });
  }

  /// Consulta el catastro público del SRI por cédula (10 dígitos) o RUC (13).
  /// Devuelve razón social y dirección de la matriz para autocompletar.
  Future<SriIdentificationResult> lookupIdentification(
    String identification,
  ) async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '/sri/identification/$identification',
      );
      final data = _payloadMapFromResponse(response);
      String? orNull(dynamic v) {
        final s = stringFrom(v);
        return s.isEmpty ? null : s;
      }

      return SriIdentificationResult(
        businessName: stringFrom(data['business_name']),
        address: orNull(data['address']),
        tradeName: orNull(data['trade_name']),
      );
    });
  }

  Future<void> saveOnboardingCompany(Map<String, dynamic> data) async {
    return _guard(() async {
      await _apiClient.post<Map<String, dynamic>>(
        '/onboarding/company',
        data: data,
      );
    });
  }

  Future<int?> saveOnboardingEstablishment(Map<String, dynamic> data) async {
    return _guard(() async {
      final response = await _apiClient.post<Map<String, dynamic>>(
        '/onboarding/establishment',
        data: data,
      );
      final d = _payloadMapFromResponse(response);
      final ep = mapFrom(d['emission_point']);
      return ep['id'] == null ? null : intFrom(ep['id']);
    });
  }

  Future<OnboardingCertInfo> uploadOnboardingCertificate({
    required String filePath,
    required String password,
  }) async {
    return _guard(() async {
      final formData = FormData.fromMap({
        'certificate': await MultipartFile.fromFile(filePath),
        'password': password,
      });
      final response = await _apiClient.post<Map<String, dynamic>>(
        '/onboarding/certificate',
        data: formData,
      );
      final data = _payloadMapFromResponse(response);
      return OnboardingCertInfo(
        subject: stringFrom(data['signature_subject']),
        daysUntilExpiry: intFrom(data['days_until_expiry']),
        expiresAt: dateFrom(data['signature_expires_at']),
      );
    });
  }

  Future<void> saveOnboardingSequentials({
    required int emissionPointId,
    required List<Map<String, dynamic>> sequentials,
  }) async {
    return _guard(() async {
      await _apiClient.post<Map<String, dynamic>>(
        '/onboarding/sequentials',
        data: {
          'emission_point_id': emissionPointId,
          'sequentials': sequentials,
        },
      );
    });
  }

  Future<void> completeOnboarding() async {
    return _guard(() async {
      await _apiClient.post<Map<String, dynamic>>('/onboarding/complete');
    });
  }

  Future<T> _guard<T>(Future<T> Function() run) async {
    try {
      return await run();
    } on DioException catch (error) {
      throw _apiExceptionFromDio(error);
    }
  }

  ApiException _apiExceptionFromDio(DioException error) {
    final status = error.response?.statusCode;
    final body = error.response?.data;
    if (body is Map) {
      final map = body.map((key, value) => MapEntry(key.toString(), value));
      final message = stringFrom(map['message'], fallback: 'Error de red');
      final errors = map['errors'];
      return ApiException(
        message,
        statusCode: status,
        details: errors is Map
            ? errors.map((key, value) => MapEntry(key.toString(), value))
            : null,
      );
    }

    return ApiException(error.message ?? 'Error de red', statusCode: status);
  }

  Map<String, dynamic> _bodyFromResponse(Response<dynamic> response) {
    final body = mapFrom(response.data);
    if (body['success'] == false) {
      throw ApiException(
        stringFrom(
          body['message'],
          fallback: 'No se pudo procesar la respuesta',
        ),
        statusCode: response.statusCode,
        details: body['errors'] is Map ? mapFrom(body['errors']) : null,
      );
    }
    return body;
  }

  Map<String, dynamic> _payloadMapFromResponse(Response<dynamic> response) {
    final body = _bodyFromResponse(response);
    final payload = body['data'];
    if (payload is Map<String, dynamic>) return payload;
    if (payload is Map) {
      return payload.map((key, value) => MapEntry(key.toString(), value));
    }
    return <String, dynamic>{};
  }
}
