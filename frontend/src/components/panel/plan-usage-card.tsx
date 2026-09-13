"use client";

import Link from "next/link";
import { Clock, CreditCard, Sparkles } from "lucide-react";
import {
  ACTIVE_SUBSCRIPTION_STATUSES,
  useCurrentSubscription,
  useUsage,
} from "@/lib/api/queries/subscription";
import { formatDate, formatMoney } from "@/lib/format";
import { cn } from "@/lib/utils";

/**
 * Tarjeta del plan al pie del menú lateral: nombre y precio del plan
 * contratado, barra de consumo de comprobantes del mes y fecha de
 * renovación. Sin plan activo, invita a contratar uno.
 */
export function PlanUsageCard({ onNavigate }: { onNavigate?: () => void }) {
  const currentQ = useCurrentSubscription();
  const usageQ = useUsage();

  if (currentQ.isLoading) {
    return (
      <div className="mx-3 mb-3 h-[92px] animate-pulse rounded-xl border border-sidebar-border bg-sidebar-accent/40" />
    );
  }

  const data = currentQ.data;
  const sub = data?.subscription ?? null;
  const active = sub != null && ACTIVE_SUBSCRIPTION_STATUSES.has(sub.status);

  if (!data || !active) {
    const pending = data?.pendingPayment;
    return (
      <div className="mx-3 mb-3 rounded-xl border border-primary/30 bg-primary/5 p-3">
        <div className="flex items-center gap-2 text-sm font-medium text-foreground">
          {pending ? <Clock className="size-4 text-warning" /> : <CreditCard className="size-4 text-primary" />}
          {pending ? "Pago en revisión" : "Sin plan activo"}
        </div>
        <p className="mt-1 text-xs text-muted-foreground">
          {pending
            ? "Activaremos tu plan al verificar la transferencia."
            : "Elige un plan para emitir comprobantes al SRI."}
        </p>
        <Link
          href="/settings/subscription"
          onClick={onNavigate}
          className="mt-2 flex h-8 items-center justify-center rounded-lg bg-primary text-xs font-medium text-primary-foreground transition hover:brightness-105"
        >
          {pending ? "Ver estado" : data?.intendedPlan ? `Contratar ${data.intendedPlan.name}` : "Elegir plan"}
        </Link>
      </div>
    );
  }

  const plan = data.plan;
  const yearly = sub.billing_cycle === "yearly";
  const price = plan ? (yearly ? plan.priceYearly : plan.priceMonthly) : null;
  const docs = usageQ.data?.documents;
  const unlimited = docs ? docs.limit === -1 : false;
  const used = docs?.used ?? 0;
  const limit = docs?.limit ?? 0;
  const pct = unlimited || limit <= 0 ? 0 : Math.min(100, Math.round((used / limit) * 100));
  const barColor = pct >= 100 ? "bg-destructive" : pct >= 80 ? "bg-warning" : "bg-primary";
  const cancelled = sub.status === "cancelled";

  return (
    <Link
      href="/settings/subscription"
      onClick={onNavigate}
      className="mx-3 mb-3 block rounded-xl border border-sidebar-border bg-card p-3 transition hover:border-primary/40 hover:shadow-sm"
    >
      <div className="flex items-start justify-between gap-2">
        <div className="min-w-0">
          <p className="flex items-center gap-1.5 text-sm font-semibold text-foreground">
            <Sparkles className="size-3.5 text-primary" />
            <span className="truncate">Plan {plan?.name ?? "activo"}</span>
          </p>
          {price != null && (
            <p className="text-xs text-muted-foreground">
              {formatMoney(price)} / {yearly ? "año" : "mes"}
            </p>
          )}
        </div>
        {cancelled && (
          <span className="rounded-full bg-muted px-2 py-0.5 text-[10px] font-medium text-muted-foreground">
            Cancelado
          </span>
        )}
      </div>

      <div className="mt-2.5">
        <div className="flex items-center justify-between text-[11px] text-muted-foreground">
          <span>Comprobantes este mes</span>
          <span className="tabular-nums text-foreground">
            {used}
            {unlimited ? " · ilimitados" : ` / ${limit}`}
          </span>
        </div>
        <div className="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-muted" role="progressbar" aria-valuemin={0} aria-valuemax={100} aria-valuenow={pct}>
          <div className={cn("h-full rounded-full transition-all", barColor)} style={{ width: `${unlimited ? 100 : pct}%` }} />
        </div>
      </div>

      {sub.ends_at && (
        <p className="mt-2 text-[11px] text-muted-foreground">
          {cancelled ? "Acceso hasta el " : "Renueva el "}
          <span className="text-foreground">{formatDate(sub.ends_at)}</span>
        </p>
      )}
    </Link>
  );
}
