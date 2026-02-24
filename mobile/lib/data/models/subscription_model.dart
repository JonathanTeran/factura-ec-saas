import 'json_helpers.dart';

/// Represents a subscription plan available for purchase.
class ApiPlan {
  final int id;
  final String name;
  final String slug;
  final String? description;
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
    required this.priceMonthly,
    required this.priceYearly,
    required this.maxDocumentsPerMonth,
    required this.maxUsers,
    required this.maxCompanies,
    required this.isFeatured,
    required this.features,
  });

  factory ApiPlan.fromJson(Map<String, dynamic> json) {
    return ApiPlan(
      id: intFrom(json['id']),
      name: stringFrom(json['name'], fallback: 'Plan'),
      slug: stringFrom(json['slug']),
      description: nullableStringFrom(json['description']),
      priceMonthly: doubleFrom(json['price_monthly']),
      priceYearly: doubleFrom(json['price_yearly']),
      maxDocumentsPerMonth: intFrom(json['max_documents_per_month']),
      maxUsers: intFrom(json['max_users']),
      maxCompanies: intFrom(json['max_companies']),
      isFeatured: json['is_featured'] == true,
      features: listFrom(json['features'])
          .map((e) => e.toString())
          .toList(growable: false),
    );
  }
}

/// Represents the user's active subscription.
class ApiSubscription {
  final int id;
  final String status;
  final String billingCycle;
  final double amount;
  final String currency;
  final DateTime? startsAt;
  final DateTime? endsAt;
  final DateTime? trialEndsAt;
  final ApiPlan? plan;

  const ApiSubscription({
    required this.id,
    required this.status,
    required this.billingCycle,
    required this.amount,
    required this.currency,
    this.startsAt,
    this.endsAt,
    this.trialEndsAt,
    this.plan,
  });

  factory ApiSubscription.fromJson(Map<String, dynamic> json) {
    final planData = nullableMapFrom(json['plan']);
    return ApiSubscription(
      id: intFrom(json['id']),
      status: stringFrom(json['status'], fallback: 'active'),
      billingCycle: stringFrom(json['billing_cycle'], fallback: 'monthly'),
      amount: doubleFrom(json['amount']),
      currency: stringFrom(json['currency'], fallback: 'USD'),
      startsAt: dateFrom(json['starts_at']),
      endsAt: dateFrom(json['ends_at']),
      trialEndsAt: dateFrom(json['trial_ends_at']),
      plan: planData != null ? ApiPlan.fromJson(planData) : null,
    );
  }
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
      paymentMethod: stringFrom(json['payment_method'], fallback: 'bank_transfer'),
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
      holderIdentification: stringFrom(json['holder_identification'], fallback: '-'),
      instructions: nullableStringFrom(json['instructions']),
    );
  }
}
