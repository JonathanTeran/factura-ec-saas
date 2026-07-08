"use client";

import { useState } from "react";
import { CheckCircle2, Clock, Loader2 } from "lucide-react";
import { toast } from "sonner";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from "@/components/ui/tabs";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  useBankAccounts,
  useCancelSubscription,
  useChangePlan,
  useCurrentSubscription,
  usePayments,
  usePlans,
  useResumeSubscription,
} from "@/lib/api/queries/subscription";
import { ClientApiError } from "@/lib/api/client";
import { formatDate, formatMoney } from "@/lib/format";
import { SubscribeDialog } from "./subscribe-dialog";
import type { Plan } from "@/lib/api/types";

function errMessage(err: unknown): string {
  if (err instanceof ClientApiError) {
    const p = err.payload as { message?: string } | null;
    return p?.message ?? err.message;
  }
  return err instanceof Error ? err.message : "Error inesperado";
}

export function SubscriptionView() {
  const currentQ = useCurrentSubscription();
  const plansQ = usePlans();
  const paymentsQ = usePayments();
  const banksQ = useBankAccounts();
  const cancel = useCancelSubscription();
  const resume = useResumeSubscription();
  const change = useChangePlan();

  const [confirmingPlan, setConfirmingPlan] = useState<number | null>(null);
  const [subscribingPlan, setSubscribingPlan] = useState<Plan | null>(null);

  if (currentQ.isLoading) {
    return (
      <div className="flex justify-center py-24">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  const sub = currentQ.data?.subscription;
  const plan = currentQ.data?.plan;
  // Comprobante de transferencia esperando verificación: se muestra el aviso
  // y se bloquea enviar otro (el backend también lo rechaza con 422).
  const pendingPayment = currentQ.data?.pendingPayment;

  return (
    <div className="space-y-6">
      {pendingPayment && (
        <Card className="border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-950/30">
          <CardContent className="flex items-start gap-3 py-4">
            <Clock className="size-5 shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" />
            <div>
              <p className="font-medium text-amber-900 dark:text-amber-200">
                Pago en revisión
              </p>
              <p className="text-sm text-amber-800 dark:text-amber-300 mt-1">
                Recibimos tu comprobante de transferencia el{" "}
                {formatDate(pendingPayment.created_at)}. Estamos verificando el
                pago — te avisaremos por correo cuando tu plan quede activo
                (normalmente en menos de 24 horas). No es necesario enviar otro
                comprobante.
              </p>
            </div>
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Plan actual</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-3">
          <div>
            <p className="text-xs text-muted-foreground">Plan</p>
            <p className="text-xl font-semibold">{plan?.name ?? "Sin plan"}</p>
            {plan && (
              <p className="text-sm text-muted-foreground">
                {formatMoney(plan.price)} / {plan.interval}
              </p>
            )}
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Estado</p>
            <Badge
              variant={sub?.status === "active" ? "default" : "secondary"}
              className="mt-1 capitalize"
            >
              {sub?.status ?? "—"}
            </Badge>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Próxima renovación</p>
            <p className="text-sm font-medium">
              {sub?.ends_at ? formatDate(sub.ends_at) : "—"}
            </p>
          </div>
          <div className="sm:col-span-3 flex gap-2">
            {sub?.status === "active" && (
              <Button
                variant="outline"
                disabled={cancel.isPending}
                onClick={() =>
                  cancel.mutate(undefined, {
                    onSuccess: () => toast.success("Suscripción cancelada"),
                    onError: (e) => toast.error(errMessage(e)),
                  })
                }
              >
                {cancel.isPending && (
                  <Loader2 className="size-4 animate-spin" />
                )}
                Cancelar suscripción
              </Button>
            )}
            {sub?.status === "cancelled" && (
              <Button
                disabled={resume.isPending}
                onClick={() =>
                  resume.mutate(undefined, {
                    onSuccess: () => toast.success("Suscripción reanudada"),
                    onError: (e) => toast.error(errMessage(e)),
                  })
                }
              >
                {resume.isPending && (
                  <Loader2 className="size-4 animate-spin" />
                )}
                Reanudar
              </Button>
            )}
          </div>
        </CardContent>
      </Card>

      <Tabs defaultValue="plans">
        <TabsList>
          <TabsTrigger value="plans">Planes</TabsTrigger>
          <TabsTrigger value="payments">Pagos</TabsTrigger>
          <TabsTrigger value="banks">Cuentas bancarias</TabsTrigger>
        </TabsList>

        <TabsContent value="plans" className="mt-4">
          {plansQ.isLoading ? (
            <Loading />
          ) : plansQ.error ? (
            <Err error={plansQ.error} />
          ) : (
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {(plansQ.data ?? []).map((p) => {
                const isCurrent = plan?.id === p.id;
                return (
                  <Card
                    key={p.id}
                    className={isCurrent ? "ring-2 ring-primary" : ""}
                  >
                    <CardHeader>
                      <CardTitle className="text-lg">{p.name}</CardTitle>
                      <p className="text-2xl font-semibold mt-1">
                        {formatMoney(p.price)}
                      </p>
                      <p className="text-xs text-muted-foreground">
                        / {p.interval}
                      </p>
                    </CardHeader>
                    <CardContent className="space-y-3">
                      {p.features && p.features.length > 0 && (
                        <ul className="space-y-1 text-sm">
                          {p.features.map((f, i) => (
                            <li key={i} className="flex items-start gap-2">
                              <CheckCircle2 className="size-4 text-emerald-500 shrink-0 mt-0.5" />
                              <span>{f}</span>
                            </li>
                          ))}
                        </ul>
                      )}
                      {isCurrent ? (
                        <Badge variant="default" className="w-full justify-center py-1">
                          Plan actual
                        </Badge>
                      ) : sub?.is_active ? (
                        // Upgrade (plan más caro) => pago por transferencia.
                        // Downgrade / mismo precio => cambio inmediato.
                        plan && p.price > plan.price ? (
                          <Button
                            className="w-full"
                            disabled={!!pendingPayment}
                            title={pendingPayment ? "Ya tienes un pago en revisión" : undefined}
                            onClick={() => setSubscribingPlan(p)}
                          >
                            {pendingPayment ? "Pago en revisión" : "Mejorar a este plan"}
                          </Button>
                        ) : (
                          <Button
                            className="w-full"
                            variant="outline"
                            disabled={change.isPending}
                            onClick={() => {
                              setConfirmingPlan(p.id);
                              change.mutate(
                                { planId: p.id, billingCycle: sub.billing_cycle ?? "monthly" },
                                {
                                  onSuccess: () => {
                                    toast.success(`Cambiaste al plan ${p.name}`);
                                    setConfirmingPlan(null);
                                  },
                                  onError: (e) => {
                                    toast.error(errMessage(e));
                                    setConfirmingPlan(null);
                                  },
                                },
                              );
                            }}
                          >
                            {change.isPending && confirmingPlan === p.id && (
                              <Loader2 className="size-4 animate-spin" />
                            )}
                            Cambiar a este plan
                          </Button>
                        )
                      ) : (
                        <Button
                          className="w-full"
                          variant="outline"
                          disabled={!!pendingPayment}
                          title={pendingPayment ? "Ya tienes un pago en revisión" : undefined}
                          onClick={() => setSubscribingPlan(p)}
                        >
                          {pendingPayment ? "Pago en revisión" : "Suscribirme"}
                        </Button>
                      )}
                    </CardContent>
                  </Card>
                );
              })}
            </div>
          )}
        </TabsContent>

        <TabsContent value="payments" className="mt-4">
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Historial de pagos</CardTitle>
            </CardHeader>
            <CardContent>
              {paymentsQ.isLoading ? (
                <Loading />
              ) : paymentsQ.error ? (
                <Err error={paymentsQ.error} />
              ) : (paymentsQ.data ?? []).length === 0 ? (
                <p className="text-sm text-muted-foreground py-8 text-center">
                  Sin pagos registrados.
                </p>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Fecha</TableHead>
                      <TableHead>Método</TableHead>
                      <TableHead>Estado</TableHead>
                      <TableHead className="text-right">Monto</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {(paymentsQ.data ?? []).map((p) => (
                      <TableRow key={p.id}>
                        <TableCell>
                          {p.paid_at
                            ? formatDate(p.paid_at)
                            : formatDate(p.created_at)}
                        </TableCell>
                        <TableCell className="text-sm capitalize">
                          {p.payment_method ?? "—"}
                        </TableCell>
                        <TableCell>
                          <Badge variant="secondary" className="capitalize">
                            {p.status}
                          </Badge>
                        </TableCell>
                        <TableCell className="text-right font-medium">
                          {formatMoney(p.amount)}
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="banks" className="mt-4">
          <Card>
            <CardHeader>
              <CardTitle className="text-base">
                Cuentas para transferencia
              </CardTitle>
            </CardHeader>
            <CardContent>
              {banksQ.isLoading ? (
                <Loading />
              ) : banksQ.error ? (
                <Err error={banksQ.error} />
              ) : (banksQ.data ?? []).length === 0 ? (
                <p className="text-sm text-muted-foreground py-8 text-center">
                  No hay cuentas configuradas.
                </p>
              ) : (
                <div className="grid gap-3 sm:grid-cols-2">
                  {(banksQ.data ?? []).map((b) => (
                    <div
                      key={b.id}
                      className="rounded-lg border p-4"
                    >
                      <p className="text-xs text-muted-foreground">
                        {b.bank_name}
                      </p>
                      <p className="font-mono text-base mt-1">
                        {b.account_number}
                      </p>
                      <p className="text-xs text-muted-foreground mt-2">
                        {b.account_type ?? ""}
                      </p>
                      {b.holder_name && (
                        <p className="text-sm mt-1">
                          {b.holder_name}
                          {b.holder_identification
                            ? ` · ${b.holder_identification}`
                            : ""}
                        </p>
                      )}
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      <SubscribeDialog
        plan={subscribingPlan}
        onOpenChange={(open) => {
          if (!open) setSubscribingPlan(null);
        }}
      />
    </div>
  );
}

function Loading() {
  return (
    <div className="flex justify-center py-12">
      <Loader2 className="size-5 animate-spin text-muted-foreground" />
    </div>
  );
}

function Err({ error }: { error: unknown }) {
  return (
    <div className="text-sm text-destructive py-6 text-center">
      Error: {(error as Error).message}
    </div>
  );
}
