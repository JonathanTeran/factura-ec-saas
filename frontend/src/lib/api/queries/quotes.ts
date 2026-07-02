import {
  useMutation,
  useQuery,
  useQueryClient,
  keepPreviousData,
} from "@tanstack/react-query";
import { api, type ApiPaginated, type ApiSuccess } from "@/lib/api/client";
import type { Quote } from "@/lib/api/types";

export const quoteKeys = {
  all: ["quotes"] as const,
  list: (q: Record<string, unknown>) => [...quoteKeys.all, "list", q] as const,
  detail: (id: number) => [...quoteKeys.all, "detail", id] as const,
};

export function useQuotes(query: { page?: number; per_page?: number; search?: string; status?: string } = {}) {
  return useQuery({
    queryKey: quoteKeys.list(query),
    queryFn: () => api.get<ApiPaginated<Quote>>("quotes", { query }),
    placeholderData: keepPreviousData,
  });
}

export function useQuote(id: number | null) {
  return useQuery({
    queryKey: id ? quoteKeys.detail(id) : ["quotes", "detail", "none"],
    queryFn: () => api.get<ApiSuccess<{ quote: Quote }>>(`quotes/${id}`),
    enabled: !!id,
    select: (raw) => raw.data.quote,
  });
}

export function useCreateQuote() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: Record<string, unknown>) =>
      api.post<ApiSuccess<{ quote: Quote }>>("quotes", input),
    onSuccess: () => qc.invalidateQueries({ queryKey: quoteKeys.all }),
  });
}

export function useDeleteQuote() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => api.delete<ApiSuccess<unknown>>(`quotes/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: quoteKeys.all }),
  });
}

export function useQuoteAction() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: { id: number; action: "send" | "accept" | "reject" }) =>
      api.post<ApiSuccess<unknown>>(`quotes/${input.id}/${input.action}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: quoteKeys.all }),
  });
}
