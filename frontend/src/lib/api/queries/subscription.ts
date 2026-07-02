import {
  useMutation,
  useQuery,
  useQueryClient,
} from "@tanstack/react-query";
import { api, type ApiSuccess } from "@/lib/api/client";
import type { Plan, Subscription } from "@/lib/api/types";

export type BankAccount = {
  id: number;
  bank_name: string;
  account_number: string;
  account_type?: string;
  holder_name?: string;
  holder_identification?: string;
};

export type Payment = {
  id: number;
  amount: number;
  status: string;
  payment_method?: string;
  paid_at?: string | null;
  receipt_url?: string | null;
  created_at: string;
};

// La API devuelve los planes con otra forma (price_monthly/price_yearly,
// features como objeto de flags). Los normalizamos a la forma `Plan` que
// consume la vista de suscripción (price, interval, features: string[]).
type RawPlan = {
  id: number;
  name: string;
  price_monthly?: number | string | null;
  price_yearly?: number | string | null;
  price?: number | string | null;
  currency?: string | null;
  interval?: string | null;
  features?: Record<string, boolean> | string[] | null;
};

const FEATURE_LABELS: Record<string, string> = {
  has_electronic_signature: "Firma electrónica",
  has_api_access: "Acceso API",
  has_inventory: "Inventario",
  has_pos: "Punto de venta (POS)",
  has_recurring_invoices: "Facturas recurrentes",
  has_proformas: "Proformas",
  has_ats: "Anexo ATS",
  has_thermal_printer: "Impresora térmica",
  has_advanced_reports: "Reportes avanzados",
  has_whitelabel_ride: "RIDE personalizado",
  has_webhooks: "Webhooks",
  has_client_portal: "Portal de clientes",
};

function normalizePlanFeatures(
  features: Record<string, boolean> | string[] | null | undefined,
): string[] {
  if (!features) return [];
  if (Array.isArray(features)) return features;
  return Object.entries(features)
    .filter(([, enabled]) => enabled)
    .map(([key]) => FEATURE_LABELS[key] ?? key);
}

function normalizePlan(raw: RawPlan): Plan {
  const price =
    raw.price ?? raw.price_monthly ?? raw.price_yearly ?? 0;
  return {
    id: raw.id,
    name: raw.name,
    price: typeof price === "string" ? Number(price) : (price ?? 0),
    currency: raw.currency ?? "USD",
    interval:
      raw.interval ?? (raw.price_monthly != null ? "mes" : "año"),
    features: normalizePlanFeatures(raw.features),
  };
}

export const subscriptionKeys = {
  all: ["subscription"] as const,
  plans: () => [...subscriptionKeys.all, "plans"] as const,
  current: () => [...subscriptionKeys.all, "current"] as const,
  payments: () => [...subscriptionKeys.all, "payments"] as const,
  usage: () => [...subscriptionKeys.all, "usage"] as const,
  banks: () => [...subscriptionKeys.all, "bank-accounts"] as const,
};

export function usePlans() {
  return useQuery({
    queryKey: subscriptionKeys.plans(),
    queryFn: () =>
      api.get<ApiSuccess<{ plans: RawPlan[] }>>("subscription/plans"),
    select: (raw) => (raw.data.plans ?? []).map(normalizePlan),
  });
}

export function useCurrentSubscription() {
  return useQuery({
    queryKey: subscriptionKeys.current(),
    queryFn: () =>
      api.get<
        ApiSuccess<{ subscription: Subscription | null; plan: RawPlan | null }>
      >("subscription/current"),
    select: (raw) => ({
      subscription: raw.data.subscription ?? null,
      plan: raw.data.plan ? normalizePlan(raw.data.plan) : null,
    }),
  });
}

export function usePayments() {
  return useQuery({
    queryKey: subscriptionKeys.payments(),
    queryFn: () =>
      api.get<ApiSuccess<{ payments: Payment[] } | Payment[]>>(
        "subscription/payments",
      ),
    select: (raw) => {
      const d = raw.data as Payment[] | { payments: Payment[] };
      return Array.isArray(d) ? d : d.payments;
    },
  });
}

export function useUsage() {
  return useQuery({
    queryKey: subscriptionKeys.usage(),
    queryFn: () =>
      api.get<ApiSuccess<unknown>>("subscription/usage"),
    select: (raw) => raw.data,
  });
}

export function useBankAccounts() {
  return useQuery({
    queryKey: subscriptionKeys.banks(),
    queryFn: () =>
      api.get<ApiSuccess<{ bank_accounts: BankAccount[] } | BankAccount[]>>(
        "subscription/bank-accounts",
      ),
    select: (raw) => {
      const d = raw.data as BankAccount[] | { bank_accounts: BankAccount[] };
      return Array.isArray(d) ? d : d.bank_accounts;
    },
  });
}

export function useCancelSubscription() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: () =>
      api.post<ApiSuccess<unknown>>("subscription/cancel"),
    onSuccess: () => qc.invalidateQueries({ queryKey: subscriptionKeys.all }),
  });
}

export function useResumeSubscription() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: () =>
      api.post<ApiSuccess<unknown>>("subscription/resume"),
    onSuccess: () => qc.invalidateQueries({ queryKey: subscriptionKeys.all }),
  });
}

export function useChangePlan() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (planId: number) =>
      api.post<ApiSuccess<unknown>>("subscription/change-plan", {
        plan_id: planId,
      }),
    onSuccess: () => qc.invalidateQueries({ queryKey: subscriptionKeys.all }),
  });
}
