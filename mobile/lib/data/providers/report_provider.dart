import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/v1_api_service.dart';
import '../models/dashboard_model.dart';
import 'auth_provider.dart';

final reportsViewDataProvider = FutureProvider<ReportsViewData>((ref) async {
  final api = ref.read(v1ApiServiceProvider);
  final to = DateTime.now();
  final from = to.subtract(const Duration(days: 30));
  // El dashboard de reportes está detrás del plan (403 si no aplica); se pide
  // primero para que ese candado gobierne toda la vista.
  final dashboard = await api.reportsDashboard();
  final byStatus = await api.documentsByStatus(from: from, to: to);
  final daily = await api.chartData(days: 30);
  final byType = await api.chartByType(days: 30);

  return ReportsViewData(
    dashboard: dashboard,
    byStatus: byStatus,
    daily: daily,
    byType: byType,
  );
});
