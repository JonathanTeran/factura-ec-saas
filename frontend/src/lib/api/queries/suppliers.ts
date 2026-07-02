import {
  useMutation,
  useQuery,
  useQueryClient,
  keepPreviousData,
} from "@tanstack/react-query";
import { api, type ApiPaginated, type ApiSuccess } from "@/lib/api/client";
import type { Supplier } from "@/lib/api/types";

export type SuppliersQuery = {
  page?: number;
  per_page?: number;
  search?: string;
};

export type SupplierInput = {
  identification_type: string;
  identification: string;
  business_name: string;
  commercial_name?: string;
  email?: string;
  phone?: string;
  address?: string;
  city?: string;
  is_withholding_agent?: boolean;
  accounting_account?: string;
  notes?: string;
};

export const supplierKeys = {
  all: ["suppliers"] as const,
  list: (q: SuppliersQuery) => [...supplierKeys.all, "list", q] as const,
  detail: (id: number) => [...supplierKeys.all, "detail", id] as const,
};

export function useSuppliers(query: SuppliersQuery = {}) {
  return useQuery({
    queryKey: supplierKeys.list(query),
    queryFn: () => api.get<ApiPaginated<Supplier>>("suppliers", { query }),
    placeholderData: keepPreviousData,
  });
}

export function useSupplier(id: number | null) {
  return useQuery({
    queryKey: id ? supplierKeys.detail(id) : ["suppliers", "detail", "none"],
    queryFn: () =>
      api.get<ApiSuccess<{ supplier: Supplier } | Supplier>>(
        `suppliers/${id}`,
      ),
    enabled: !!id,
    select: (raw) => {
      const data = raw.data as Supplier | { supplier: Supplier };
      return "supplier" in data ? data.supplier : data;
    },
  });
}

export function useCreateSupplier() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: SupplierInput) =>
      api.post<ApiSuccess<unknown>>("suppliers", input),
    onSuccess: () => qc.invalidateQueries({ queryKey: supplierKeys.all }),
  });
}

export function useUpdateSupplier(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: SupplierInput) =>
      api.put<ApiSuccess<unknown>>(`suppliers/${id}`, input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: supplierKeys.all });
      qc.invalidateQueries({ queryKey: supplierKeys.detail(id) });
    },
  });
}

export function useDeleteSupplier() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) =>
      api.delete<ApiSuccess<unknown>>(`suppliers/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: supplierKeys.all }),
  });
}
