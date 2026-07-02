import {
  useMutation,
  useQuery,
  useQueryClient,
  keepPreviousData,
} from "@tanstack/react-query";
import { api, type ApiPaginated, type ApiSuccess } from "@/lib/api/client";
import type { RecurringInvoice } from "@/lib/api/types";

export const recurringKeys = {
  all: ["recurring-invoices"] as const,
  list: (q: Record<string, unknown>) =>
    [...recurringKeys.all, "list", q] as const,
};

export function useRecurringInvoices(
  query: { status?: string; page?: number; per_page?: number } = {},
) {
  return useQuery({
    queryKey: recurringKeys.list(query),
    queryFn: () =>
      api.get<ApiPaginated<RecurringInvoice>>("recurring-invoices", { query }),
    placeholderData: keepPreviousData,
  });
}

export function useCreateRecurringInvoice() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: Record<string, unknown>) =>
      api.post<ApiSuccess<{ recurring_invoice: RecurringInvoice }>>(
        "recurring-invoices",
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
      api.post<ApiSuccess<unknown>>(
        `recurring-invoices/${input.id}/${input.action}`,
      ),
    onSuccess: () => qc.invalidateQueries({ queryKey: recurringKeys.all }),
  });
}
