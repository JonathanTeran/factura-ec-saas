"use client";

import { useState } from "react";
import { Loader2, Upload } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Field } from "@/components/panel/form";
import {
  useBankAccounts,
  useSubscribeBankTransfer,
} from "@/lib/api/queries/subscription";
import { ClientApiError } from "@/lib/api/client";
import { formatMoney } from "@/lib/format";
import type { Plan } from "@/lib/api/types";

function errMessage(err: unknown): string {
  if (err instanceof ClientApiError) {
    const p = err.payload as { message?: string } | null;
    return p?.message ?? err.message;
  }
  return err instanceof Error ? err.message : "Error inesperado";
}

export function SubscribeDialog({
  plan,
  onOpenChange,
}: {
  plan: Plan | null;
  onOpenChange: (open: boolean) => void;
}) {
  const banksQ = useBankAccounts();
  const subscribe = useSubscribeBankTransfer();

  const [billingCycle, setBillingCycle] = useState<"monthly" | "yearly">(
    "monthly",
  );
  const [bankAccountId, setBankAccountId] = useState<number | null>(null);
  const [transferReference, setTransferReference] = useState("");
  const [billingName, setBillingName] = useState("");
  const [billingEmail, setBillingEmail] = useState("");
  const [billingIdentification, setBillingIdentification] = useState("");
  const [receipt, setReceipt] = useState<File | null>(null);

  if (!plan) return null;

  const price = billingCycle === "yearly" ? plan.priceYearly : plan.priceMonthly;
  const selectedBank = banksQ.data?.find((b) => b.id === bankAccountId);

  const reset = () => {
    setBillingCycle("monthly");
    setBankAccountId(null);
    setTransferReference("");
    setBillingName("");
    setBillingEmail("");
    setBillingIdentification("");
    setReceipt(null);
  };

  const submit = () => {
    if (!bankAccountId || !receipt || !transferReference.trim() || !billingName.trim() || !billingEmail.trim()) {
      toast.error("Completa la cuenta, referencia, comprobante y datos de facturación.");
      return;
    }
    subscribe.mutate(
      {
        planId: plan.id,
        billingCycle,
        bankAccountId,
        transferReceipt: receipt,
        transferReference: transferReference.trim(),
        billingName: billingName.trim(),
        billingEmail: billingEmail.trim(),
        billingIdentification: billingIdentification.trim() || undefined,
      },
      {
        onSuccess: () => {
          toast.success(
            "Comprobante recibido. Tu suscripción se activará una vez verificado el pago.",
          );
          reset();
          onOpenChange(false);
        },
        onError: (e) => toast.error(errMessage(e)),
      },
    );
  };

  return (
    <Dialog open={!!plan} onOpenChange={(o) => { if (!o) reset(); onOpenChange(o); }}>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Suscribirme a {plan.name}</DialogTitle>
          <DialogDescription>
            Transfiere a una de nuestras cuentas y sube el comprobante. Un
            administrador activa tu plan al verificar el pago (no hay cobro
            automático con tarjeta).
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          <Field label="Ciclo de facturación" required>
            <div className="grid grid-cols-2 gap-2">
              <button
                type="button"
                onClick={() => setBillingCycle("monthly")}
                className={`rounded-lg border p-3 text-left text-sm transition ${
                  billingCycle === "monthly"
                    ? "border-primary bg-primary/5"
                    : "border-input hover:border-ring/40"
                }`}
              >
                <p className="font-medium">Mensual</p>
                <p className="text-muted-foreground">
                  {formatMoney(plan.priceMonthly)} / mes
                </p>
              </button>
              <button
                type="button"
                onClick={() => setBillingCycle("yearly")}
                className={`rounded-lg border p-3 text-left text-sm transition ${
                  billingCycle === "yearly"
                    ? "border-primary bg-primary/5"
                    : "border-input hover:border-ring/40"
                }`}
              >
                <p className="font-medium">Anual</p>
                <p className="text-muted-foreground">
                  {formatMoney(plan.priceYearly)} / año
                </p>
              </button>
            </div>
          </Field>

          <Field label="Cuenta para transferir" required hint="Elige la cuenta a la que hiciste la transferencia.">
            {banksQ.isLoading ? (
              <div className="flex justify-center py-4">
                <Loader2 className="size-4 animate-spin text-muted-foreground" />
              </div>
            ) : (banksQ.data ?? []).length === 0 ? (
              <p className="rounded-lg border border-warning/30 bg-warning/5 p-3 text-sm text-muted-foreground">
                No hay cuentas bancarias configuradas todavía. Contacta a soporte.
              </p>
            ) : (
              <div className="space-y-2">
                {(banksQ.data ?? []).map((b) => (
                  <button
                    key={b.id}
                    type="button"
                    onClick={() => setBankAccountId(b.id)}
                    className={`w-full rounded-lg border p-3 text-left text-sm transition ${
                      bankAccountId === b.id
                        ? "border-primary bg-primary/5"
                        : "border-input hover:border-ring/40"
                    }`}
                  >
                    <p className="font-medium">
                      {b.bank_name}
                      {b.account_type ? ` · ${b.account_type}` : ""}
                    </p>
                    <p className="font-mono text-muted-foreground">{b.account_number}</p>
                    {b.holder_name && (
                      <p className="text-muted-foreground">
                        {b.holder_name}
                        {b.holder_identification ? ` · ${b.holder_identification}` : ""}
                      </p>
                    )}
                  </button>
                ))}
              </div>
            )}
          </Field>

          {selectedBank?.instructions && (
            <p className="text-xs text-muted-foreground">{selectedBank.instructions}</p>
          )}

          <Field label="Número de referencia de la transferencia" required htmlFor="transfer_reference">
            <Input
              id="transfer_reference"
              placeholder="Ej. 000123456"
              value={transferReference}
              onChange={(e) => setTransferReference(e.target.value)}
            />
          </Field>

          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Nombre de facturación" required htmlFor="billing_name">
              <Input
                id="billing_name"
                value={billingName}
                onChange={(e) => setBillingName(e.target.value)}
              />
            </Field>
            <Field label="Correo de facturación" required htmlFor="billing_email">
              <Input
                id="billing_email"
                type="email"
                value={billingEmail}
                onChange={(e) => setBillingEmail(e.target.value)}
              />
            </Field>
          </div>

          <Field label="RUC / Cédula de facturación" htmlFor="billing_identification">
            <Input
              id="billing_identification"
              value={billingIdentification}
              onChange={(e) => setBillingIdentification(e.target.value)}
            />
          </Field>

          <Field label="Comprobante de transferencia" required hint="Imagen (JPG/PNG), máx. 5MB.">
            <label className="flex cursor-pointer items-center gap-3 rounded-lg border border-dashed border-input bg-card px-4 py-4 text-sm transition hover:border-primary/40">
              <Upload className="size-5 text-muted-foreground" />
              <span className={receipt ? "font-medium" : "text-muted-foreground"}>
                {receipt ? receipt.name : "Selecciona la imagen del comprobante"}
              </span>
              <input
                type="file"
                accept="image/*"
                className="hidden"
                onChange={(e) => setReceipt(e.target.files?.[0] ?? null)}
              />
            </label>
          </Field>

          <p className="text-sm font-medium">
            Total a pagar: {formatMoney(price)} ({billingCycle === "yearly" ? "anual" : "mensual"})
          </p>
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancelar
          </Button>
          <Button onClick={submit} disabled={subscribe.isPending}>
            {subscribe.isPending && <Loader2 className="size-4 animate-spin" />}
            Enviar comprobante
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
