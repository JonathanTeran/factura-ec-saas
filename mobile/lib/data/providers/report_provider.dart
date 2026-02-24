import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/v1_api_service.dart';
import '../models/dashboard_model.dart';
import 'auth_provider.dart';

final reportsViewDataProvider = FutureProvider<ReportsViewData>((ref) async {
  final api = ref.read(v1ApiServiceProvider);
  final to = DateTime.now();
  final from = to.subtract(const Duration(days: 30));
  final dashboardFuture = api.reportsDashboard();
  final byStatusFuture = api.documentsByStatus(from: from, to: to);

  final dashboard = await dashboardFuture;
  final byStatus = await byStatusFuture;

  return ReportsViewData(dashboard: dashboard, byStatus: byStatus);
});
