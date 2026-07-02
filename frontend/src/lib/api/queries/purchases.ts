import {
  useMutation,
  useQuery,
  useQueryClient,
  keepPreviousData,
} from "@tanstack/react-query";
import { api, type ApiPaginated, type ApiSuccess } from "@/lib/api/client";
import type { Purchase } from "@/lib/api/types";

export type PurchasesQuery = {
  page?: number;
  per_page?: number;
  search?: string;
  supplier_id?: number;
  status?: string;
  from?: string;
  to?: string;
};

export type PurchaseInput = {
  company_id: number;
  supplier_id: number;
  document_type: string;
  supplier_document_number: string;
  supplier_authorization?: string;
  issue_date: string;
  authorization_date?: string;
  payment_methods?: Array<{ code: string; amount: number }>;
  notes?: string;
  items: Array<{
    product_id?: number | null;
    main_code?: string;
    description: string;
    quantity: number;
    unit_price: number;
    discount?: number;
    tax_rate?: number;
    tax_percentage_code?: string;
  }>;
};

export const purchaseKeys = {
  all: ["purchases"] as const,
  list: (q: PurchasesQuery) => [...purchaseKeys.all, "list", q] as const,
  detail: (id: number) => [...purchaseKeys.all, "detail", id] as const,
};

export function usePurchases(query: PurchasesQuery = {}) {
  return useQuery({
    queryKey: purchaseKeys.list(query),
    queryFn: () => api.get<ApiPaginated<Purchase>>("purchases", { query }),
    placeholderData: keepPreviousData,
  });
}

export function usePurchase(id: number | null) {
  return useQuery({
    queryKey: id ? purchaseKeys.detail(id) : ["purchases", "detail", "none"],
    queryFn: () =>
      api.get<ApiSuccess<{ purchase: Purchase } | Purchase>>(
        `purchases/${id}`,
      ),
    enabled: !!id,
    select: (raw) => {
      const data = raw.data as Purchase | { purchase: Purchase };
      return "purchase" in data ? data.purchase : data;
    },
  });
}

export function useCreatePurchase() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: PurchaseInput) =>
      api.post<ApiSuccess<{ purchase: Purchase }>>("purchases", input),
    onSuccess: () => qc.invalidateQueries({ queryKey: purchaseKeys.all }),
  });
}

export function useDeletePurchase() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) =>
      api.delete<ApiSuccess<unknown>>(`purchases/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: purchaseKeys.all }),
  });
}
