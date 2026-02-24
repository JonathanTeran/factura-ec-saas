import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/v1_api_service.dart';
import '../models/dashboard_model.dart';
import 'auth_provider.dart';

final dashboardViewDataProvider = FutureProvider<DashboardViewData>((
  ref,
) async {
  final api = ref.read(v1ApiServiceProvider);
  final statsFuture = api.dashboardStats();
  final recentFuture = api.recentDocuments();
  final chartFuture = api.chartData(days: 30);
  final typeFuture = api.chartByType(days: 30);

  final stats = await statsFuture;
  final recent = await recentFuture;
  final chart = await chartFuture;
  final typeSummary = await typeFuture;

  return DashboardViewData(
    stats: stats,
    recentDocuments: recent,
    chartPoints: chart,
    typeSummary: typeSummary,
  );
});
