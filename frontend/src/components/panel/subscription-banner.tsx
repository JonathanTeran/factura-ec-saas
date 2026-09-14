"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { Clock, CreditCard, Info } from "lucide-react";
import {
  ACTIVE_SUBSCRIPTION_STATUSES,
  normalizeCurrentSubscription,
  useCurrentSubscription,
  type CurrentSubscriptionRaw,
} from "@/lib/api/queries/subscription";
import { formatDate } from "@/lib/format";

/**
 * Franja permanente (no se puede descartar) mientras la cuenta no tenga una
 * suscripción activa: sin plan no se puede emitir al SRI. También informa
 * cuando hay un pago en revisión o la suscripción está cancelada y por
 * terminar. Se oculta en la propia página de suscripción.
 */
export function SubscriptionBanner({ initial }: { initial: CurrentSubscriptionRaw | null }) {
  const pathname = usePathname();
  const q = useCurrentSubscription();
  const data = q.data ?? (initial ? normalizeCurrentSubscription(initial) : null);

  if (!data || pathname?.startsWith("/settings/subscription")) return null;

  const sub = data.subscription;
  const active = sub != null && ACTIVE_SUBSCRIPTION_STATUSES.has(sub.status);

  if (active && sub?.status !== "cancelled") return null;

  let icon = CreditCard;
  let wrap = "border-primary/30 bg-primary/5 text-primary";
  let title: string;
  let body: string;
  let cta: string;

  if (data.pendingPayment) {
    icon = Clock;
    wrap = "border-warning/40 bg-warning/5 text-warning";
    title = "Pago en revisión";
    body =
      data.pendingPayment.payment_method === "paypal"
        ? `PayPal está revisando tu pago del ${formatDate(data.pendingPayment.created_at)}. Te avisamos por correo cuando tu plan quede activo.`
        : `Recibimos tu comprobante el ${formatDate(data.pendingPayment.created_at)}. Te avisamos por correo cuando tu plan quede activo (normalmente en menos de 24 horas).`;
    cta = "Ver estado";
  } else if (active && sub?.status === "cancelled") {
    icon = Info;
    wrap = "border-border bg-muted/60 text-foreground";
    title = "Tu suscripción está cancelada";
    body = sub?.ends_at
      ? `Conservas el acceso hasta el ${formatDate(sub.ends_at)}. Reanúdala para no interrumpir tu facturación.`
      : "Reanúdala para no interrumpir tu facturación.";
    cta = "Reanudar";
  } else {
    title = "Tu cuenta no tiene un plan activo";
    body = "Sin suscripción no puedes emitir comprobantes al SRI. Elige un plan y completa el pago para activarla.";
    cta = data.intendedPlan ? `Contratar ${data.intendedPlan.name}` : "Elegir plan";
  }

  const Icon = icon;

  return (
    <div className="px-4 pt-4 lg:px-6" role="status" aria-live="polite">
      <div className={`flex items-center gap-3 rounded-xl border px-4 py-3 ${wrap}`}>
        <Icon className="size-5 shrink-0" />
        <div className="min-w-0 flex-1 text-sm">
          <span className="font-medium">{title}.</span>{" "}
          <span className="text-foreground/70">{body}</span>
        </div>
        <Link
          href="/settings/subscription"
          className="shrink-0 rounded-lg border border-current/30 px-3 py-1.5 text-xs font-medium transition hover:bg-current/10"
        >
          {cta}
        </Link>
      </div>
    </div>
  );
}
