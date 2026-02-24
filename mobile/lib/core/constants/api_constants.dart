class ApiConstants {
  static const bool enableBackend =
      bool.fromEnvironment('ENABLE_BACKEND', defaultValue: true);
  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://127.0.0.1:8000',
  );
  static const String apiVersion = 'v1';
  static const String apiPath = '/api/$apiVersion';

  // Endpoints
  static const String auth = '/auth';
  static const String login = '$auth/login';
  static const String register = '$auth/register';
  static const String logout = '$auth/logout';
  static const String refreshToken = '$auth/refresh';
  static const String forgotPassword = '$auth/forgot-password';

  static const String dashboard = '/dashboard';
  static const String documents = '/documents';
  static const String invoices = '/invoices';
  static const String creditNotes = '/credit-notes';
  static const String retentions = '/retentions';

  static const String customers = '/customers';
  static const String products = '/products';
  static const String categories = '/categories';

  static const String companies = '/companies';
  static const String branches = '/branches';
  static const String emissionPoints = '/emission-points';

  static const String reports = '/reports';
  static const String settings = '/settings';

  // Suppliers & Purchases
  static const String suppliers = '/suppliers';
  static const String purchases = '/purchases';

  // POS
  static const String pos = '/pos';
  static const String posActiveSession = '$pos/active-session';
  static const String posOpenSession = '$pos/open-session';
  static const String posSessions = '$pos/sessions';

  // Subscriptions & Billing
  static const String subscription = '/subscription';
  static const String subscriptionPlans = '$subscription/plans';
  static const String subscriptionCurrent = '$subscription/current';
  static const String subscriptionBankTransfer = '$subscription/bank-transfer';
  static const String subscriptionCancel = '$subscription/cancel';
  static const String subscriptionResume = '$subscription/resume';
  static const String subscriptionPayments = '$subscription/payments';
  static const String subscriptionBankAccounts = '$subscription/bank-accounts';
  static const String subscriptionValidateCoupon = '$subscription/validate-coupon';

  // AI
  static const String ai = '/ai';

  // Headers
  static const String contentType = 'Content-Type';
  static const String authorization = 'Authorization';
  static const String accept = 'Accept';
  static const String applicationJson = 'application/json';
  static const String bearer = 'Bearer';

  // Timeouts
  static const Duration connectTimeout = Duration(seconds: 30);
  static const Duration receiveTimeout = Duration(seconds: 30);
}
