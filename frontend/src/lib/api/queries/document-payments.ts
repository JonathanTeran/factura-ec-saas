import {
  useMutation,
  useQuery,
  useQueryClient,
} from "@tanstack/react-query";
import { api, type ApiSuccess } from "@/lib/api/client";

export type DocumentPayment = {
  id: number;
  amount: string;
  payment_method: "cash" | "transfer" | "card" | "other";
  payment_date: string;
  notes: string | null;
  created_at: string;
};

export type DocumentPaymentTotals = {
  id: number;
  total: string;
  paid_amount: string;
  balance: string;
};

export type PaymentInput = {
  amount: number;
  payment_method: "cash" | "transfer" | "card" | "other";
  payment_date?: string;
  notes?: string;
};

export const documentPaymentKeys = {
  list: (documentId: number) =>
    ["documents", documentId, "payments"] as const,
};

export function useDocumentPayments(documentId: number) {
  return useQuery({
    queryKey: documentPaymentKeys.list(documentId),
    queryFn: () =>
      api.get<
        ApiSuccess<{
          payments: DocumentPayment[];
          document: DocumentPaymentTotals;
        }>
      >(`documents/${documentId}/payments`),
    select: (raw) => raw.data,
  });
}

export function useRegisterPayment(documentId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: PaymentInput) =>
      api.post<
        ApiSuccess<{
          payment: DocumentPayment;
          document: DocumentPaymentTotals;
        }>
      >(`documents/${documentId}/payments`, input),
    onSuccess: () => {
      qc.invalidateQueries({
        queryKey: documentPaymentKeys.list(documentId),
      });
    },
  });
}
