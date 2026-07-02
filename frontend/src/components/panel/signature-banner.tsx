"use client";

import { useState } from "react";
import Link from "next/link";
import { AlertTriangle, ShieldAlert, ShieldX, X } from "lucide-react";
import type { SignatureStatus } from "@/lib/api/queries/onboarding";

const STYLES = {
  expired: {
    icon: ShieldX,
    wrap: "border-destructive/30 bg-destructive/5 text-destructive",
    title: "Tu firma electrónica venció",
    body: "No podrás emitir comprobantes hasta renovarla.",
    cta: "Renovar firma",
  },
  expiring_soon: {
    icon: AlertTriangle,
    wrap: "border-warning/40 bg-warning/5 text-warning",
    title: "Tu firma electrónica está por vencer",
    body: "Renuévala pronto para no interrumpir tu facturación.",
    cta: "Ver firma",
  },
  missing: {
    icon: ShieldAlert,
    wrap: "border-primary/30 bg-primary/5 text-primary",
    title: "Aún no configuras tu firma electrónica",
    body: "La necesitas para emitir comprobantes al SRI.",
    cta: "Configurar firma",
  },
} as const;

export function SignatureBanner({ data }: { data: SignatureStatus }) {
  const [dismissed, setDismissed] = useState(false);

  const key =
    data.status === "expired" ||
    data.status === "expiring_soon" ||
    data.status === "missing"
      ? data.status
      : null;

  if (!key || dismissed) return null;

  const s = STYLES[key];
  const Icon = s.icon;
  const title =
    key === "expiring_soon" && data.days_remaining != null
      ? `Tu firma vence en ${data.days_remaining} día${data.days_remaining === 1 ? "" : "s"}`
      : s.title;

  return (
    <div className="px-4 pt-4 lg:px-6">
      <div
        className={`flex items-center gap-3 rounded-xl border px-4 py-3 ${s.wrap}`}
      >
        <Icon className="size-5 shrink-0" />
        <div className="min-w-0 flex-1 text-sm">
          <span className="font-medium">{title}.</span>{" "}
          <span className="text-foreground/70">{s.body}</span>
        </div>
        <Link
          href="/settings/firma"
          className="shrink-0 rounded-lg border border-current/30 px-3 py-1.5 text-xs font-medium transition hover:bg-current/10"
        >
          {s.cta}
        </Link>
        <button
          type="button"
          onClick={() => setDismissed(true)}
          aria-label="Descartar"
          className="shrink-0 rounded-md p-1 transition hover:bg-current/10"
        >
          <X className="size-4" />
        </button>
      </div>
    </div>
  );
}
