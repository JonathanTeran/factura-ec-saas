import {
  useMutation,
  useQuery,
  useQueryClient,
  keepPreviousData,
} from "@tanstack/react-query";
import { api, type ApiPaginated, type ApiSuccess } from "@/lib/api/client";
import type { Customer } from "@/lib/api/types";

export type CustomersQuery = {
  page?: number;
  per_page?: number;
  search?: string;
};

export type CustomerInput = {
  identification_type: string;
  identification_number: string;
  name: string;
  email?: string;
  additional_emails?: string[];
  phone?: string;
  address?: string;
  is_active?: boolean;
};

export const customerKeys = {
  all: ["customers"] as const,
  list: (q: CustomersQuery) => [...customerKeys.all, "list", q] as const,
  detail: (id: number) => [...customerKeys.all, "detail", id] as const,
};

export function useCustomers(query: CustomersQuery = {}) {
  return useQuery({
    queryKey: customerKeys.list(query),
    queryFn: () => api.get<ApiPaginated<Customer>>("customers", { query }),
    placeholderData: keepPreviousData,
  });
}

export function useCustomer(id: number | null) {
  return useQuery({
    queryKey: id ? customerKeys.detail(id) : ["customers", "detail", "none"],
    queryFn: () =>
      api.get<ApiSuccess<{ customer: Customer } | Customer>>(`customers/${id}`),
    enabled: !!id,
    select: (raw) => {
      const data = raw.data as Customer | { customer: Customer };
      return "customer" in data ? data.customer : data;
    },
  });
}

export function useCreateCustomer() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: CustomerInput) =>
      api.post<ApiSuccess<{ customer: Customer } | Customer>>("customers", input),
    onSuccess: () => qc.invalidateQueries({ queryKey: customerKeys.all }),
  });
}

export function useUpdateCustomer(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: CustomerInput) =>
      api.put<ApiSuccess<{ customer: Customer } | Customer>>(
        `customers/${id}`,
        input,
      ),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: customerKeys.all });
      qc.invalidateQueries({ queryKey: customerKeys.detail(id) });
    },
  });
}

export function useDeleteCustomer() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) =>
      api.delete<ApiSuccess<unknown>>(`customers/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: customerKeys.all }),
  });
}
