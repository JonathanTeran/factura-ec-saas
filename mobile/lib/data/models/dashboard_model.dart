import 'document_model.dart';
import 'json_helpers.dart';

/// Aggregated statistics for the main dashboard.
class ApiDashboardStats {
  final int currentMonthCount;
  final double currentMonthTotal;
  final int lastMonthCount;
  final double lastMonthTotal;
  final int authorizedCount;
  final int pendingCount;
  final int rejectedCount;
  final int documentsUsed;
  final int documentsLimit;
  final double planPercentage;

  const ApiDashboardStats({
    required this.currentMonthCount,
    required this.currentMonthTotal,
    required this.lastMonthCount,
    required this.lastMonthTotal,
    required this.authorizedCount,
    required this.pendingCount,
    required this.rejectedCount,
    required this.documentsUsed,
    required this.documentsLimit,
    required this.planPercentage,
  });

  factory ApiDashboardStats.fromJson(Map<String, dynamic> json) {
    final current = mapFrom(json['current_month']);
    final last = mapFrom(json['last_month']);
    final byStatus = mapFrom(json['by_status']);
    final usage = mapFrom(json['plan_usage']);

    return ApiDashboardStats(
      currentMonthCount: intFrom(current['documents_count']),
      currentMonthTotal: doubleFrom(current['documents_total']),
      lastMonthCount: intFrom(last['documents_count']),
      lastMonthTotal: doubleFrom(last['documents_total']),
      authorizedCount: intFrom(byStatus['authorized']),
      pendingCount: intFrom(byStatus['pending']),
      rejectedCount: intFrom(byStatus['rejected']),
      documentsUsed: intFrom(usage['documents_used']),
      documentsLimit: intFrom(usage['documents_limit']),
      planPercentage: doubleFrom(usage['percentage']),
    );
  }
}

/// A single data point for time-series charts.
class ApiChartPoint {
  final DateTime? date;
  final int count;
  final double total;

  const ApiChartPoint({
    required this.date,
    required this.count,
    required this.total,
  });

  factory ApiChartPoint.fromJson(Map<String, dynamic> json) {
    return ApiChartPoint(
      date: dateFrom(json['date']),
      count: intFrom(json['count']),
      total: doubleFrom(json['total']),
    );
  }
}

/// Summary of documents grouped by type.
class ApiTypeSummary {
  final String type;
  final String label;
  final int count;
  final double total;

  const ApiTypeSummary({
    required this.type,
    required this.label,
    required this.count,
    required this.total,
  });

  factory ApiTypeSummary.fromJson(Map<String, dynamic> json) {
    return ApiTypeSummary(
      type: stringFrom(json['type']),
      label: stringFrom(json['label'], fallback: 'Tipo'),
      count: intFrom(json['count']),
      total: doubleFrom(json['total']),
    );
  }
}

/// Combined data required to render the main dashboard view.
class DashboardViewData {
  final ApiDashboardStats stats;
  final List<ApiDocument> recentDocuments;
  final List<ApiChartPoint> chartPoints;
  final List<ApiTypeSummary> typeSummary;

  const DashboardViewData({
    required this.stats,
    required this.recentDocuments,
    required this.chartPoints,
    required this.typeSummary,
  });
}

/// Combined data required to render the reports view.
class ReportsViewData {
  final ReportsDashboardStats dashboard;
  final Map<String, int> byStatus;

  const ReportsViewData({required this.dashboard, required this.byStatus});
}

/// Aggregated statistics for the reports dashboard.
class ReportsDashboardStats {
  final int documentsThisMonth;
  final int documentsLastMonth;
  final double revenueThisMonth;
  final double revenueLastMonth;
  final int customersTotal;
  final int productsTotal;
  final int lowStockProducts;

  const ReportsDashboardStats({
    required this.documentsThisMonth,
    required this.documentsLastMonth,
    required this.revenueThisMonth,
    required this.revenueLastMonth,
    required this.customersTotal,
    required this.productsTotal,
    required this.lowStockProducts,
  });

  factory ReportsDashboardStats.fromJson(Map<String, dynamic> json) {
    final documents = mapFrom(json['documents']);
    final revenue = mapFrom(json['revenue']);
    final customers = mapFrom(json['customers']);
    final products = mapFrom(json['products']);

    return ReportsDashboardStats(
      documentsThisMonth: intFrom(mapFrom(documents['this_month'])['count']),
      documentsLastMonth: intFrom(mapFrom(documents['last_month'])['count']),
      revenueThisMonth: doubleFrom(
        mapFrom(revenue['this_month'])['invoices'],
      ),
      revenueLastMonth: doubleFrom(
        mapFrom(revenue['last_month'])['invoices'],
      ),
      customersTotal: intFrom(customers['total']),
      productsTotal: intFrom(products['total']),
      lowStockProducts: intFrom(products['low_stock']),
    );
  }
}
