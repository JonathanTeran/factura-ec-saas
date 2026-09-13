import {
  useMutation,
  useQuery,
  useQueryClient,
  keepPreviousData,
} from "@tanstack/react-query";
import { api, type ApiPaginated, type ApiSuccess } from "@/lib/api/client";
import type {
  Document,
  RecurringInvoice,
  RecurringInvoiceItem,
  RecurringInvoicePaymentMethod,
} from "@/lib/api/types";

export const recurringKeys = {
  all: ["recurring-invoices"] as const,
  list: (q: Record<string, unknown>) =>
    [...recurringKeys.all, "list", q] as const,
  detail: (id: number) => [...recurringKeys.all, "detail", id] as const,
  documents: (id: number, q: Record<string, unknown>) =>
    [...recurringKeys.all, "detail", id, "documents", q] as const,
};

export const FREQUENCIES: Array<{ value: string; label: string }> = [
  { value: "weekly", label: "Semanal" },
  { value: "biweekly", label: "Quincenal" },
  { value: "monthly", label: "Mensual" },
  { value: "bimonthly", label: "Bimestral" },
  { value: "quarterly", label: "Trimestral" },
  { value: "semiannual", label: "Semestral" },
  { value: "annual", label: "Anual" },
];

export const FREQ_LABELS: Record<string, string> = Object.fromEntries(
  FREQUENCIES.map((f) => [f.value, f.label]),
);

export type RecurringInvoiceInput = {
  name?: string | null;
  company_id: number;
  branch_id: number;
  emission_point_id: number;
  customer_id: number;
  frequency: string;
  start_date: string;
  next_issue_date?: string | null;
  end_date?: string | null;
  max_issues?: number | null;
  items: RecurringInvoiceItem[];
  payment_methods?: RecurringInvoicePaymentMethod[];
  notes?: string | null;
  notify_before_issue?: boolean;
  notify_days_before?: number;
  auto_send?: boolean;
};

export function useRecurringInvoices(
  query: { status?: string; search?: string; page?: number; per_page?: number } = {},
) {
  return useQuery({
    queryKey: recurringKeys.list(query),
    queryFn: () =>
      api.get<ApiPaginated<RecurringInvoice>>("recurring-invoices", { query }),
    placeholderData: keepPreviousData,
  });
}

export function useRecurringInvoice(id: number | null) {
  return useQuery({
    queryKey: id ? recurringKeys.detail(id) : ["recurring-invoices", "detail", "none"],
    queryFn: () =>
      api.get<ApiSuccess<{ recurring_invoice: RecurringInvoice }>>(
        `recurring-invoices/${id}`,
      ),
    enabled: !!id,
    select: (raw) => raw.data.recurring_invoice,
  });
}

export function useRecurringInvoiceDocuments(
  id: number | null,
  query: { page?: number; per_page?: number } = {},
) {
  return useQuery({
    queryKey: id
      ? recurringKeys.documents(id, query)
      : ["recurring-invoices", "documents", "none"],
    queryFn: () =>
      api.get<ApiPaginated<Document>>(`recurring-invoices/${id}/documents`, {
        query,
      }),
    enabled: !!id,
    placeholderData: keepPreviousData,
  });
}

export function useCreateRecurringInvoice() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: RecurringInvoiceInput) =>
      api.post<ApiSuccess<{ recurring_invoice: RecurringInvoice }>>(
        "recurring-invoices",
        input,
      ),
    onSuccess: () => qc.invalidateQueries({ queryKey: recurringKeys.all }),
  });
}

export function useUpdateRecurringInvoice(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: Partial<RecurringInvoiceInput>) =>
      api.put<ApiSuccess<{ recurring_invoice: RecurringInvoice }>>(
        `recurring-invoices/${id}`,
        input,
      ),
    onSuccess: () => qc.invalidateQueries({ queryKey: recurringKeys.all }),
  });
}

export function useDeleteRecurringInvoice() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) =>
      api.delete<ApiSuccess<unknown>>(`recurring-invoices/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: recurringKeys.all }),
  });
}

export function useRecurringInvoiceAction() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: { id: number; action: "pause" | "resume" }) =>
      api.post<ApiSuccess<{ recurring_invoice: RecurringInvoice }>>(
        `recurring-invoices/${input.id}/${input.action}`,
      ),
    onSuccess: () => qc.invalidateQueries({ queryKey: recurringKeys.all }),
  });
}

export type GenerateRecurringResult = {
  document: Document;
  recurring_invoice: RecurringInvoice;
};

/** "Emitir ahora": genera la factura sin esperar al lote diario. */
export function useGenerateRecurringInvoice(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: { send?: boolean } = {}) =>
      api.post<ApiSuccess<GenerateRecurringResult>>(
        `recurring-invoices/${id}/generate`,
        input,
      ),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: recurringKeys.all });
      qc.invalidateQueries({ queryKey: ["documents"] });
      qc.invalidateQueries({ queryKey: ["dashboard"] });
    },
  });
}
