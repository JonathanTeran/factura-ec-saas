import '../../core/api/v1_api_service.dart';
import '../models/subscription_model.dart';

/// Repository that wraps subscription and billing API calls.
class SubscriptionRepository {
  final V1ApiService _api;

  const SubscriptionRepository(this._api);

  /// Fetch all available subscription plans.
  Future<List<ApiPlan>> plans() => _api.plans();

  /// Fetch the current user's active subscription, if any.
  Future<ApiSubscription?> currentSubscription() {
    return _api.currentSubscription();
  }

  /// Subscribe via bank transfer with a receipt file upload.
  Future<ApiSubscription> subscribeBankTransfer({
    required int planId,
    required String billingCycle,
    required String receiptFilePath,
    required String transferReference,
    required String billingName,
    required String billingEmail,
  }) {
    return _api.subscribeBankTransfer(
      planId: planId,
      billingCycle: billingCycle,
      receiptFilePath: receiptFilePath,
      transferReference: transferReference,
      billingName: billingName,
      billingEmail: billingEmail,
    );
  }

  /// Cancel the current subscription.
  Future<void> cancel() => _api.cancelSubscription();

  /// Resume a cancelled subscription before it expires.
  Future<void> resume() => _api.resumeSubscription();

  /// Fetch the payment history.
  Future<List<ApiPayment>> payments() => _api.payments();

  /// Fetch the status of a specific payment.
  Future<ApiPayment> paymentStatus(int paymentId) {
    return _api.paymentStatus(paymentId);
  }

  /// Fetch the list of bank accounts where transfers can be sent.
  Future<List<ApiBankAccount>> bankAccounts() => _api.bankAccounts();

  /// Validate a coupon/discount code.
  Future<Map<String, dynamic>> validateCoupon(String code) {
    return _api.validateCoupon(code);
  }
}
