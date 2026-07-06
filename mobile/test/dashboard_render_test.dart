import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:factura_ec_app/core/theme/app_theme.dart';
import 'package:factura_ec_app/data/models/dashboard_model.dart';
import 'package:factura_ec_app/data/models/user_model.dart';
import 'package:factura_ec_app/data/providers/auth_provider.dart';
import 'package:factura_ec_app/data/providers/dashboard_provider.dart';
import 'package:factura_ec_app/features/dashboard/dashboard_screen.dart';

void main() {
  testWidgets('dashboard renders with data without throwing', (tester) async {
    final data = DashboardViewData(
      stats: const ApiDashboardStats(
        currentMonthCount: 5,
        currentMonthTotal: 1234.5,
        lastMonthCount: 3,
        lastMonthTotal: 900,
        authorizedCount: 4,
        pendingCount: 1,
        rejectedCount: 0,
        documentsUsed: 5,
        documentsLimit: 100,
        planPercentage: 5,
      ),
      recentDocuments: const [],
      chartPoints: [
        for (var i = 0; i < 10; i++)
          ApiChartPoint(
            date: DateTime(2026, 7, i + 1),
            count: i,
            total: (i * 10).toDouble(),
          ),
      ],
      typeSummary: const [],
    );

    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          dashboardViewDataProvider.overrideWith((ref) async => data),
          meProvider.overrideWith(
            (ref) async =>
                const ApiUser(id: 1, name: 'Jonathan Teran', email: 'j@x.com'),
          ),
        ],
        child: MaterialApp(
          theme: AppTheme.lightTheme,
          home: const Scaffold(body: DashboardScreen()),
        ),
      ),
    );

    await tester.pump();
    await tester.pump(const Duration(seconds: 1));

    expect(tester.takeException(), isNull);
    expect(find.textContaining('Hola'), findsOneWidget);
  });
}
