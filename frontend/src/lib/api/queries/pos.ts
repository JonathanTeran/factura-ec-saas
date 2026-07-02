import {
  useMutation,
  useQuery,
  useQueryClient,
  keepPreviousData,
} from "@tanstack/react-query";
import { api, type ApiPaginated, type ApiSuccess } from "@/lib/api/client";
import type { PosSession, PosTransaction } from "@/lib/api/types";

export const posKeys = {
  all: ["pos"] as const,
  active: () => [...posKeys.all, "active"] as const,
  sessions: (q: Record<string, unknown>) =>
    [...posKeys.all, "sessions", q] as const,
  sessionTransactions: (sessionId: number) =>
    [...posKeys.all, "session", sessionId, "transactions"] as const,
};

export function useActivePosSession() {
  return useQuery({
    queryKey: posKeys.active(),
    queryFn: () =>
      api.get<ApiSuccess<{ session: PosSession | null }>>(
        "pos/active-session",
      ),
    select: (raw) => raw.data.session,
  });
}

export function useOpenPosSession() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: {
      company_id: number;
      branch_id: number;
      emission_point_id: number;
      opening_amount?: number;
    }) =>
      api.post<ApiSuccess<{ session: PosSession }>>(
        "pos/open-session",
        input,
      ),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: posKeys.all });
    },
  });
}

export function useClosePosSession(sessionId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: { closing_amount: number; closing_notes?: string }) =>
      api.post<ApiSuccess<{ session: PosSession }>>(
        `pos/sessions/${sessionId}/close`,
        input,
      ),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: posKeys.all });
    },
  });
}

export function usePosSessions(
  query: { page?: number; per_page?: number } = {},
) {
  return useQuery({
    queryKey: posKeys.sessions(query),
    queryFn: () =>
      api.get<ApiPaginated<PosSession> | ApiSuccess<{ sessions: PosSession[] }>>(
        "pos/sessions",
        { query },
      ),
    placeholderData: keepPreviousData,
  });
}

export function useSessionTransactions(sessionId: number | null) {
  return useQuery({
    queryKey: sessionId
      ? posKeys.sessionTransactions(sessionId)
      : ["pos", "session", "none", "transactions"],
    queryFn: () =>
      api.get<ApiPaginated<PosTransaction>>(
        `pos/sessions/${sessionId}/transactions`,
      ),
    enabled: !!sessionId,
  });
}

export function useCreatePosTransaction(sessionId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: {
      customer_id?: number | null;
      payment_method: "cash" | "card" | "transfer" | "other";
      amount_received?: number;
      notes?: string;
      items: Array<{
        product_id?: number | null;
        description?: string;
        quantity: number;
        unit_price?: number;
        discount?: number;
        tax_rate?: number;
      }>;
    }) =>
      api.post<ApiSuccess<{ transaction: PosTransaction }>>(
        `pos/sessions/${sessionId}/transactions`,
        input,
      ),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: posKeys.all });
    },
  });
}

export function useVoidPosTransaction() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (transactionId: number) =>
      api.post<ApiSuccess<unknown>>(
        `pos/transactions/${transactionId}/void`,
      ),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: posKeys.all });
    },
  });
}
