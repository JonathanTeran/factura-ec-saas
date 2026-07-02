import {
  useMutation,
  useQuery,
  useQueryClient,
  keepPreviousData,
} from "@tanstack/react-query";
import { api, type ApiPaginated, type ApiSuccess } from "@/lib/api/client";
import type { ReceivedDocument } from "@/lib/api/types";

export const receivedKeys = {
  all: ["received-documents"] as const,
  list: (q: Record<string, unknown>) => [...receivedKeys.all, "list", q] as const,
  detail: (id: number) => [...receivedKeys.all, "detail", id] as const,
};

export function useReceivedDocuments(query: {
  page?: number;
  per_page?: number;
  search?: string;
  expense_category?: string;
  from?: string;
  to?: string;
} = {}) {
  return useQuery({
    queryKey: receivedKeys.list(query),
    queryFn: () =>
      api.get<ApiPaginated<ReceivedDocument>>("received-documents", { query }),
    placeholderData: keepPreviousData,
  });
}

export function useCreateReceivedDocument() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: Record<string, unknown>) =>
      api.post<ApiSuccess<{ received_document: ReceivedDocument }>>(
        "received-documents",
        input,
      ),
    onSuccess: () => qc.invalidateQueries({ queryKey: receivedKeys.all }),
  });
}

export function useDeleteReceivedDocument() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) =>
      api.delete<ApiSuccess<unknown>>(`received-documents/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: receivedKeys.all }),
  });
}
