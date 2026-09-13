"use client";

import { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import {
  AlertTriangle,
  ChevronLeft,
  Loader2,
  Pause,
  Pencil,
  Play,
  Zap,
} from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { PageHeader } from "@/components/panel/page-header";
import { DeleteConfirmButton } from "@/components/forms/delete-confirm-button";
import { TablePagination } from "@/components/panel/table-pagination";
import {
  FREQ_LABELS,
  useDeleteRecurringInvoice,
  useGenerateRecurringInvoice,
  useRecurringInvoice,
  useRecurringInvoiceAction,
  useRecurringInvoiceDocuments,
} from "@/lib/api/queries/recurring-invoices";
import { ClientApiError } from "@/lib/api/client";
import type { RecurringInvoice } from "@/lib/api/types";
import { documentStatusMeta } from "@/lib/status";
import { formatDate, formatMoney } from "@/lib/format";

const STATUS_LABELS: Record<string, string> = {
  active: "Activa",
  paused: "Pausada",
  completed: "Completada",
  cancelled: "Cancelada",
};

const PAYMENT_METHOD_LABELS: Record<string, string> = {
  "01": "Sin uso del sistema financiero",
  "15": "Compensación de deudas",
  "16": "Tarjeta de débito",
  "17": "Dinero electrónico",
  "18": "Tarjeta prepago",
  "19": "Tarjeta de crédito",
  "20": "Otros con utilización del sistema financiero",
  "21": "Endoso de títulos",
};

function statusVariant(s: string): "default" | "secondary" | "destructive" {
  if (s === "active") return "default";
  if (s === "cancelled") return "destructive";
  return "secondary";
}

function errMessage(err: unknown): string {
  if (err instanceof ClientApiError) {
    const p = err.payload as
      | { message?: string; errors?: Record<string, string[]> }
      | null;
    const first = p?.errors ? Object.values(p.errors).flat()[0] : null;
    return first ?? p?.message ?? err.message;
  }
  return err instanceof Error ? err.message : "Error inesperado";
}

export function RecurringDetail({ id }: { id: number }) {
  const router = useRouter();
  const { data: recurring, isLoading, error } = useRecurringInvoice(id);
  const action = useRecurringInvoiceAction();
  const del = useDeleteRecurringInvoice();
  const [generateOpen, setGenerateOpen] = useState(false);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (error || !recurring) {
    return (
      <div className="p-6">
        <p className="text-sm text-destructive">No se pudo cargar la recurrente.</p>
        <Button variant="outline" asChild className="mt-4">
          <Link href="/recurring-invoices">
            <ChevronLeft className="size-4" />
            Volver
          </Link>
        </Button>
      </div>
    );
  }

  const title = recurring.name || recurring.customer?.name || `Recurrente #${recurring.id}`;
  const payment = recurring.payment_methods?.[0];

  const runAction = (kind: "pause" | "resume", ok: string) =>
    action.mutate(
      { id: recurring.id, action: kind },
      {
        onSuccess: () => toast.success(ok),
        onError: (e) => toast.error(errMessage(e)),
      },
    );

  return (
    <div className="pb-10">
      <PageHeader
        title={title}
        description={`${recurring.frequency_label ?? FREQ_LABELS[recurring.frequency] ?? recurring.frequency} · ${recurring.customer?.name ?? "—"}`}
        actions={
          <>
            <Button variant="outline" asChild>
              <Link href="/recurring-invoices">
                <ChevronLeft className="size-4" />
                Volver
              </Link>
            </Button>
            <Button variant="outline" asChild>
              <Link href={`/recurring-invoices/${recurring.id}/edit`}>
                <Pencil className="size-4" />
                Editar
              </Link>
            </Button>
            {recurring.status === "active" && (
              <Button variant="outline" disabled={action.isPending} onClick={() => runAction("pause", "Recurrente pausada")}>
                <Pause className="size-4" />
                Pausar
              </Button>
            )}
            {recurring.status === "paused" && (
              <Button variant="outline" disabled={action.isPending} onClick={() => runAction("resume", "Recurrente reanudada")}>
                <Play className="size-4" />
                Reanudar
              </Button>
            )}
            {recurring.can_issue && (
              <Button onClick={() => setGenerateOpen(true)}>
                <Zap className="size-4" />
                Emitir ahora
              </Button>
            )}
            <DeleteConfirmButton
              onConfirm={async () => {
                await del.mutateAsync(recurring.id);
                router.push("/recurring-invoices");
              }}
              isPending={del.isPending}
              title="¿Eliminar recurrente?"
              description="Las facturas ya generadas se conservan; solo se detiene la programación."
              successMessage="Recurrente eliminada"
              iconOnly
            />
          </>
        }
      />

      <div className="space-y-4 px-4 pt-4 lg:px-6">
        {recurring.last_error && (
          <div className="flex items-start gap-3 rounded-xl border border-warning/40 bg-warning/10 p-4 text-sm">
            <AlertTriangle className="mt-0.5 size-4 shrink-0 text-warning" />
            <div>
              <p className="font-medium">La última emisión falló{recurring.last_error_at ? ` (${formatDate(recurring.last_error_at)})` : ""}</p>
              <p className="text-muted-foreground">{recurring.last_error}</p>
              <p className="mt-1 text-muted-foreground">
                Corrige el problema y usa “Emitir ahora” o espera al próximo lote diario.
              </p>
            </div>
          </div>
        )}

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <InfoCard label="Estado">
            <Badge variant={statusVariant(recurring.status)}>
              {recurring.status_label ?? STATUS_LABELS[recurring.status] ?? recurring.status}
            </Badge>
          </InfoCard>
          <InfoCard label="Próxima emisión">
            {recurring.status === "active" && recurring.next_issue_date
              ? formatDate(recurring.next_issue_date)
              : "—"}
          </InfoCard>
          <InfoCard label="Emitidas">
            {recurring.total_issued}
            {recurring.max_issues != null && (
              <span className="text-muted-foreground"> / {recurring.max_issues}</span>
            )}
          </InfoCard>
          <InfoCard label="Total por emisión">
            <span className="tabular-nums">{formatMoney(recurring.estimated_total ?? 0)}</span>
          </InfoCard>
        </div>

        <div className="grid gap-4 lg:grid-cols-3">
          <Card className="lg:col-span-2">
            <CardHeader>
              <CardTitle>Ítems de cada factura</CardTitle>
            </CardHeader>
            <CardContent className="p-0">
              <div className="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow className="hover:bg-transparent">
                      <TableHead>Descripción</TableHead>
                      <TableHead className="text-right">Cant.</TableHead>
                      <TableHead className="text-right">P. unit.</TableHead>
                      <TableHead className="text-right">Desc.</TableHead>
                      <TableHead className="text-right">IVA</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {recurring.items.map((it, idx) => (
                      <TableRow key={idx}>
                        <TableCell className="font-medium">
                          {it.description}
                          {it.main_code && (
                            <span className="ml-2 font-mono text-xs text-muted-foreground">{it.main_code}</span>
                          )}
                        </TableCell>
                        <TableCell className="text-right tabular-nums">{it.quantity}</TableCell>
                        <TableCell className="text-right tabular-nums">{formatMoney(it.unit_price)}</TableCell>
                        <TableCell className="text-right tabular-nums">{formatMoney(it.discount ?? 0)}</TableCell>
                        <TableCell className="text-right tabular-nums">{it.tax_rate ?? 0}%</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-base">Configuración</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2 text-sm">
              <Row label="Empresa" value={recurring.company?.trade_name || recurring.company?.business_name || "—"} />
              <Row
                label="Punto de emisión"
                value={recurring.branch && recurring.emission_point ? `${recurring.branch.code}-${recurring.emission_point.code}` : "—"}
              />
              <Row label="Inicio" value={formatDate(recurring.start_date)} />
              <Row label="Fin" value={recurring.end_date ? formatDate(recurring.end_date) : "Sin fecha de fin"} />
              <Row
                label="Forma de pago"
                value={payment ? `${PAYMENT_METHOD_LABELS[payment.code] ?? payment.code}${payment.term ? ` · ${payment.term} días` : ""}` : "—"}
              />
              <Row label="Envío al SRI" value={recurring.auto_send ? "Automático" : "Manual (borrador)"} />
              <Row
                label="Aviso previo"
                value={recurring.notify_before_issue && (recurring.notify_days_before ?? 0) > 0 ? `${recurring.notify_days_before} día(s) antes` : "Sin aviso"}
              />
              {recurring.last_issued_at && <Row label="Última emisión" value={formatDate(recurring.last_issued_at)} />}
              {recurring.notes && <Row label="Notas" value={recurring.notes} />}
            </CardContent>
          </Card>
        </div>

        <HistoryCard recurring={recurring} />
      </div>

      <GenerateDialog recurring={recurring} open={generateOpen} onOpenChange={setGenerateOpen} />
    </div>
  );
}

function HistoryCard({ recurring }: { recurring: RecurringInvoice }) {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(10);
  const { data, isLoading, isFetching } = useRecurringInvoiceDocuments(recurring.id, { page, per_page: perPage });
  const docs = data?.data ?? [];

  return (
    <Card>
      <CardHeader>
        <CardTitle>Facturas generadas</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        {isLoading ? (
          <Loader2 className="mx-auto my-8 size-5 animate-spin text-muted-foreground" />
        ) : docs.length === 0 ? (
          <p className="py-8 text-center text-sm text-muted-foreground">
            Todavía no se ha generado ninguna factura.
          </p>
        ) : (
          <div className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow className="hover:bg-transparent">
                  <TableHead>Número</TableHead>
                  <TableHead>Fecha</TableHead>
                  <TableHead>Estado</TableHead>
                  <TableHead className="text-right">Total</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {docs.map((d) => {
                  const meta = documentStatusMeta(d.status);
                  return (
                    <TableRow key={d.id}>
                      <TableCell className="font-mono text-xs">
                        <Link href={`/documents/${d.id}`} className="block py-1 font-medium">
                          {d.document_number ?? `#${d.id}`}
                        </Link>
                      </TableCell>
                      <TableCell>{formatDate(d.issue_date)}</TableCell>
                      <TableCell>
                        <Badge variant="outline" className={meta.className}>
                          {d.status_label ?? meta.label}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right tabular-nums">{formatMoney(d.total)}</TableCell>
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
          </div>
        )}
        {data?.meta && data.meta.total > perPage && (
          <TablePagination
            meta={data.meta}
            page={page}
            onPageChange={setPage}
            perPage={perPage}
            onPerPageChange={setPerPage}
            isFetching={isFetching}
          />
        )}
      </CardContent>
    </Card>
  );
}

function GenerateDialog({
  recurring,
  open,
  onOpenChange,
}: {
  recurring: RecurringInvoice;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}) {
  const router = useRouter();
  const generate = useGenerateRecurringInvoice(recurring.id);
  const [send, setSend] = useState(recurring.auto_send ?? true);

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Emitir ahora</DialogTitle>
          <DialogDescription>
            Se genera la factura de {formatMoney(recurring.estimated_total ?? 0)} hoy mismo y el calendario avanza a la siguiente fecha.
          </DialogDescription>
        </DialogHeader>
        <label className="flex cursor-pointer items-start gap-3 rounded-lg border border-border p-3 text-sm">
          <input
            type="checkbox"
            className="mt-0.5 size-4 accent-primary"
            checked={send}
            onChange={(e) => setSend(e.target.checked)}
          />
          <span>
            <span className="font-medium">Enviar al SRI ahora</span>
            <span className="block text-muted-foreground">
              Si lo desmarcas, la factura queda en borrador para revisarla antes de emitirla.
            </span>
          </span>
        </label>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancelar
          </Button>
          <Button
            disabled={generate.isPending}
            onClick={() =>
              generate.mutate(
                { send },
                {
                  onSuccess: (res) => {
                    toast.success(res.message ?? "Factura generada");
                    onOpenChange(false);
                    router.push(`/documents/${res.data.document.id}`);
                  },
                  onError: (e) => toast.error(errMessage(e)),
                },
              )
            }
          >
            {generate.isPending && <Loader2 className="size-4 animate-spin" />}
            {send ? "Emitir y enviar" : "Generar borrador"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function InfoCard({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <Card>
      <CardContent className="p-5">
        <p className="text-sm text-muted-foreground">{label}</p>
        <div className="mt-1.5 text-base font-medium">{children}</div>
      </CardContent>
    </Card>
  );
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex items-start justify-between gap-4">
      <span className="shrink-0 text-muted-foreground">{label}</span>
      <span className="text-right">{value}</span>
    </div>
  );
}
