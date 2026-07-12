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
import '../../data/models/quote_model.dart';
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
export '../../data/models/quote_model.dart';
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

/// Códigos de retención del SRI (renta e IVA) con su porcentaje, tal como el
/// catálogo del backend (config/sri.php).
const List<({String code, double percentage, String name})> kRentaRetentionCodes = [
  (code: '303', percentage: 10, name: 'Honorarios profesionales'),
  (code: '304', percentage: 8, name: 'Servicios predomina intelecto (sin título)'),
  (code: '307', percentage: 2, name: 'Servicios predomina mano de obra'),
  (code: '308', percentage: 2, name: 'Servicios entre sociedades'),
  (code: '309', percentage: 1, name: 'Publicidad y comunicación'),
  (code: '310', percentage: 1, name: 'Transporte privado / carga'),
  (code: '312', percentage: 1, name: 'Transferencia de bienes muebles'),
  (code: '319', percentage: 1, name: 'Arrendamiento mercantil'),
  (code: '320', percentage: 8, name: 'Arrendamiento bienes inmuebles'),
  (code: '322', percentage: 1.75, name: 'Seguros y reaseguros'),
  (code: '323', percentage: 2, name: 'Rendimientos financieros'),
  (code: '332', percentage: 0, name: 'No sujetas a retención'),
  (code: '341', percentage: 1, name: 'Otras retenciones 1%'),
  (code: '342', percentage: 2, name: 'Otras retenciones 2%'),
  (code: '343', percentage: 8, name: 'Otras retenciones 8%'),
  (code: '344', percentage: 25, name: 'Otras retenciones 25%'),
];

const List<({String code, double percentage, String name})> kIvaRetentionCodes = [
  (code: '721', percentage: 30, name: 'Retención 30% del IVA'),
  (code: '723', percentage: 70, name: 'Retención 70% del IVA'),
  (code: '725', percentage: 100, name: 'Retención 100% del IVA'),
];

/// Tipos de documento de sustento (para el comprobante de retención).
const List<({String code, String label})> kSupportDocTypes = [
  (code: '01', label: 'Factura'),
  (code: '03', label: 'Liquidación de compra'),
  (code: '02', label: 'Nota de venta'),
  (code: '12', label: 'Documento instituciones financieras'),
  (code: '11', label: 'Pasajes transporte'),
  (code: '41', label: 'Comprobante de reembolso'),
];

/// Una línea de retención dentro de un comprobante de retención.
class WithholdingLine {
  final String taxType; // 'renta' | 'iva'
  final String code; // código de retención
  final double base; // base imponible
  final double rate; // porcentaje

  const WithholdingLine({
    required this.taxType,
    required this.code,
    required this.base,
    required this.rate,
  });

  double get retained => base * rate / 100;
}

/// Destinatario de una Guía de Remisión.
class WaybillRecipient {
  final String identification;
  final String name;
  final String address;
  final String reason; // motivo de traslado
  final String? route; // ruta (opcional)

  const WaybillRecipient({
    required this.identification,
    required this.name,
    required this.address,
    required this.reason,
    this.route,
  });
}

/// Entrada para crear una Guía de Remisión (06).
class CreateWaybillInput {
  final int companyId;
  final int customerId;
  final int emissionPointId;
  final List<InvoiceLine> lines; // bienes trasladados
  final String startAddress; // dirección de partida
  final String carrierName; // razón social transportista
  final String carrierId; // RUC/cédula transportista
  final String carrierIdType; // '04' RUC, '05' cédula, '06' pasaporte
  final String plate;
  final DateTime startDate;
  final DateTime endDate;
  final WaybillRecipient recipient;

  const CreateWaybillInput({
    required this.companyId,
    required this.customerId,
    required this.emissionPointId,
    required this.lines,
    required this.startAddress,
    required this.carrierName,
    required this.carrierId,
    required this.carrierIdType,
    required this.plate,
    required this.startDate,
    required this.endDate,
    required this.recipient,
  });
}

/// Entrada para crear un Comprobante de Retención (07).
class CreateRetentionInput {
  final int companyId;
  final int customerId;
  final int emissionPointId;
  final String supportDocCode; // tipo de documento sustento
  final String supportDocNumber; // 001-001-000000001
  final DateTime supportDocDate;
  final double supportDocTotal;
  final List<WithholdingLine> withholdings;
  final List<({String name, String value})> additionalInfo;

  const CreateRetentionInput({
    required this.companyId,
    required this.customerId,
    required this.emissionPointId,
    required this.supportDocCode,
    required this.supportDocNumber,
    required this.supportDocDate,
    required this.supportDocTotal,
    required this.withholdings,
    this.additionalInfo = const [],
  });
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

  /// true si el establecimiento está ABIERTO en el catastro del SRI; false si
  /// está cerrado (en ese caso se desactiva al importar).
  final bool isOpen;

  const RucEstablishment({
    required this.code,
    this.tradeName,
    this.address,
    required this.isMain,
    required this.isOpen,
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

/// Estado de la firma electrónica (certificado .p12) de la empresa.
class SignatureStatus {
  final String status; // missing | unknown | expired | expiring_soon | valid
  final String? message;
  final int? daysRemaining;
  final DateTime? expiresAt;
  final String? subject;

  const SignatureStatus({
    required this.status,
    this.message,
    this.daysRemaining,
    this.expiresAt,
    this.subject,
  });

  bool get hasCertificate => status != 'missing' && status != 'unknown';
  bool get isValid => status == 'valid' || status == 'expiring_soon';
  bool get isExpired => status == 'expired';
}

/// Plantillas de documento/correo de la empresa (asunto, mensaje y pie del RIDE).
class DocumentSettings {
  final bool autoSendEmail;
  final String emailSubject;
  final String emailMessage;
  final String rideFooter;

  const DocumentSettings({
    required this.autoSendEmail,
    required this.emailSubject,
    required this.emailMessage,
    required this.rideFooter,
  });

  factory DocumentSettings.fromJson(Map<String, dynamic> json) {
    return DocumentSettings(
      autoSendEmail: json['auto_send_email'] == true,
      emailSubject: stringFrom(json['email_subject']),
      emailMessage: stringFrom(json['email_message']),
      rideFooter: stringFrom(json['ride_footer']),
    );
  }
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
          // La UI solo llama register() tras marcar el checkbox de aceptación.
          'terms': true,
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

  /// Actualiza el perfil del usuario (nombre y teléfono).
  Future<ApiUser> updateProfile({String? name, String? phone}) async {
    return _guard(() async {
      final response = await _apiClient.put<Map<String, dynamic>>(
        '/profile',
        data: {
          if (name != null) 'name': name.trim(),
          'phone': (phone == null || phone.trim().isEmpty)
              ? null
              : phone.trim(),
        },
      );
      final data = _payloadMapFromResponse(response);
      return ApiUser.fromJson(mapFrom(data['user']));
    });
  }

  /// Cambia la contraseña de la cuenta (requiere la contraseña actual).
  Future<void> updatePassword({
    required String currentPassword,
    required String password,
    required String passwordConfirmation,
  }) async {
    return _guard(() async {
      await _apiClient.put<Map<String, dynamic>>(
        '/profile/password',
        data: {
          'current_password': currentPassword,
          'password': password,
          'password_confirmation': passwordConfirmation,
        },
      );
    });
  }

  /// Plantillas de documento/correo de la empresa activa.
  Future<DocumentSettings> documentSettings() async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '/document-settings',
      );
      return DocumentSettings.fromJson(_payloadMapFromResponse(response));
    });
  }

  /// Guarda las plantillas de documento/correo de la empresa activa.
  Future<DocumentSettings> updateDocumentSettings({
    required bool autoSendEmail,
    required String emailSubject,
    required String emailMessage,
    required String rideFooter,
  }) async {
    return _guard(() async {
      final response = await _apiClient.put<Map<String, dynamic>>(
        '/document-settings',
        data: {
          'auto_send_email': autoSendEmail,
          'email_subject': emailSubject.trim(),
          'email_message': emailMessage.trim(),
          'ride_footer': rideFooter.trim(),
        },
      );
      return DocumentSettings.fromJson(_payloadMapFromResponse(response));
    });
  }

  // ==================== Proformas / Cotizaciones ====================

  Future<PaginatedResult<ApiQuote>> quotes({
    String? search,
    String? status,
    int perPage = 15,
    int page = 1,
  }) async {
    return _guard(() async {
      final query = <String, dynamic>{'per_page': perPage, 'page': page};
      if (search != null && search.trim().isNotEmpty) {
        query['search'] = search.trim();
      }
      if (status != null && status.isNotEmpty) {
        query['status'] = status;
      }

      final response = await _apiClient.get<Map<String, dynamic>>(
        '/quotes',
        queryParameters: query,
      );
      final body = _bodyFromResponse(response);
      final meta = mapFrom(body['meta']);
      final items = listFrom(body['data'])
          .map((item) => ApiQuote.fromJson(mapFrom(item)))
          .toList(growable: false);

      return PaginatedResult<ApiQuote>(
        items: items,
        currentPage: intFrom(meta['current_page']),
        lastPage: intFrom(meta['last_page']),
        total: intFrom(meta['total']),
        perPage: intFrom(meta['per_page']),
      );
    });
  }

  Future<ApiQuote> createQuote({
    required int companyId,
    required int customerId,
    required DateTime issueDate,
    DateTime? expiryDate,
    required List<ApiQuoteItem> items,
    String? notes,
    String? paymentTerms,
  }) async {
    return _guard(() async {
      final subtotal = items.fold<double>(0, (s, i) => s + i.subtotal);
      final totalDiscount = items.fold<double>(0, (s, i) => s + i.discount);
      final totalTax = items.fold<double>(0, (s, i) => s + i.taxValue);
      final total = items.fold<double>(0, (s, i) => s + i.total);

      String d(DateTime v) =>
          '${v.year.toString().padLeft(4, '0')}-${v.month.toString().padLeft(2, '0')}-${v.day.toString().padLeft(2, '0')}';

      final response = await _apiClient.post<Map<String, dynamic>>(
        '/quotes',
        data: {
          'company_id': companyId,
          'customer_id': customerId,
          'issue_date': d(issueDate),
          if (expiryDate != null) 'expiry_date': d(expiryDate),
          'subtotal': double.parse(subtotal.toStringAsFixed(2)),
          'total_discount': double.parse(totalDiscount.toStringAsFixed(2)),
          'total_tax': double.parse(totalTax.toStringAsFixed(2)),
          'total': double.parse(total.toStringAsFixed(2)),
          if (notes != null && notes.trim().isNotEmpty) 'notes': notes.trim(),
          if (paymentTerms != null && paymentTerms.trim().isNotEmpty)
            'payment_terms': paymentTerms.trim(),
          'items': items.map((i) => i.toPayload()).toList(),
        },
      );
      final data = _payloadMapFromResponse(response);
      return ApiQuote.fromJson(mapFrom(data['quote']));
    });
  }

  /// Acción sobre una proforma: 'send' | 'accept' | 'reject'.
  Future<ApiQuote> quoteAction(int quoteId, String action) async {
    return _guard(() async {
      final response = await _apiClient.post<Map<String, dynamic>>(
        '/quotes/$quoteId/$action',
      );
      final data = _payloadMapFromResponse(response);
      return ApiQuote.fromJson(mapFrom(data['quote']));
    });
  }

  Future<void> deleteQuote(int quoteId) async {
    return _guard(() async {
      await _apiClient.delete<Map<String, dynamic>>('/quotes/$quoteId');
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

  /// Actualiza los datos de una empresa (PUT). Requiere el conjunto completo de
  /// campos fiscales (razón social, RUC, dirección, tipo, ambiente, correo…).
  Future<ApiCompany> updateCompany(int companyId, Map<String, dynamic> data) async {
    return _guard(() async {
      final response = await _apiClient.put<Map<String, dynamic>>(
        '${ApiConstants.companies}/$companyId',
        data: data,
      );
      final d = _payloadMapFromResponse(response);
      return ApiCompany.fromJson(mapFrom(d['company']));
    });
  }

  /// Sube el logo de la empresa (imagen, máx 2 MB) y devuelve su URL pública.
  Future<String> uploadCompanyLogo(int companyId, String filePath) async {
    return _guard(() async {
      final formData = FormData.fromMap({
        'logo': await MultipartFile.fromFile(filePath),
      });
      final response = await _apiClient.post<Map<String, dynamic>>(
        '${ApiConstants.companies}/$companyId/logo',
        data: formData,
      );
      final d = _payloadMapFromResponse(response);
      return stringFrom(d['logo_url']);
    });
  }

  // ───────── ESTABLECIMIENTOS / PUNTOS DE EMISIÓN / SECUENCIALES ─────────

  /// Establecimientos (sucursales) de la empresa, con sus puntos de emisión.
  Future<List<ApiBranch>> branches(int companyId) async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '${ApiConstants.companies}/$companyId/branches',
      );
      final d = _payloadMapFromResponse(response);
      return listFrom(d['branches'])
          .map((e) => ApiBranch.fromJson(mapFrom(e)))
          .toList(growable: false);
    });
  }

  Future<void> createBranch(int companyId, Map<String, dynamic> data) async {
    return _guard(() async {
      await _apiClient.post<Map<String, dynamic>>(
        '${ApiConstants.companies}/$companyId/branches',
        data: {...data, 'company_id': companyId},
      );
    });
  }

  Future<void> updateBranch(
    int companyId,
    int branchId,
    Map<String, dynamic> data,
  ) async {
    return _guard(() async {
      await _apiClient.put<Map<String, dynamic>>(
        '${ApiConstants.companies}/$companyId/branches/$branchId',
        data: {...data, 'company_id': companyId},
      );
    });
  }

  Future<void> createEmissionPoint(int branchId, Map<String, dynamic> data) async {
    return _guard(() async {
      await _apiClient.post<Map<String, dynamic>>(
        '${ApiConstants.branches}/$branchId/emission-points',
        data: {...data, 'branch_id': branchId},
      );
    });
  }

  Future<void> updateEmissionPoint(
    int branchId,
    int emissionPointId,
    Map<String, dynamic> data,
  ) async {
    return _guard(() async {
      await _apiClient.put<Map<String, dynamic>>(
        '${ApiConstants.branches}/$branchId/emission-points/$emissionPointId',
        data: {...data, 'branch_id': branchId},
      );
    });
  }

  Future<void> deleteEmissionPoint(int branchId, int emissionPointId) async {
    return _guard(() async {
      await _apiClient.delete<Map<String, dynamic>>(
        '${ApiConstants.branches}/$branchId/emission-points/$emissionPointId',
      );
    });
  }

  Future<void> deleteBranch(int companyId, int branchId) async {
    return _guard(() async {
      await _apiClient.delete<Map<String, dynamic>>(
        '${ApiConstants.companies}/$companyId/branches/$branchId',
      );
    });
  }

  /// Secuenciales existentes de un punto de emisión (por tipo de comprobante).
  Future<List<ApiSequential>> emissionPointSequentials(int emissionPointId) async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '/onboarding/sequentials',
        queryParameters: {'emission_point_id': emissionPointId},
      );
      final d = _payloadMapFromResponse(response);
      return listFrom(d['sequentials'])
          .map((e) => ApiSequential.fromJson(mapFrom(e)))
          .toList(growable: false);
    });
  }

  /// Fija el último número usado por tipo de comprobante (migración a
  /// producción sin repetir números). El próximo emitido será last_number + 1.
  Future<void> saveEmissionPointSequentials(
    int emissionPointId,
    List<({String documentType, int lastNumber})> sequentials,
  ) async {
    return _guard(() async {
      await _apiClient.post<Map<String, dynamic>>(
        '/onboarding/sequentials',
        data: {
          'emission_point_id': emissionPointId,
          'sequentials': sequentials
              .map((s) => {
                    'document_type': s.documentType,
                    'last_number': s.lastNumber,
                  })
              .toList(),
        },
      );
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

  /// Cambia el ambiente SRI de la empresa ('1' Pruebas, '2' Producción).
  Future<ApiCompany> updateCompanyEnvironment(
    int companyId,
    String environment,
  ) async {
    return _guard(() async {
      final response = await _apiClient.post<Map<String, dynamic>>(
        '${ApiConstants.companies}/$companyId/environment',
        data: {'environment': environment},
      );
      final data = _payloadMapFromResponse(response);
      return ApiCompany.fromJson(mapFrom(data['company']));
    });
  }

  /// Elimina definitivamente los comprobantes emitidos en ambiente de PRUEBAS
  /// (sin validez fiscal): documentos, XML/RIDE y sus asientos contables.
  /// Devuelve cuántos se eliminaron.
  Future<int> purgeTestDocuments(int companyId) async {
    return _guard(() async {
      final response = await _apiClient.delete<Map<String, dynamic>>(
        '${ApiConstants.companies}/$companyId/test-documents',
        data: {'confirm': true},
      );
      final data = _payloadMapFromResponse(response);
      return intFrom(data['deleted']);
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

  Future<ApiCustomer> updateCustomer(int id, Map<String, dynamic> data) async {
    return _guard(() async {
      final response = await _apiClient.put<Map<String, dynamic>>(
        '${ApiConstants.customers}/$id',
        data: data,
      );
      final payload = _payloadMapFromResponse(response);
      return ApiCustomer.fromJson(mapFrom(payload['customer']));
    });
  }

  Future<void> deleteCustomer(int id) async {
    return _guard(() async {
      await _apiClient.delete<Map<String, dynamic>>(
        '${ApiConstants.customers}/$id',
      );
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

  Future<ApiProduct> updateProduct(int id, Map<String, dynamic> data) async {
    return _guard(() async {
      final response = await _apiClient.put<Map<String, dynamic>>(
        '${ApiConstants.products}/$id',
        data: data,
      );
      final payload = _payloadMapFromResponse(response);
      return ApiProduct.fromJson(mapFrom(payload['product']));
    });
  }

  Future<void> deleteProduct(int id) async {
    return _guard(() async {
      await _apiClient.delete<Map<String, dynamic>>(
        '${ApiConstants.products}/$id',
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

  /// Clientes con mayor facturación autorizada en el período.
  Future<List<ApiTopCustomer>> topCustomers({
    required DateTime from,
    required DateTime to,
    int limit = 5,
  }) async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '${ApiConstants.reports}/top-customers',
        queryParameters: {
          'from': dateOnly(from),
          'to': dateOnly(to),
          'limit': limit,
        },
      );
      final data = _payloadMapFromResponse(response);
      return listFrom(data['customers'])
          .whereType<Map>()
          .map((e) => ApiTopCustomer.fromJson(mapFrom(e)))
          .toList(growable: false);
    });
  }

  /// Productos más vendidos (facturas autorizadas) en el período.
  Future<List<ApiTopProduct>> topProducts({
    required DateTime from,
    required DateTime to,
    int limit = 5,
  }) async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '${ApiConstants.reports}/top-products',
        queryParameters: {
          'from': dateOnly(from),
          'to': dateOnly(to),
          'limit': limit,
        },
      );
      final data = _payloadMapFromResponse(response);
      return listFrom(data['products'])
          .whereType<Map>()
          .map((e) => ApiTopProduct.fromJson(mapFrom(e)))
          .toList(growable: false);
    });
  }

  /// Clave de subtotal para una línea, agrupando por codigoPorcentaje del SRI
  /// (fuente autoritativa que distingue 0% / no objeto / exento aunque
  /// compartan tarifa 0). Si no hay código, cae a la tarifa numérica.
  static String _subtotalKeyFor(String? code, double rate) {
    switch (code) {
      case '0':
        return 'subtotal_0';
      case '5':
        return 'subtotal_5';
      case '8':
        return 'subtotal_8';
      case '2':
        return 'subtotal_12';
      case '10':
        return 'subtotal_13';
      case '4':
        return 'subtotal_15';
      case '6':
      case '7':
        return 'subtotal_no_tax';
    }
    if (rate <= 0) return 'subtotal_0';
    if (rate <= 5) return 'subtotal_5';
    if (rate <= 8) return 'subtotal_8';
    if (rate <= 12.5) return 'subtotal_12';
    if (rate <= 13.5) return 'subtotal_13';
    return 'subtotal_15';
  }

  /// Mapa de subtotales por tarifa inicializado en 0 (todas las claves que el
  /// backend acepta).
  static Map<String, double> _emptySubtotals() => {
        'subtotal_no_tax': 0,
        'subtotal_0': 0,
        'subtotal_5': 0,
        'subtotal_8': 0,
        'subtotal_12': 0,
        'subtotal_13': 0,
        'subtotal_15': 0,
      };

  Future<ApiDocument> createDocument(CreateDocumentInput input) async {
    return _guard(() async {
      final quantity = input.quantity <= 0 ? 1.0 : input.quantity;
      final unitPrice = input.product.unitPrice;
      final subtotal = quantity * unitPrice;
      final taxRate = input.product.taxRate;
      final taxValue = subtotal * taxRate / 100;
      final total = subtotal + taxValue;

      final subtotals = _emptySubtotals();
      final key = _subtotalKeyFor(input.product.taxPercentageCode, taxRate);
      subtotals[key] = (subtotals[key] ?? 0) + subtotal;

      final payload = <String, dynamic>{
        'company_id': input.companyId,
        'customer_id': input.customerId,
        'emission_point_id': input.emissionPointId,
        'document_type': input.documentType,
        'issue_date': dateOnly(DateTime.now()),
        ...subtotals,
        'total_tax': taxValue,
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
  /// Arma el payload de una factura (y tipos con ítems) a partir del input.
  Map<String, dynamic> _invoicePayload(CreateInvoiceInput input) {
    final subtotals = _emptySubtotals();
    double totalTax = 0, totalDiscount = 0;
    final items = <Map<String, dynamic>>[];

    for (final line in input.lines) {
      final rate = line.taxRate;
      final base = line.base;
      final taxValue = line.taxValue;

      final key = _subtotalKeyFor(line.product.taxPercentageCode, rate);
      subtotals[key] = (subtotals[key] ?? 0) + base;
      totalTax += taxValue;
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

    final tip = input.tip < 0 ? 0.0 : input.tip;
    final subtotalsSum = subtotals.values.fold(0.0, (s, v) => s + v);
    final total = subtotalsSum + totalTax + tip;

    final payments = input.payments.isEmpty
        ? [
            {'code': '01', 'amount': total},
          ]
        : input.payments
              .map((p) => {'code': p.code, 'amount': p.amount})
              .toList();

    return <String, dynamic>{
      'company_id': input.companyId,
      'customer_id': input.customerId,
      'emission_point_id': input.emissionPointId,
      'document_type': input.documentType,
      'issue_date': dateOnly(DateTime.now()),
      ...subtotals,
      'total_tax': totalTax,
      'total_discount': totalDiscount,
      'discount': totalDiscount,
      'tip': tip,
      'total': total,
      'payment_method': payments.first['code'],
      'payment_methods': payments,
      'payment_term': input.paymentTerm,
      // El RIDE renderiza la info adicional como mapa {clave: valor}.
      'additional_info': {
        for (final e in input.additionalInfo)
          if (e.name.trim().isNotEmpty) e.name.trim(): e.value,
      },
      'items': items,
      if (input.referenceDocumentId != null)
        'reference_document_id': input.referenceDocumentId,
      if (input.modificationReason != null &&
          input.modificationReason!.trim().isNotEmpty)
        'modification_reason': input.modificationReason!.trim(),
    };
  }

  Future<ApiDocument> createInvoice(CreateInvoiceInput input) async {
    return _guard(() async {
      final response = await _apiClient.post<Map<String, dynamic>>(
        ApiConstants.documents,
        data: _invoicePayload(input),
      );
      final data = _payloadMapFromResponse(response);
      return ApiDocument.fromJson(mapFrom(data['document']));
    });
  }

  /// Actualiza un borrador con ítems (PUT). Reemplaza ítems y datos según el
  /// mismo payload que la creación. Solo funciona en documentos editables.
  Future<ApiDocument> updateInvoice(int documentId, CreateInvoiceInput input) async {
    return _guard(() async {
      final response = await _apiClient.put<Map<String, dynamic>>(
        '${ApiConstants.documents}/$documentId',
        data: _invoicePayload(input),
      );
      final data = _payloadMapFromResponse(response);
      return ApiDocument.fromJson(mapFrom(data['document']));
    });
  }

  /// Crea un Comprobante de Retención (07). No lleva ítems; lleva
  /// withholding_details[] con el documento sustento y cada retención.
  Future<ApiDocument> createRetention(CreateRetentionInput input) async {
    return _guard(() async {
      final total = input.withholdings.fold<double>(
        0,
        (s, w) => s + w.retained,
      );
      final supportDate = dateOnly(input.supportDocDate);

      final details = input.withholdings
          .map(
            (w) => {
              'support_doc_code': input.supportDocCode,
              'support_doc_number': input.supportDocNumber,
              'support_doc_date': supportDate,
              'support_doc_total': input.supportDocTotal,
              'tax_type': w.taxType,
              'retention_code': w.code,
              'tax_base': w.base,
              'retention_rate': w.rate,
              'retained_value': w.retained,
            },
          )
          .toList();

      final payload = <String, dynamic>{
        'company_id': input.companyId,
        'customer_id': input.customerId,
        'emission_point_id': input.emissionPointId,
        'document_type': '07',
        'issue_date': dateOnly(DateTime.now()),
        'subtotal_no_tax': 0,
        'subtotal_0': 0,
        'total_tax': 0,
        'total_discount': 0,
        'discount': 0,
        'tip': 0,
        'total': total,
        'additional_info': input.additionalInfo
            .map((e) => {'name': e.name, 'value': e.value})
            .toList(),
        'withholding_details': details,
      };

      final response = await _apiClient.post<Map<String, dynamic>>(
        ApiConstants.documents,
        data: payload,
      );
      final data = _payloadMapFromResponse(response);
      return ApiDocument.fromJson(mapFrom(data['document']));
    });
  }

  /// Crea una Guía de Remisión (06). Lleva ítems (los bienes) y los datos de
  /// transporte + destinatarios en additional_info (mapa), tal como espera
  /// DocumentBuilder::waybill y la librería amephia/sri-ec.
  Future<ApiDocument> createWaybill(CreateWaybillInput input) async {
    return _guard(() async {
      String ddmmyyyy(DateTime d) =>
          '${d.day.toString().padLeft(2, '0')}/${d.month.toString().padLeft(2, '0')}/${d.year}';

      final items = <Map<String, dynamic>>[];
      final detalles = <Map<String, dynamic>>[];
      for (final line in input.lines) {
        // Los bienes de una guía no llevan valor monetario en el XML.
        items.add({
          'product_id': line.product.id,
          'main_code': line.product.code,
          'aux_code': null,
          'description': line.product.name,
          'quantity': line._qty,
          'unit_price': 0,
          'discount': 0,
          'subtotal': 0,
          'tax_code': line.product.taxCode,
          'tax_percentage_code': line.product.taxPercentageCode,
          'tax_rate': 0,
          'tax_base': 0,
          'tax_value': 0,
        });
        detalles.add({
          'codigoInterno': line.product.code,
          'descripcion': line.product.name,
          'cantidad': line._qty,
        });
      }

      final additionalInfo = <String, dynamic>{
        'dirPartida': input.startAddress,
        'razonSocialTransportista': input.carrierName,
        'tipoIdTransportista': input.carrierIdType,
        'rucTransportista': input.carrierId,
        'fechaIniTransporte': ddmmyyyy(input.startDate),
        'fechaFinTransporte': ddmmyyyy(input.endDate),
        'placa': input.plate,
        'destinatarios': [
          {
            'identificacionDestinatario': input.recipient.identification,
            'razonSocialDestinatario': input.recipient.name,
            'dirDestinatario': input.recipient.address,
            'motivoTraslado': input.recipient.reason,
            if (input.recipient.route != null &&
                input.recipient.route!.trim().isNotEmpty)
              'ruta': input.recipient.route!.trim(),
            'detalles': detalles,
          },
        ],
      };

      final payload = <String, dynamic>{
        'company_id': input.companyId,
        'customer_id': input.customerId,
        'emission_point_id': input.emissionPointId,
        'document_type': '06',
        'issue_date': dateOnly(DateTime.now()),
        'subtotal_no_tax': 0,
        'subtotal_0': 0,
        'total_tax': 0,
        'total_discount': 0,
        'discount': 0,
        'tip': 0,
        'total': 0,
        'additional_info': additionalInfo,
        'items': items,
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

  /// URL temporal (30 min) del RIDE (PDF) del documento. Para borradores/en
  /// proceso devuelve una vista previa con marca de agua; para finales, el
  /// definitivo. Se abre en el navegador para ver/compartir/guardar.
  Future<DocumentFileLink> documentRide(int documentId) async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '${ApiConstants.documents}/$documentId/ride',
      );
      final data = _payloadMapFromResponse(response);
      return DocumentFileLink.fromJson(data, fallbackName: 'documento.pdf');
    });
  }

  /// Descarga el RIDE (PDF) como bytes, servido por el dominio público de la
  /// app (no por el storage interno). Sirve para borradores y autorizados.
  Future<List<int>> documentRideBytes(int documentId) async {
    return _downloadBytes('${ApiConstants.documents}/$documentId/ride-file');
  }

  /// Descarga el XML firmado como bytes.
  Future<List<int>> documentXmlBytes(int documentId) async {
    return _downloadBytes('${ApiConstants.documents}/$documentId/xml-file');
  }

  Future<List<int>> _downloadBytes(String path) async {
    return _guard(() async {
      final response = await _apiClient.get<List<int>>(
        path,
        options: Options(responseType: ResponseType.bytes),
      );
      return response.data ?? const <int>[];
    });
  }

  /// URL temporal del XML firmado. Solo existe cuando el documento ya fue
  /// firmado/enviado (el backend responde 400 si no está disponible).
  Future<DocumentFileLink> documentXml(int documentId) async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '${ApiConstants.documents}/$documentId/xml',
      );
      final data = _payloadMapFromResponse(response);
      return DocumentFileLink.fromJson(data, fallbackName: 'documento.xml');
    });
  }

  /// Reenvía el comprobante autorizado por correo. Si [email] es null usa el
  /// del cliente registrado en el documento.
  Future<String> resendDocumentEmail(int documentId, {String? email}) async {
    return _guard(() async {
      final response = await _apiClient.post<Map<String, dynamic>>(
        '${ApiConstants.documents}/$documentId/resend-email',
        data: {if (email != null && email.isNotEmpty) 'email': email},
      );
      final body = _bodyFromResponse(response);
      return stringFrom(body['message'], fallback: 'Documento enviado.');
    });
  }

  /// Marca un documento autorizado como anulado (control interno; en Ecuador la
  /// anulación real se hace con una Nota de Crédito).
  Future<ApiDocument> voidDocument(int documentId, String reason) async {
    return _guard(() async {
      final response = await _apiClient.post<Map<String, dynamic>>(
        '${ApiConstants.documents}/$documentId/void',
        data: {'reason': reason},
      );
      final data = _payloadMapFromResponse(response);
      return ApiDocument.fromJson(mapFrom(data['document']));
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

  /// Fetch the current user's subscription + pending transfer payment.
  Future<SubscriptionOverview> currentSubscription() async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiConstants.subscriptionCurrent,
      );
      final data = _payloadMapFromResponse(response);
      return SubscriptionOverview(
        subscription: data['subscription'] == null
            ? null
            : ApiSubscription.fromJson(mapFrom(data['subscription'])),
        pendingPayment: data['pending_payment'] == null
            ? null
            : ApiPendingPayment.fromJson(mapFrom(data['pending_payment'])),
      );
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
          // Si el SRI no envía el campo, asumimos abierto para no desactivar
          // por error.
          isOpen: m.containsKey('is_open') ? b(m['is_open']) : true,
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

  /// Estado actual de la firma electrónica de la empresa.
  Future<SignatureStatus> signatureStatus() async {
    return _guard(() async {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '/signature-status',
      );
      final data = _payloadMapFromResponse(response);
      return SignatureStatus(
        status: stringFrom(data['status'], fallback: 'unknown'),
        message: nullableStringFrom(data['message']),
        daysRemaining: data['days_remaining'] == null
            ? null
            : intFrom(data['days_remaining']),
        expiresAt: dateFrom(data['expires_at']),
        subject: nullableStringFrom(data['subject']),
      );
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

    // Mensajes amigables (sin la jerga técnica de Dio) según el tipo de error.
    final friendly = switch (error.type) {
      DioExceptionType.connectionTimeout ||
      DioExceptionType.receiveTimeout ||
      DioExceptionType.sendTimeout =>
        'El servidor está tardando en responder. Reintentá en unos segundos.',
      DioExceptionType.connectionError =>
        'Sin conexión con el servidor. Revisá tu internet e intentá de nuevo.',
      _ => 'No pudimos conectar con el servidor. Intentá de nuevo.',
    };
    return ApiException(friendly, statusCode: status);
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
