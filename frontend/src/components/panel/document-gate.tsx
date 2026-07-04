"use client";

import Link from "next/link";
import { AlertTriangle } from "lucide-react";
import { useCurrentSubscription } from "@/lib/api/queries/subscription";
import { useEmissionReadiness } from "@/lib/api/queries/readiness";

type Reason = { key: string; label: string; href: string };

// La clave del SRI (portal) no se usa para emitir, por eso no aparece aquí:
// solo bloquean los requisitos que el backend exige al firmar/enviar.
const CHECKLIST_META: Record<string, { label: string; href: string }> = {
  basic_data: { label: "Completa los datos de tu empresa", href: "/settings/company" },
  digital_signature: { label: "Sube tu firma electrónica", href: "/settings/firma" },
  establishments: {
    label: "Configura establecimientos y puntos de emisión",
    href: "/settings/establishments",
  },
};

const ACTIVE_STATUSES = new Set(["active", "trialing"]);

/**
 * Precondiciones para crear/enviar documentos, verificadas en el cliente para
 * mostrar el motivo con link en vez de dejar que el backend lo rechace recién
 * al guardar/enviar (403 "Necesitas una suscripción activa...",
 * "La empresa no tiene firma electrónica...", etc.).
 *
 * blockCreate: también bloquea el borrador (el backend rechaza el POST base).
 * blockSend: además bloquea el envío al SRI (firma/clave/establecimientos).
 */
export function useDocumentGate() {
  const subscriptionQ = useCurrentSubscription();
  const readinessQ = useEmissionReadiness();

  const subscriptionActive =
    subscriptionQ.data?.subscription != null &&
    ACTIVE_STATUSES.has(subscriptionQ.data.subscription.status);

  const subscriptionReason: Reason | null =
    !subscriptionQ.isLoading && !subscriptionActive
      ? {
          key: "subscription",
          label: "Necesitas una suscripción activa para crear documentos",
          href: "/settings/subscription",
        }
      : null;

  const checklist = readinessQ.data?.checklist;
  const checklistReasons: Reason[] = checklist
    ? Object.keys(CHECKLIST_META)
        .filter((k) => !checklist[k as keyof typeof checklist])
        .map((k) => ({ key: k, ...CHECKLIST_META[k] }))
    : [];

  const rucInactive = readinessQ.data?.ruc_active === false;

  const reasons: Reason[] = [
    ...(subscriptionReason ? [subscriptionReason] : []),
    ...checklistReasons,
    ...(rucInactive
      ? [
          {
            key: "ruc",
            label: "El SRI reporta tu RUC como inactivo — regulariza tu estado",
            href: "/settings/company",
          },
        ]
      : []),
  ];

  return {
    blockCreate: !!subscriptionReason,
    blockSend: !!subscriptionReason || checklistReasons.length > 0 || rucInactive,
    reasons,
  };
}

export function DocumentGateBanner({ reasons }: { reasons: Reason[] }) {
  if (reasons.length === 0) return null;

  return (
    <div className="border-b border-warning/30 bg-warning/10 px-4 py-2 lg:px-6">
      <div className="mx-auto flex max-w-5xl flex-wrap items-center gap-x-4 gap-y-1.5 text-xs">
        {reasons.map((r) => (
          <Link
            key={r.key}
            href={r.href}
            className="flex items-center gap-1.5 font-medium text-foreground hover:text-primary hover:underline"
          >
            <AlertTriangle className="size-3.5 shrink-0 text-warning" />
            {r.label}
          </Link>
        ))}
      </div>
    </div>
  );
}
