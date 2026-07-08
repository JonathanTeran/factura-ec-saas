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
  instructions?: string | null;
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
  has_client_portal: "Portal de clientes",
  has_multi_currency: "Multi-moneda",
  has_ai_categorization: "Categorización con IA",
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

function toNumber(v: number | string | null | undefined): number {
  if (v == null) return 0;
  return typeof v === "string" ? Number(v) : v;
}

function normalizePlan(raw: RawPlan): Plan {
  const price =
    raw.price ?? raw.price_monthly ?? raw.price_yearly ?? 0;
  return {
    id: raw.id,
    name: raw.name,
    price: typeof price === "string" ? Number(price) : (price ?? 0),
    priceMonthly: toNumber(raw.price_monthly),
    priceYearly: toNumber(raw.price_yearly),
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
        ApiSuccess<{
          subscription: (Subscription & { plan?: RawPlan | null }) | null;
          plan: RawPlan | null;
          pending_payment?: (Payment & { status_label?: string }) | null;
        }>
      >("subscription/current"),
    select: (raw) => {
      // Con suscripción activa, el backend anida el plan dentro de
      // `subscription.plan`; sin suscripción, lo manda como `plan` suelto
      // (vista previa del plan elegido en onboarding, sin pago aún).
      const rawPlan = raw.data.subscription?.plan ?? raw.data.plan;
      return {
        subscription: raw.data.subscription ?? null,
        plan: rawPlan ? normalizePlan(rawPlan) : null,
        // Transferencia esperando verificación del admin: la vista muestra
        // "pendiente" y bloquea el envío de otro comprobante.
        pendingPayment: raw.data.pending_payment ?? null,
      };
    },
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
    mutationFn: (input: { planId: number; billingCycle: "monthly" | "yearly" }) =>
      api.post<ApiSuccess<unknown>>("subscription/change-plan", {
        plan_id: input.planId,
        billing_cycle: input.billingCycle,
      }),
    onSuccess: () => qc.invalidateQueries({ queryKey: subscriptionKeys.all }),
  });
}

export type SubscribeBankTransferInput = {
  planId: number;
  billingCycle: "monthly" | "yearly";
  bankAccountId: number;
  transferReceipt: File;
  transferReference: string;
  billingName: string;
  billingEmail: string;
  billingIdentification?: string;
  couponCode?: string;
};

/**
 * Único camino real para que un tenant sin suscripción activa obtenga la
 * primera: sube el comprobante de transferencia y queda con un pago
 * PENDING hasta que el super admin lo apruebe (no hay pasarela de pago).
 * Usa fetch crudo (no el cliente `api`) porque el body es FormData.
 */
export function useSubscribeBankTransfer() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (input: SubscribeBankTransferInput) => {
      const fd = new FormData();
      fd.append("plan_id", String(input.planId));
      fd.append("billing_cycle", input.billingCycle);
      fd.append("transfer_receipt", input.transferReceipt);
      fd.append("transfer_reference", input.transferReference);
      fd.append("billing_name", input.billingName);
      fd.append("billing_email", input.billingEmail);
      if (input.billingIdentification) {
        fd.append("billing_identification", input.billingIdentification);
      }
      if (input.couponCode) fd.append("coupon_code", input.couponCode);

      const res = await fetch("/api/proxy/subscription/subscribe-bank-transfer", {
        method: "POST",
        body: fd,
        headers: { Accept: "application/json" },
      });
      const payload = await res.json().catch(() => null);
      if (!res.ok) {
        throw new Error(
          payload?.message ?? "No se pudo registrar la suscripción.",
        );
      }
      return payload as ApiSuccess<unknown>;
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: subscriptionKeys.all }),
  });
}
