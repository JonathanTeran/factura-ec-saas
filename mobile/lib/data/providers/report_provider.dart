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

  // El resto en PARALELO: en serie la pantalla tardaba la suma de las 5
  // latencias en mostrar algo.
  final results = await Future.wait([
    api.documentsByStatus(from: from, to: to),
    api.chartData(days: 30),
    api.chartByType(days: 30),
    api.topCustomers(from: from, to: to, limit: 5),
    api.topProducts(from: from, to: to, limit: 5),
  ]);

  return ReportsViewData(
    dashboard: dashboard,
    byStatus: results[0] as Map<String, int>,
    daily: _zeroFillDaily(results[1] as List<ApiChartPoint>, days: 30, to: to),
    byType: results[2] as List<ApiTypeSummary>,
    topCustomers: results[3] as List<ApiTopCustomer>,
    topProducts: results[4] as List<ApiTopProduct>,
  );
});

/// La API solo devuelve los días CON documentos; con 2 días de datos los
/// gráficos salían rotos (2 barras sueltas, línea diagonal). Se rellena la
/// serie continua de [days] días terminando hoy, con 0 en los días vacíos.
List<ApiChartPoint> _zeroFillDaily(
  List<ApiChartPoint> sparse, {
  required int days,
  required DateTime to,
}) {
  final byDay = <String, ApiChartPoint>{
    for (final p in sparse)
      if (p.date != null)
        '${p.date!.year}-${p.date!.month}-${p.date!.day}': p,
  };

  final end = DateTime(to.year, to.month, to.day);
  return List<ApiChartPoint>.generate(days, (i) {
    final day = end.subtract(Duration(days: days - 1 - i));
    final key = '${day.year}-${day.month}-${day.day}';
    final found = byDay[key];
    return found ??
        ApiChartPoint(date: day, count: 0, total: 0);
  }, growable: false);
}
