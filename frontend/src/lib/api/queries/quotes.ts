import {
  useMutation,
  useQuery,
  useQueryClient,
  keepPreviousData,
} from "@tanstack/react-query";
import { api, type ApiPaginated, type ApiSuccess } from "@/lib/api/client";
import type { Document, Quote } from "@/lib/api/types";

export const quoteKeys = {
  all: ["quotes"] as const,
  list: (q: Record<string, unknown>) => [...quoteKeys.all, "list", q] as const,
  detail: (id: number) => [...quoteKeys.all, "detail", id] as const,
};

export type QuoteItemInput = {
  product_id: number | null;
  description: string;
  quantity: number;
  unit_price: number;
  discount: number;
  tax_rate: number;
};

/** Los totales los calcula el servidor: solo se envían los ítems. */
export type QuoteInput = {
  company_id: number;
  customer_id: number;
  issue_date: string;
  expiry_date?: string | null;
  notes?: string | null;
  payment_terms?: string | null;
  items: QuoteItemInput[];
};

export type ConvertQuoteInput = {
  emission_point_id: number;
  issue_date?: string;
  send: boolean;
  payment_method?: string;
  payment_term?: number;
};

export type ConvertQuoteResult = {
  quote: Quote;
  document: Document;
  sent: boolean;
  send_error: string | null;
};

export function useQuotes(
  query: { page?: number; per_page?: number; search?: string; status?: string } = {},
) {
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
    mutationFn: (input: QuoteInput) =>
      api.post<ApiSuccess<{ quote: Quote }>>("quotes", input),
    onSuccess: () => qc.invalidateQueries({ queryKey: quoteKeys.all }),
  });
}

export function useUpdateQuote(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: Partial<QuoteInput>) =>
      api.put<ApiSuccess<{ quote: Quote }>>(`quotes/${id}`, input),
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
    mutationFn: (input: { id: number; action: "accept" | "reject" }) =>
      api.post<ApiSuccess<{ quote: Quote }>>(`quotes/${input.id}/${input.action}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: quoteKeys.all }),
  });
}

/** Envía la cotización por correo (PDF adjunto) y la marca como enviada. */
export function useSendQuote(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: { email?: string; message?: string }) =>
      api.post<ApiSuccess<{ quote: Quote }>>(`quotes/${id}/send`, input),
    onSuccess: () => qc.invalidateQueries({ queryKey: quoteKeys.all }),
  });
}

/** Convierte la cotización en factura electrónica (y opcionalmente la envía al SRI). */
export function useConvertQuote(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: ConvertQuoteInput) =>
      api.post<ApiSuccess<ConvertQuoteResult>>(`quotes/${id}/convert`, input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: quoteKeys.all });
      qc.invalidateQueries({ queryKey: ["documents"] });
      qc.invalidateQueries({ queryKey: ["dashboard"] });
    },
  });
}

/** Abre/descarga el PDF de la cotización (URL temporal de 30 min). */
export async function downloadQuotePdf(id: number) {
  const res = await api.get<ApiSuccess<{ url: string; filename: string }>>(
    `quotes/${id}/pdf`,
  );
  if (typeof window === "undefined") return;
  const a = window.document.createElement("a");
  a.href = res.data.url;
  a.download = res.data.filename;
  a.target = "_blank";
  a.rel = "noopener";
  window.document.body.appendChild(a);
  a.click();
  a.remove();
}
