import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/widgets/app_scaffold.dart';
import '../../core/widgets/error_widget.dart';
import '../../data/models/customer_model.dart';
import '../../data/models/product_model.dart';
import '../../features/auth/login_screen.dart';
import '../../features/auth/forgot_password_screen.dart';
import '../../features/auth/register_screen.dart';
import '../../features/auth/splash_screen.dart';
import '../../features/auth/welcome_screen.dart';
import '../../features/customers/customer_create_screen.dart';
import '../../features/customers/customer_list_screen.dart';
import '../../features/dashboard/dashboard_screen.dart';
import '../../features/onboarding/onboarding_screen.dart';
import '../../features/documents/document_create_screen.dart';
import '../../features/documents/document_detail_screen.dart';
import '../../features/documents/document_list_screen.dart';
import '../../features/pos/pos_screen.dart';
import '../../features/products/product_create_screen.dart';
import '../../features/products/product_list_screen.dart';
import '../../features/purchases/purchases_screen.dart';
import '../../features/purchases/supplier_create_screen.dart';
import '../../features/purchases/suppliers_screen.dart';
import '../../features/reports/reports_screen.dart';
import '../../data/models/company_model.dart';
import '../../features/settings/billing_screen.dart';
import '../../features/settings/certificate_screen.dart';
import '../../features/settings/company_create_screen.dart';
import '../../features/settings/company_edit_screen.dart';
import '../../features/settings/settings_screen.dart';

/// Central GoRouter provider. All screens are imported from feature modules.
/// Providers are in `data/providers/`. Widgets are in `core/widgets/`.
final appRouterProvider = Provider<GoRouter>((ref) {
  return GoRouter(
    initialLocation: '/splash',
    debugLogDiagnostics: false,
    routes: [
      GoRoute(
        path: '/splash',
        name: 'splash',
        builder: (context, state) => const SplashScreen(),
      ),
      GoRoute(
        path: '/welcome',
        name: 'welcome',
        builder: (context, state) => const WelcomeScreen(),
      ),
      GoRoute(
        path: '/login',
        name: 'login',
        builder: (context, state) => const LoginScreen(),
      ),
      GoRoute(
        path: '/register',
        name: 'register',
        builder: (context, state) => const RegisterScreen(),
      ),
      GoRoute(
        path: '/forgot-password',
        name: 'forgot-password',
        builder: (context, state) => const ForgotPasswordScreen(),
      ),
      GoRoute(
        path: '/onboarding',
        name: 'onboarding',
        builder: (context, state) => const OnboardingScreen(),
      ),
      ShellRoute(
        builder: (context, state, child) =>
            AppShell(location: state.uri.path, child: child),
        routes: [
          GoRoute(
            path: '/',
            name: 'dashboard',
            builder: (context, state) => const DashboardScreen(),
          ),
          GoRoute(
            path: '/documents',
            name: 'documents',
            builder: (context, state) => const DocumentsScreen(),
            routes: [
              GoRoute(
                path: 'new',
                name: 'new-document',
                builder: (context, state) => NewDocumentScreen(
                  initialType: state.uri.queryParameters['type'],
                ),
              ),
              GoRoute(
                path: 'edit/:id',
                name: 'edit-document',
                builder: (context, state) {
                  final id = int.tryParse(state.pathParameters['id'] ?? '');
                  return NewDocumentScreen(editDocumentId: id);
                },
              ),
              GoRoute(
                path: ':id',
                name: 'document-detail',
                builder: (context, state) {
                  final id = state.pathParameters['id'] ?? '-';
                  return DocumentDetailScreen(documentId: id);
                },
              ),
            ],
          ),
          GoRoute(
            path: '/customers',
            name: 'customers',
            builder: (context, state) => const CustomersScreen(),
            routes: [
              GoRoute(
                path: 'new',
                name: 'new-customer',
                builder: (context, state) => const CustomerCreateScreen(),
              ),
              GoRoute(
                path: 'edit',
                name: 'edit-customer',
                builder: (context, state) =>
                    CustomerCreateScreen(customer: state.extra as ApiCustomer?),
              ),
            ],
          ),
          GoRoute(
            path: '/products',
            name: 'products',
            builder: (context, state) => const ProductsScreen(),
            routes: [
              GoRoute(
                path: 'new',
                name: 'new-product',
                builder: (context, state) => const ProductCreateScreen(),
              ),
              GoRoute(
                path: 'edit',
                name: 'edit-product',
                builder: (context, state) =>
                    ProductCreateScreen(product: state.extra as ApiProduct?),
              ),
            ],
          ),
          GoRoute(
            path: '/reports',
            name: 'reports',
            builder: (context, state) => const ReportsScreen(),
          ),
          GoRoute(
            path: '/settings',
            name: 'settings',
            builder: (context, state) => const SettingsScreen(),
            routes: [
              GoRoute(
                path: 'billing',
                name: 'billing',
                builder: (context, state) => const BillingScreen(),
              ),
              GoRoute(
                path: 'company/new',
                name: 'company-new',
                builder: (context, state) => const CompanyCreateScreen(),
              ),
              GoRoute(
                path: 'company/edit',
                name: 'company-edit',
                builder: (context, state) =>
                    CompanyEditScreen(company: state.extra as ApiCompany?),
              ),
              GoRoute(
                path: 'certificate',
                name: 'certificate',
                builder: (context, state) => const CertificateScreen(),
              ),
            ],
          ),
          GoRoute(
            path: '/pos',
            name: 'pos',
            builder: (context, state) => const PosScreen(),
          ),
          GoRoute(
            path: '/purchases',
            name: 'purchases',
            builder: (context, state) => const PurchasesScreen(),
          ),
          GoRoute(
            path: '/suppliers',
            name: 'suppliers',
            builder: (context, state) => const SuppliersScreen(),
            routes: [
              GoRoute(
                path: 'new',
                name: 'new-supplier',
                builder: (context, state) => const SupplierCreateScreen(),
              ),
            ],
          ),
        ],
      ),
    ],
    errorBuilder: (context, state) => _ErrorScreen(error: state.error),
  );
});

class _ErrorScreen extends StatelessWidget {
  final Exception? error;

  const _ErrorScreen({this.error});

  @override
  Widget build(BuildContext context) {
    return AppErrorWidget(message: '$error');
  }
}
