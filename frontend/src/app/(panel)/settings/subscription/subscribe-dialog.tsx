"use client";

import { useState, type ComponentType } from "react";
import {
  Building2,
  ExternalLink,
  Loader2,
  ShieldCheck,
  Upload,
  Wallet,
} from "lucide-react";
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
  quoteAmounts,
  useBankAccounts,
  useCheckoutOptions,
  useCreatePayPalOrder,
  useSubscribeBankTransfer,
} from "@/lib/api/queries/subscription";
import { useProfile } from "@/lib/api/queries/profile";
import { useCompanies } from "@/lib/api/queries/companies";
import { ClientApiError } from "@/lib/api/client";
import { formatMoney } from "@/lib/format";
import { redirectTo } from "@/lib/navigation";
import { cn } from "@/lib/utils";
import type { Plan } from "@/lib/api/types";

type Method = "paypal" | "transfer";

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
  const optionsQ = useCheckoutOptions();
  const profileQ = useProfile();
  const companiesQ = useCompanies();
  const banksQ = useBankAccounts();
  const subscribe = useSubscribeBankTransfer();
  const createOrder = useCreatePayPalOrder();

  const [billingCycle, setBillingCycle] = useState<"monthly" | "yearly">(
    "monthly",
  );
  const [methodChoice, setMethodChoice] = useState<Method | null>(null);
  const [bankAccountId, setBankAccountId] = useState<number | null>(null);
  const [transferReference, setTransferReference] = useState("");
  // null = aún sin tocar: se muestra el dato del perfil.
  const [billingName, setBillingName] = useState<string | null>(null);
  const [billingEmail, setBillingEmail] = useState<string | null>(null);
  // null = aún sin tocar: se muestra el RUC/cédula ya registrado.
  const [billingIdentification, setBillingIdentification] = useState<
    string | null
  >(null);
  const [receipt, setReceipt] = useState<File | null>(null);
  const [redirecting, setRedirecting] = useState(false);
  const [bankAutoSelected, setBankAutoSelected] = useState(false);

  if (!plan) return null;

  const paypal = optionsQ.data?.paypal;
  const paypalEnabled = paypal?.enabled === true;
  const method: Method = paypalEnabled ? (methodChoice ?? "paypal") : "transfer";
  const taxRate = optionsQ.data?.tax_rate ?? 15;
  const price = billingCycle === "yearly" ? plan.priceYearly : plan.priceMonthly;
  const amounts = quoteAmounts(price, taxRate);
  const selectedBank = banksQ.data?.find((b) => b.id === bankAccountId);
  const name = billingName ?? profileQ.data?.tenant?.name ?? profileQ.data?.name ?? "";
  const email = billingEmail ?? profileQ.data?.email ?? "";
  // La empresa activa del usuario si existe entre sus RUCs; si no, la primera
  // registrada (la inmensa mayoría de las cuentas tiene una sola).
  const registeredCompany =
    companiesQ.data?.find((c) => c.id === profileQ.data?.current_company_id) ??
    companiesQ.data?.[0];
  const identification = billingIdentification ?? registeredCompany?.ruc ?? "";
  const busy = subscribe.isPending || createOrder.isPending || redirecting;

  // Con una sola cuenta configurada (el caso más común) no hay nada que
  // elegir: la seleccionamos sola en vez de obligar a un clic sobre la única
  // opción visible. Una sola vez por apertura del diálogo (igual que
  // "autoOpened" en subscription-view.tsx).
  if (!bankAutoSelected && banksQ.data?.length === 1) {
    setBankAutoSelected(true);
    setBankAccountId(banksQ.data[0].id);
  }

  const reset = () => {
    setBillingCycle("monthly");
    setMethodChoice(null);
    setBankAccountId(null);
    setTransferReference("");
    setBillingName(null);
    setBillingEmail(null);
    setBillingIdentification(null);
    setReceipt(null);
    setBankAutoSelected(false);
  };

  const payWithPayPal = () => {
    if (!name.trim() || !email.trim()) {
      toast.error("Completa el nombre y el correo de facturación.");
      return;
    }
    createOrder.mutate(
      {
        planId: plan.id,
        billingCycle,
        billingName: name.trim(),
        billingEmail: email.trim(),
        billingIdentification: identification.trim() || undefined,
      },
      {
        onSuccess: (res) => {
          setRedirecting(true);
          redirectTo(res.data.approve_url);
        },
        onError: (e) => toast.error(errMessage(e)),
      },
    );
  };

  const submitTransfer = () => {
    // Mensajes puntuales: el genérico ("completa todo") no dejaba ver cuál
    // de los cinco campos faltaba, y con una sola cuenta bancaria era fácil
    // no darse cuenta de que igual había que seleccionarla con un clic.
    if (!bankAccountId) {
      toast.error("Elige la cuenta a la que hiciste la transferencia.");
      return;
    }
    if (!transferReference.trim()) {
      toast.error("Escribe el número de referencia de la transferencia.");
      return;
    }
    if (!receipt) {
      toast.error("Adjunta el comprobante de la transferencia.");
      return;
    }
    if (!name.trim() || !email.trim()) {
      toast.error("Completa el nombre y el correo de facturación.");
      return;
    }
    subscribe.mutate(
      {
        planId: plan.id,
        billingCycle,
        bankAccountId,
        transferReceipt: receipt,
        transferReference: transferReference.trim(),
        billingName: name.trim(),
        billingEmail: email.trim(),
        billingIdentification: identification.trim() || undefined,
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
    <Dialog
      open={!!plan}
      onOpenChange={(o) => {
        if (redirecting) return;
        if (!o) reset();
        onOpenChange(o);
      }}
    >
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Contratar {plan.name}</DialogTitle>
          <DialogDescription>
            {paypalEnabled
              ? "Paga con PayPal y tu plan se activa al instante, o por transferencia bancaria."
              : "Transfiere a una de nuestras cuentas y sube el comprobante. Activamos tu plan al verificar el pago, normalmente en menos de 24 horas."}
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-5">
          <Field label="Ciclo de facturación" required>
            <div className="grid grid-cols-2 gap-2">
              <ChoiceButton
                active={billingCycle === "monthly"}
                onClick={() => setBillingCycle("monthly")}
                title="Mensual"
                description={`${formatMoney(plan.priceMonthly)} / mes`}
              />
              <ChoiceButton
                active={billingCycle === "yearly"}
                onClick={() => setBillingCycle("yearly")}
                title="Anual"
                description={`${formatMoney(plan.priceYearly)} / año`}
              />
            </div>
          </Field>

          {paypalEnabled && (
            <Field label="Método de pago" required>
              <div role="radiogroup" aria-label="Método de pago" className="grid gap-2 sm:grid-cols-2">
                <ChoiceButton
                  role="radio"
                  active={method === "paypal"}
                  onClick={() => setMethodChoice("paypal")}
                  icon={Wallet}
                  title="PayPal"
                  description="Saldo PayPal o tarjeta. Tu plan se activa al instante."
                  badge={paypal?.sandbox ? "Modo pruebas" : undefined}
                />
                <ChoiceButton
                  role="radio"
                  active={method === "transfer"}
                  onClick={() => setMethodChoice("transfer")}
                  icon={Building2}
                  title="Transferencia bancaria"
                  description="Subes el comprobante y activamos tu plan en menos de 24 horas."
                />
              </div>
            </Field>
          )}

          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Nombre de facturación" required htmlFor="billing_name">
              <Input
                id="billing_name"
                value={name}
                onChange={(e) => setBillingName(e.target.value)}
              />
            </Field>
            <Field label="Correo de facturación" required htmlFor="billing_email">
              <Input
                id="billing_email"
                type="email"
                value={email}
                onChange={(e) => setBillingEmail(e.target.value)}
              />
            </Field>
          </div>

          <Field
            label="RUC / Cédula de facturación"
            htmlFor="billing_identification"
            hint={
              registeredCompany?.ruc
                ? "Tomado del RUC/cédula ya registrado. Puedes cambiarlo."
                : undefined
            }
          >
            <Input
              id="billing_identification"
              value={identification}
              onChange={(e) => setBillingIdentification(e.target.value)}
            />
          </Field>

          {method === "transfer" && (
            <>
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

              <Field label="Comprobante de transferencia" required hint="Imagen (JPG/PNG/WebP) o PDF, máx. 5MB.">
                <label className="flex cursor-pointer items-center gap-3 rounded-lg border border-dashed border-input bg-card px-4 py-4 text-sm transition hover:border-primary/40">
                  <Upload className="size-5 text-muted-foreground" />
                  <span className={receipt ? "font-medium" : "text-muted-foreground"}>
                    {receipt ? receipt.name : "Selecciona el comprobante"}
                  </span>
                  <input
                    type="file"
                    accept="image/*,application/pdf"
                    className="hidden"
                    onChange={(e) => setReceipt(e.target.files?.[0] ?? null)}
                  />
                </label>
              </Field>
            </>
          )}

          <dl
            aria-label="Resumen del pago"
            className="space-y-1.5 rounded-xl border border-border bg-muted/40 px-4 py-3 text-sm"
          >
            <div className="flex justify-between gap-4">
              <dt className="text-muted-foreground">
                Plan {plan.name} ({billingCycle === "yearly" ? "anual" : "mensual"})
              </dt>
              <dd className="tabular-nums">{formatMoney(amounts.subtotal)}</dd>
            </div>
            <div className="flex justify-between gap-4">
              <dt className="text-muted-foreground">IVA {taxRate} %</dt>
              <dd className="tabular-nums">{formatMoney(amounts.tax)}</dd>
            </div>
            <div className="flex justify-between gap-4 border-t border-border pt-1.5 font-semibold">
              <dt>Total a pagar</dt>
              <dd className="tabular-nums">{formatMoney(amounts.total)}</dd>
            </div>
          </dl>

          {method === "paypal" && (
            <p className="flex items-start gap-2 text-xs leading-relaxed text-muted-foreground">
              <ShieldCheck className="mt-0.5 size-4 shrink-0 text-emerald-600" />
              Te llevamos a PayPal para pagar de forma segura. Facturón no ve ni guarda los datos
              de tu tarjeta. Al confirmar vuelves aquí con tu plan activo.
            </p>
          )}
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)} disabled={redirecting}>
            Cancelar
          </Button>
          {method === "paypal" ? (
            <Button onClick={payWithPayPal} disabled={busy}>
              {busy ? (
                <Loader2 className="size-4 animate-spin" />
              ) : (
                <ExternalLink className="size-4" />
              )}
              {redirecting ? "Abriendo PayPal…" : `Pagar ${formatMoney(amounts.total)} con PayPal`}
            </Button>
          ) : (
            <Button onClick={submitTransfer} disabled={subscribe.isPending}>
              {subscribe.isPending && <Loader2 className="size-4 animate-spin" />}
              Enviar comprobante
            </Button>
          )}
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function ChoiceButton({
  active,
  onClick,
  title,
  description,
  icon: Icon,
  badge,
  role,
}: {
  active: boolean;
  onClick: () => void;
  title: string;
  description: string;
  icon?: ComponentType<{ className?: string }>;
  badge?: string;
  role?: "radio";
}) {
  return (
    <button
      type="button"
      role={role}
      aria-checked={role === "radio" ? active : undefined}
      aria-pressed={role === "radio" ? undefined : active}
      onClick={onClick}
      className={cn(
        "rounded-lg border p-3 text-left text-sm transition",
        active ? "border-primary bg-primary/5 ring-1 ring-primary/30" : "border-input hover:border-ring/40",
      )}
    >
      <p className="flex items-center gap-2 font-medium">
        {Icon && <Icon className={cn("size-4", active ? "text-primary" : "text-muted-foreground")} />}
        {title}
        {badge && (
          <span className="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
            {badge}
          </span>
        )}
      </p>
      <p className="mt-0.5 text-muted-foreground">{description}</p>
    </button>
  );
}
