import {
  useMutation,
  useQuery,
  useQueryClient,
  keepPreviousData,
} from "@tanstack/react-query";
import { api, type ApiPaginated, type ApiSuccess } from "@/lib/api/client";
import type { Product } from "@/lib/api/types";

export type ProductsQuery = {
  page?: number;
  per_page?: number;
  search?: string;
  category_id?: number;
};

export type ProductInput = {
  code: string;
  sku?: string;
  name: string;
  description?: string;
  type: "product" | "service";
  category_id?: number | null;
  unit_price: number;
  cost?: number;
  tax_rate?: number;
  tax_percentage_code?: string;
  track_inventory?: boolean;
  stock?: number;
  min_stock?: number;
  is_active?: boolean;
};

export const productKeys = {
  all: ["products"] as const,
  list: (q: ProductsQuery) => [...productKeys.all, "list", q] as const,
  detail: (id: number) => [...productKeys.all, "detail", id] as const,
};

export function useProducts(query: ProductsQuery = {}) {
  return useQuery({
    queryKey: productKeys.list(query),
    queryFn: () => api.get<ApiPaginated<Product>>("products", { query }),
    placeholderData: keepPreviousData,
  });
}

export function useProduct(id: number | null) {
  return useQuery({
    queryKey: id ? productKeys.detail(id) : ["products", "detail", "none"],
    queryFn: () =>
      api.get<ApiSuccess<{ product: Product } | Product>>(`products/${id}`),
    enabled: !!id,
    select: (raw) => {
      const data = raw.data as Product | { product: Product };
      return "product" in data ? data.product : data;
    },
  });
}

export function useCreateProduct() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: ProductInput) =>
      api.post<ApiSuccess<{ product: Product } | Product>>("products", input),
    onSuccess: () => qc.invalidateQueries({ queryKey: productKeys.all }),
  });
}

export function useUpdateProduct(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: ProductInput) =>
      api.put<ApiSuccess<{ product: Product } | Product>>(
        `products/${id}`,
        input,
      ),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: productKeys.all });
      qc.invalidateQueries({ queryKey: productKeys.detail(id) });
    },
  });
}

export function useDeleteProduct() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) =>
      api.delete<ApiSuccess<unknown>>(`products/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: productKeys.all }),
  });
}
