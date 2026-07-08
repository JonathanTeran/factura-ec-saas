import 'json_helpers.dart';

/// Represents a subscription plan available for purchase.
class ApiPlan {
  final int id;
  final String name;
  final String slug;
  final String? description;
  final String currency;
  final int trialDays;
  final double priceMonthly;
  final double priceYearly;
  final int maxDocumentsPerMonth;
  final int maxUsers;
  final int maxCompanies;
  final bool isFeatured;
  final List<String> features;

  const ApiPlan({
    required this.id,
    required this.name,
    required this.slug,
    this.description,
    required this.currency,
    required this.trialDays,
    required this.priceMonthly,
    required this.priceYearly,
    required this.maxDocumentsPerMonth,
    required this.maxUsers,
    required this.maxCompanies,
    required this.isFeatured,
    required this.features,
  });

  factory ApiPlan.fromJson(Map<String, dynamic> json) {
    final limits = nullableMapFrom(json['limits']) ?? const <String, dynamic>{};
    final features =
        nullableMapFrom(json['features']) ?? const <String, dynamic>{};

    return ApiPlan(
      id: intFrom(json['id']),
      name: stringFrom(json['name'], fallback: 'Plan'),
      slug: stringFrom(json['slug']),
      description: nullableStringFrom(json['description']),
      currency: stringFrom(json['currency'], fallback: 'USD'),
      trialDays: intFrom(json['trial_days']),
      priceMonthly: doubleFrom(json['price_monthly'] ?? json['monthly_price']),
      priceYearly: doubleFrom(json['price_yearly'] ?? json['yearly_price']),
      maxDocumentsPerMonth: intFrom(
          limits['max_documents_per_month'] ?? json['max_documents_per_month']),
      maxUsers: intFrom(limits['max_users'] ?? json['max_users']),
      maxCompanies: intFrom(limits['max_companies'] ?? json['max_companies']),
      isFeatured: json['is_featured'] == true || json['is_popular'] == true,
      features: _featureListFromJson(features, json['features']),
    );
  }

  double get monthlyPrice => priceMonthly;
  double get yearlyPrice => priceYearly;
  int get maxDocuments => maxDocumentsPerMonth;
  bool get isPopular => isFeatured;
}

/// Represents the user's active subscription.
class ApiSubscription {
  final int id;
  final String status;
  final String statusLabel;
  final String billingCycle;
  final double amount;
  final double price;
  final double discountAmount;
  final double finalPrice;
  final String currency;
  final DateTime? startsAt;
  final DateTime? endsAt;
  final DateTime? trialEndsAt;
  final DateTime? nextPaymentAt;
  final bool onTrial;
  final bool isActive;
  final bool isCanceled;
  final bool isPastDue;
  final ApiPlan? plan;

  const ApiSubscription({
    required this.id,
    required this.status,
    required this.statusLabel,
    required this.billingCycle,
    required this.amount,
    required this.price,
    required this.discountAmount,
    required this.finalPrice,
    required this.currency,
    this.startsAt,
    this.endsAt,
    this.trialEndsAt,
    this.nextPaymentAt,
    required this.onTrial,
    required this.isActive,
    required this.isCanceled,
    required this.isPastDue,
    this.plan,
  });

  factory ApiSubscription.fromJson(Map<String, dynamic> json) {
    final planData = nullableMapFrom(json['plan']);
    final price = doubleFrom(json['price']);
    final finalPrice =
        doubleFrom(json['final_price'] ?? json['amount'] ?? price);

    return ApiSubscription(
      id: intFrom(json['id']),
      status: stringFrom(json['status'], fallback: 'active'),
      statusLabel: stringFrom(json['status_label'], fallback: 'Activa'),
      billingCycle: stringFrom(json['billing_cycle'], fallback: 'monthly'),
      amount: finalPrice,
      price: price,
      discountAmount: doubleFrom(json['discount_amount']),
      finalPrice: finalPrice,
      currency: stringFrom(json['currency'], fallback: 'USD'),
      startsAt: dateFrom(json['starts_at']),
      endsAt: dateFrom(json['ends_at']),
      trialEndsAt: dateFrom(json['trial_ends_at']),
      nextPaymentAt: dateFrom(json['next_payment_at']),
      onTrial: json['on_trial'] == true,
      isActive: json['is_active'] == true,
      isCanceled: json['is_canceled'] == true,
      isPastDue: json['is_past_due'] == true,
      plan: planData != null ? ApiPlan.fromJson(planData) : null,
    );
  }

  String get planName => plan?.name ?? 'Plan actual';
  DateTime? get currentPeriodEnd => endsAt ?? nextPaymentAt;
}

/// Represents a payment record (bank transfer, etc.).
class ApiPayment {
  final int id;
  final String status;
  final String paymentMethod;
  final double amount;
  final double taxAmount;
  final double totalAmount;
  final String? transferReference;
  final String? transferReceiptUrl;
  final DateTime? createdAt;
  final DateTime? paidAt;

  const ApiPayment({
    required this.id,
    required this.status,
    required this.paymentMethod,
    required this.amount,
    required this.taxAmount,
    required this.totalAmount,
    this.transferReference,
    this.transferReceiptUrl,
    this.createdAt,
    this.paidAt,
  });

  factory ApiPayment.fromJson(Map<String, dynamic> json) {
    return ApiPayment(
      id: intFrom(json['id']),
      status: stringFrom(json['status'], fallback: 'pending'),
      paymentMethod:
          stringFrom(json['payment_method'], fallback: 'bank_transfer'),
      amount: doubleFrom(json['amount']),
      taxAmount: doubleFrom(json['tax_amount']),
      totalAmount: doubleFrom(json['total_amount']),
      transferReference: nullableStringFrom(json['transfer_reference']),
      transferReceiptUrl: nullableStringFrom(json['transfer_receipt_url']),
      createdAt: dateFrom(json['created_at']),
      paidAt: dateFrom(json['paid_at']),
    );
  }
}

/// Represents a bank account where users can send transfer payments.
class ApiBankAccount {
  final int id;
  final String bankName;
  final String accountType;
  final String accountNumber;
  final String holderName;
  final String holderIdentification;
  final String? instructions;

  const ApiBankAccount({
    required this.id,
    required this.bankName,
    required this.accountType,
    required this.accountNumber,
    required this.holderName,
    required this.holderIdentification,
    this.instructions,
  });

  factory ApiBankAccount.fromJson(Map<String, dynamic> json) {
    return ApiBankAccount(
      id: intFrom(json['id']),
      bankName: stringFrom(json['bank_name'], fallback: 'Banco'),
      accountType: stringFrom(json['account_type'], fallback: 'savings'),
      accountNumber: stringFrom(json['account_number'], fallback: '-'),
      holderName: stringFrom(json['holder_name'], fallback: '-'),
      holderIdentification:
          stringFrom(json['holder_identification'], fallback: '-'),
      instructions: nullableStringFrom(json['instructions']),
    );
  }

  String get accountHolder => holderName;
  String get identificationNumber => holderIdentification;
}

List<String> _featureListFromJson(
  Map<String, dynamic> featuresMap,
  dynamic rawFeatures,
) {
  if (featuresMap.isNotEmpty) {
    const labels = <String, String>{
      'has_electronic_signature': 'Firma electrónica',
      'has_api_access': 'Acceso API',
      'has_inventory': 'Inventario',
      'has_pos': 'Punto de venta',
      'has_recurring_invoices': 'Facturas recurrentes',
      'has_proformas': 'Proformas',
      'has_ats': 'ATS',
      'has_thermal_printer': 'Impresión térmica',
      'has_advanced_reports': 'Reportes avanzados',
      'has_whitelabel_ride': 'RIDE personalizado',
      'has_webhooks': 'Webhooks',
      'has_client_portal': 'Portal de clientes',
    };

    return featuresMap.entries
        .where((entry) => entry.value == true)
        .map((entry) => labels[entry.key] ?? entry.key)
        .toList(growable: false);
  }

  return listFrom(rawFeatures)
      .map((e) => e.toString())
      .where((e) => e.isNotEmpty)
      .toList(growable: false);
}

/// Pago por transferencia pendiente de verificación del admin. Mientras
/// exista, la app muestra "pendiente" y bloquea enviar otro comprobante.
class ApiPendingPayment {
  final int id;
  final String status;
  final String statusLabel;
  final double totalAmount;
  final String? transferReference;
  final DateTime? createdAt;

  const ApiPendingPayment({
    required this.id,
    required this.status,
    required this.statusLabel,
    required this.totalAmount,
    this.transferReference,
    this.createdAt,
  });

  factory ApiPendingPayment.fromJson(Map<String, dynamic> json) {
    return ApiPendingPayment(
      id: intFrom(json['id']),
      status: stringFrom(json['status'], fallback: 'pending'),
      statusLabel: stringFrom(json['status_label'], fallback: 'Pendiente'),
      totalAmount: doubleFrom(json['total_amount'] ?? json['amount']),
      transferReference: nullableStringFrom(json['transfer_reference']),
      createdAt: dateFrom(json['created_at']),
    );
  }
}

/// Respuesta de /subscription/current: suscripción activa (si hay) + pago
/// por transferencia pendiente de verificación (si hay).
class SubscriptionOverview {
  final ApiSubscription? subscription;
  final ApiPendingPayment? pendingPayment;

  const SubscriptionOverview({this.subscription, this.pendingPayment});
}
