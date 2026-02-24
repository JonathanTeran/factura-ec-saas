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
      return ApiSupplier.fromJson(payload);
    });
  }

  // ───────── PURCHASES ─────────

  Future<PaginatedResult<ApiPurchase>> purchases({
    String? status,
    int perPage = 25,
    int page = 1,
  }) async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiConstants.purchases,
        queryParameters: {
          'per_page': perPage,
          'page': page,
          'status': ?status,
        },
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

  Future<ApiPosSession> posCloseSession(int sessionId, {
    required double closingAmount,
    String? notes,
  }) async {
    return _guard(() async {
      final response = await _apiClient.post<Map<String, dynamic>>(
        '${ApiConstants.posSessions}/$sessionId/close',
        data: {
          'closing_amount': closingAmount,
          'closing_notes': ?notes,
        },
      );
      final payload = _payloadMapFromResponse(response);
      return ApiPosSession.fromJson(mapFrom(payload['session']));
    });
  }

  Future<ApiPosTransaction> posCreateTransaction(int sessionId, {
    required String paymentMethod,
    required List<Map<String, dynamic>> items,
    double amountReceived = 0,
    int? customerId,
  }) async {
    return _guard(() async {
      final response = await _apiClient.post<Map<String, dynamic>>(
        '${ApiConstants.posSessions}/$sessionId/transactions',
        data: {
          'payment_method': paymentMethod,
          'items': items,
          'amount_received': amountReceived,
          'customer_id': ?customerId,
        },
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
      final data = _payloadMapFromResponse(response);
      return listFrom(data['payments'])
          .map((item) => ApiPayment.fromJson(mapFrom(item)))
          .toList(growable: false);
    });
  }

  /// Fetch the status of a specific payment.
  Future<ApiPayment> paymentStatus(int paymentId) async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '${ApiConstants.subscriptionPayments}/$paymentId',
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
