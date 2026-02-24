import 'json_helpers.dart';

/// Represents a POS (Point of Sale) session.
class ApiPosSession {
  final int id;
  final int companyId;
  final int branchId;
  final int emissionPointId;
  final String status;
  final double openingAmount;
  final double? closingAmount;
  final int totalTransactions;
  final double totalCash;
  final double totalCard;
  final double totalTransfer;
  final double totalSales;
  final DateTime? openedAt;
  final DateTime? closedAt;

  const ApiPosSession({
    required this.id,
    required this.companyId,
    required this.branchId,
    required this.emissionPointId,
    required this.status,
    required this.openingAmount,
    this.closingAmount,
    required this.totalTransactions,
    required this.totalCash,
    required this.totalCard,
    required this.totalTransfer,
    required this.totalSales,
    this.openedAt,
    this.closedAt,
  });

  bool get isOpen => status == 'open';

  factory ApiPosSession.fromJson(Map<String, dynamic> json) {
    return ApiPosSession(
      id: intFrom(json['id']),
      companyId: intFrom(json['company_id']),
      branchId: intFrom(json['branch_id']),
      emissionPointId: intFrom(json['emission_point_id']),
      status: stringFrom(json['status'], fallback: 'open'),
      openingAmount: doubleFrom(json['opening_amount']),
      closingAmount: json['closing_amount'] != null
          ? doubleFrom(json['closing_amount'])
          : null,
      totalTransactions: intFrom(json['total_transactions']),
      totalCash: doubleFrom(json['total_cash']),
      totalCard: doubleFrom(json['total_card']),
      totalTransfer: doubleFrom(json['total_transfer']),
      totalSales: doubleFrom(json['total_sales']),
      openedAt: dateFrom(json['opened_at']),
      closedAt: dateFrom(json['closed_at']),
    );
  }
}

/// Represents a single POS transaction within a session.
class ApiPosTransaction {
  final int id;
  final String transactionNumber;
  final String paymentMethod;
  final double subtotal;
  final double tax;
  final double discount;
  final double total;
  final double amountReceived;
  final double changeAmount;
  final String status;
  final DateTime? createdAt;

  const ApiPosTransaction({
    required this.id,
    required this.transactionNumber,
    required this.paymentMethod,
    required this.subtotal,
    required this.tax,
    required this.discount,
    required this.total,
    required this.amountReceived,
    required this.changeAmount,
    required this.status,
    this.createdAt,
  });

  factory ApiPosTransaction.fromJson(Map<String, dynamic> json) {
    return ApiPosTransaction(
      id: intFrom(json['id']),
      transactionNumber: stringFrom(json['transaction_number']),
      paymentMethod: stringFrom(json['payment_method']),
      subtotal: doubleFrom(json['subtotal']),
      tax: doubleFrom(json['tax']),
      discount: doubleFrom(json['discount']),
      total: doubleFrom(json['total']),
      amountReceived: doubleFrom(json['amount_received']),
      changeAmount: doubleFrom(json['change_amount']),
      status: stringFrom(json['status']),
      createdAt: dateFrom(json['created_at']),
    );
  }
}
