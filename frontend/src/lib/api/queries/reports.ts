import { useQuery } from "@tanstack/react-query";
import { api, type ApiSuccess } from "@/lib/api/client";

export type DateRange = { from: string; to: string };

export type SalesReport = {
  data: Array<{
    period: string;
    count: number;
    total: number;
    tax: number;
    average: number;
  }>;
  totals: { count: number; total: number; tax: number; average: number };
  from: string;
  to: string;
  group_by: string;
};

export type TaxReport = {
  subtotals: Record<string, number>;
  total_tax: number;
  total: number;
};

export type TaxBucket = { count: number; base: number; iva: number };

export type TaxSummary = {
  period: { from: string; to: string };
  ventas: TaxBucket;
  notas_credito: TaxBucket;
  compras: {
    con_ruc: TaxBucket;
    con_cedula: TaxBucket;
    total: TaxBucket;
  };
  iva_ventas_neto: number;
  iva_credito_compras: number;
  iva_a_pagar: number;
};

export type TopRow = {
  id: number;
  name: string;
  identification?: string;
  code?: string;
  count?: number;
  total?: number;
  total_purchases?: number;
  total_amount?: number;
  total_revenue?: number;
};

export const reportKeys = {
  all: ["reports"] as const,
  dashboard: () => [...reportKeys.all, "dashboard"] as const,
  sales: (q: DateRange & { group_by?: string }) =>
    [...reportKeys.all, "sales", q] as const,
  taxes: (q: DateRange) => [...reportKeys.all, "taxes", q] as const,
  taxSummary: (q: { year: number; month: number }) =>
    [...reportKeys.all, "tax-summary", q] as const,
  topCustomers: (q: DateRange & { limit?: number }) =>
    [...reportKeys.all, "top-customers", q] as const,
  topProducts: (q: DateRange & { limit?: number }) =>
    [...reportKeys.all, "top-products", q] as const,
  documentsByStatus: (q: DateRange) =>
    [...reportKeys.all, "docs-by-status", q] as const,
};

export function useReportsDashboard() {
  return useQuery({
    queryKey: reportKeys.dashboard(),
    queryFn: () => api.get<ApiSuccess<unknown>>("reports/dashboard"),
    select: (raw) => raw.data,
  });
}

export function useSalesReport(
  range: DateRange,
  groupBy: "day" | "week" | "month" = "day",
) {
  return useQuery({
    queryKey: reportKeys.sales({ ...range, group_by: groupBy }),
    queryFn: () =>
      api.get<ApiSuccess<SalesReport>>("reports/sales", {
        query: { from: range.from, to: range.to, group_by: groupBy },
      }),
    select: (raw) => raw.data,
    enabled: !!range.from && !!range.to,
  });
}

export function useTaxReport(range: DateRange) {
  return useQuery({
    queryKey: reportKeys.taxes(range),
    queryFn: () =>
      api.get<ApiSuccess<TaxReport>>("reports/taxes", {
        query: { from: range.from, to: range.to },
      }),
    select: (raw) => raw.data,
    enabled: !!range.from && !!range.to,
  });
}

export function useTaxSummary(year: number, month: number) {
  return useQuery({
    queryKey: reportKeys.taxSummary({ year, month }),
    queryFn: () =>
      api.get<ApiSuccess<TaxSummary>>("reports/tax-summary", {
        query: { year, month },
      }),
    select: (raw) => raw.data,
  });
}

/** Descarga el Excel de ventas del período (año/mes) vía el proxy. */
export function downloadSalesExcel(year: number, month: number) {
  if (typeof window === "undefined") return;
  const a = window.document.createElement("a");
  a.href = `/api/proxy/reports/sales/export?year=${year}&month=${month}`;
  a.download = `ventas_${year}-${String(month).padStart(2, "0")}.xlsx`;
  a.target = "_blank";
  window.document.body.appendChild(a);
  a.click();
  a.remove();
}

export function useTopCustomers(range: DateRange, limit = 10) {
  return useQuery({
    queryKey: reportKeys.topCustomers({ ...range, limit }),
    queryFn: () =>
      api.get<ApiSuccess<{ customers: TopRow[] }>>("reports/top-customers", {
        query: { from: range.from, to: range.to, limit },
      }),
    select: (raw) => raw.data.customers,
    enabled: !!range.from && !!range.to,
  });
}

export function useTopProducts(range: DateRange, limit = 10) {
  return useQuery({
    queryKey: reportKeys.topProducts({ ...range, limit }),
    queryFn: () =>
      api.get<ApiSuccess<{ products: TopRow[] }>>("reports/top-products", {
        query: { from: range.from, to: range.to, limit },
      }),
    select: (raw) => raw.data.products,
    enabled: !!range.from && !!range.to,
  });
}

export function useDocumentsByStatus(range: DateRange) {
  return useQuery({
    queryKey: reportKeys.documentsByStatus(range),
    // El endpoint devuelve { data: { statuses: { authorized: 38, ... } } }.
    queryFn: () =>
      api.get<ApiSuccess<{ statuses: Record<string, number> }>>(
        "reports/documents-by-status",
        { query: { from: range.from, to: range.to } },
      ),
    select: (raw) => raw.data?.statuses ?? {},
    enabled: !!range.from && !!range.to,
  });
}
