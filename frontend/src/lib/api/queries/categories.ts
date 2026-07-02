import {
  useMutation,
  useQuery,
  useQueryClient,
  keepPreviousData,
} from "@tanstack/react-query";
import { api, type ApiPaginated, type ApiSuccess } from "@/lib/api/client";
import type { Category } from "@/lib/api/types";

export type CategoriesQuery = {
  page?: number;
  per_page?: number;
  search?: string;
  parent_id?: number | "null";
};

export type CategoryInput = {
  name: string;
  parent_id?: number | null;
  description?: string;
  color?: string;
  icon?: string;
  sort_order?: number;
  is_active?: boolean;
};

export const categoryKeys = {
  all: ["categories"] as const,
  list: (q: CategoriesQuery) => [...categoryKeys.all, "list", q] as const,
  detail: (id: number) => [...categoryKeys.all, "detail", id] as const,
};

export function useCategories(query: CategoriesQuery = {}) {
  return useQuery({
    queryKey: categoryKeys.list(query),
    // El endpoint devuelve { data: { categories: [...] } } (lista plana, sin paginar).
    queryFn: () =>
      api.get<ApiSuccess<{ categories: Category[] }>>("categories", { query }),
    placeholderData: keepPreviousData,
    select: (raw) => ({
      data: raw.data?.categories ?? [],
      meta: undefined as ApiPaginated<Category>["meta"] | undefined,
    }),
  });
}

export function useCategory(id: number | null) {
  return useQuery({
    queryKey: id ? categoryKeys.detail(id) : ["categories", "detail", "none"],
    queryFn: () =>
      api.get<ApiSuccess<{ category: Category } | Category>>(
        `categories/${id}`,
      ),
    enabled: !!id,
    select: (raw) => {
      const data = raw.data as Category | { category: Category };
      return "category" in data ? data.category : data;
    },
  });
}

export function useCreateCategory() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: CategoryInput) =>
      api.post<ApiSuccess<unknown>>("categories", input),
    onSuccess: () => qc.invalidateQueries({ queryKey: categoryKeys.all }),
  });
}

export function useUpdateCategory(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: CategoryInput) =>
      api.put<ApiSuccess<unknown>>(`categories/${id}`, input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: categoryKeys.all });
      qc.invalidateQueries({ queryKey: categoryKeys.detail(id) });
    },
  });
}

export function useDeleteCategory() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => api.delete<ApiSuccess<unknown>>(`categories/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: categoryKeys.all }),
  });
}
