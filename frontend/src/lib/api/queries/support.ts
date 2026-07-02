import {
  useMutation,
  useQuery,
  useQueryClient,
  keepPreviousData,
} from "@tanstack/react-query";
import { api, type ApiPaginated, type ApiSuccess } from "@/lib/api/client";
import type { SupportTicket } from "@/lib/api/types";

export const supportKeys = {
  all: ["support"] as const,
  list: (q: Record<string, unknown>) => [...supportKeys.all, "list", q] as const,
  detail: (id: number) => [...supportKeys.all, "detail", id] as const,
};

export function useSupportTickets(
  query: {
    status?: string;
    priority?: string;
    page?: number;
    per_page?: number;
  } = {},
) {
  return useQuery({
    queryKey: supportKeys.list(query),
    queryFn: () =>
      api.get<ApiPaginated<SupportTicket>>("support/tickets", { query }),
    placeholderData: keepPreviousData,
  });
}

export function useSupportTicket(id: number | null) {
  return useQuery({
    queryKey: id ? supportKeys.detail(id) : ["support", "detail", "none"],
    queryFn: () =>
      api.get<ApiSuccess<{ ticket: SupportTicket }>>(`support/tickets/${id}`),
    enabled: !!id,
    select: (raw) => raw.data.ticket,
  });
}

export function useCreateTicket() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: {
      subject: string;
      category: string;
      priority: string;
      message: string;
    }) => api.post<ApiSuccess<{ ticket: SupportTicket }>>("support/tickets", input),
    onSuccess: () => qc.invalidateQueries({ queryKey: supportKeys.all }),
  });
}

export function useReplyTicket(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (message: string) =>
      api.post<ApiSuccess<unknown>>(`support/tickets/${id}/reply`, { message }),
    onSuccess: () => qc.invalidateQueries({ queryKey: supportKeys.all }),
  });
}

export function useTicketAction() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: { id: number; action: "close" | "reopen" }) =>
      api.post<ApiSuccess<unknown>>(
        `support/tickets/${input.id}/${input.action}`,
      ),
    onSuccess: () => qc.invalidateQueries({ queryKey: supportKeys.all }),
  });
}
