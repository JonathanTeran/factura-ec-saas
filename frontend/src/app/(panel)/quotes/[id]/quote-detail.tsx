"use client";

import { useMemo, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import {
  ArrowRightLeft,
  Check,
  ChevronLeft,
  FileDown,
  FileSpreadsheet,
  Loader2,
  Mail,
  Pencil,
  X,
} from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
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
import { useCompanyBranches } from "@/lib/api/queries/companies";
import {
  downloadQuotePdf,
  useConvertQuote,
  useDeleteQuote,
  useQuote,
  useQuoteAction,
  useSendQuote,
} from "@/lib/api/queries/quotes";
import { ClientApiError } from "@/lib/api/client";
import type { Quote } from "@/lib/api/types";
import { documentStatusMeta } from "@/lib/status";
import { formatDate, formatMoney } from "@/lib/format";

const STATUS_CLASS: Record<string, string> = {
  accepted: "border-transparent bg-success/10 text-success",
  invoiced: "border-transparent bg-success/10 text-success",
  sent: "border-transparent bg-warning/10 text-warning",
  draft: "border-transparent bg-muted text-muted-foreground",
  rejected: "border-transparent bg-destructive/10 text-destructive",
  expired: "border-transparent bg-destructive/10 text-destructive",
};

const PAYMENT_METHODS = [
  { code: "01", label: "Sin uso del sistema financiero" },
  { code: "15", label: "Compensación de deudas" },
  { code: "16", label: "Tarjeta de débito" },
  { code: "17", label: "Dinero electrónico" },
  { code: "18", label: "Tarjeta prepago" },
  { code: "19", label: "Tarjeta de crédito" },
  { code: "20", label: "Otros con utilización del sistema financiero" },
  { code: "21", label: "Endoso de títulos" },
];

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

export function QuoteDetail({ id }: { id: number }) {
  const router = useRouter();
  const { data: quote, isLoading, error } = useQuote(id);
  const action = useQuoteAction();
  const del = useDeleteQuote();
  const [sendOpen, setSendOpen] = useState(false);
  const [convertOpen, setConvertOpen] = useState(false);
  const [downloading, setDownloading] = useState(false);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (error || !quote) {
    return (
      <div className="p-6">
        <p className="text-sm text-destructive">No se pudo cargar la cotización.</p>
        <Button variant="outline" asChild className="mt-4">
          <Link href="/quotes">
            <ChevronLeft className="size-4" />
            Volver
          </Link>
        </Button>
      </div>
    );
  }

  const items = quote.items ?? [];
  const converted = quote.converted_document;

  const runAction = (kind: "accept" | "reject", ok: string) =>
    action.mutate(
      { id: quote.id, action: kind },
      {
        onSuccess: () => toast.success(ok),
        onError: (e) => toast.error(errMessage(e)),
      },
    );

  return (
    <div className="pb-10">
      <PageHeader
        title={`Cotización ${quote.quote_number}`}
        description={quote.customer?.name ?? "—"}
        actions={
          <>
            <Button variant="outline" asChild>
              <Link href="/quotes">
                <ChevronLeft className="size-4" />
                Volver
              </Link>
            </Button>
            <Button
              variant="outline"
              disabled={downloading}
              onClick={async () => {
                setDownloading(true);
                try {
                  await downloadQuotePdf(quote.id);
                } catch (e) {
                  toast.error(errMessage(e));
                } finally {
                  setDownloading(false);
                }
              }}
            >
              {downloading ? <Loader2 className="size-4 animate-spin" /> : <FileDown className="size-4" />}
              PDF
            </Button>
            {quote.can_edit && (
              <Button variant="outline" asChild>
                <Link href={`/quotes/${quote.id}/edit`}>
                  <Pencil className="size-4" />
                  Editar
                </Link>
              </Button>
            )}
            {quote.can_send && (
              <Button variant="outline" onClick={() => setSendOpen(true)}>
                <Mail className="size-4" />
                {quote.sent_at ? "Reenviar" : "Enviar por correo"}
              </Button>
            )}
            {quote.can_accept && (
              <Button variant="outline" disabled={action.isPending} onClick={() => runAction("accept", "Cotización aceptada")}>
                <Check className="size-4" />
                Aceptar
              </Button>
            )}
            {quote.can_reject && (
              <Button variant="ghost" disabled={action.isPending} onClick={() => runAction("reject", "Cotización rechazada")}>
                <X className="size-4" />
                Rechazar
              </Button>
            )}
            {quote.can_convert && (
              <Button onClick={() => setConvertOpen(true)}>
                <ArrowRightLeft className="size-4" />
                Convertir en factura
              </Button>
            )}
            {quote.can_delete && (
              <DeleteConfirmButton
                onConfirm={async () => {
                  await del.mutateAsync(quote.id);
                  router.push("/quotes");
                }}
                isPending={del.isPending}
                title={`¿Eliminar ${quote.quote_number}?`}
                successMessage="Cotización eliminada"
                iconOnly
              />
            )}
          </>
        }
      />

      <div className="space-y-4 px-4 pt-4 lg:px-6">
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <InfoCard label="Estado">
            <div className="flex flex-wrap items-center gap-2">
              <Badge
                variant="outline"
                className={STATUS_CLASS[quote.status] ?? "border-transparent bg-muted text-muted-foreground"}
              >
                {quote.status_label ?? quote.status}
              </Badge>
              {quote.is_expired && quote.status !== "expired" && (
                <Badge variant="outline" className={STATUS_CLASS.expired}>
                  Vencida
                </Badge>
              )}
            </div>
          </InfoCard>
          <InfoCard label="Fecha de emisión">{formatDate(quote.issue_date)}</InfoCard>
          <InfoCard label="Válida hasta">
            {quote.expiry_date ? formatDate(quote.expiry_date) : "Sin vencimiento"}
          </InfoCard>
          <InfoCard label="Total">
            <span className="tabular-nums">{formatMoney(quote.total)}</span>
          </InfoCard>
        </div>

        {converted && (
          <Card className="border-success/30 bg-success/5">
            <CardContent className="flex flex-col gap-2 p-5 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <p className="text-sm font-medium">Facturada</p>
                <p className="text-sm text-muted-foreground">
                  Factura {converted.document_number ?? `#${converted.id}`} ·{" "}
                  <Badge variant="outline" className={documentStatusMeta(converted.status).className}>
                    {converted.status_label ?? documentStatusMeta(converted.status).label}
                  </Badge>
                  {quote.converted_at && <> · {formatDate(quote.converted_at)}</>}
                </p>
              </div>
              <Button variant="outline" asChild>
                <Link href={`/documents/${converted.id}`}>Ver factura</Link>
              </Button>
            </CardContent>
          </Card>
        )}

        <div className="grid gap-4 lg:grid-cols-3">
          <Card className="lg:col-span-2">
            <CardHeader>
              <CardTitle>Ítems</CardTitle>
            </CardHeader>
            <CardContent className="p-0">
              {items.length === 0 ? (
                <div className="flex flex-col items-center gap-2 py-12 text-center text-sm text-muted-foreground">
                  <FileSpreadsheet className="size-6" />
                  Esta cotización no tiene ítems.
                </div>
              ) : (
                <div className="overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow className="hover:bg-transparent">
                        <TableHead>Descripción</TableHead>
                        <TableHead className="text-right">Cant.</TableHead>
                        <TableHead className="text-right">P. unit.</TableHead>
                        <TableHead className="text-right">Desc.</TableHead>
                        <TableHead className="text-right">IVA</TableHead>
                        <TableHead className="text-right">Subtotal</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {items.map((it) => (
                        <TableRow key={it.id}>
                          <TableCell className="font-medium">{it.description}</TableCell>
                          <TableCell className="text-right tabular-nums">{it.quantity}</TableCell>
                          <TableCell className="text-right tabular-nums">{formatMoney(it.unit_price)}</TableCell>
                          <TableCell className="text-right tabular-nums">{formatMoney(it.discount)}</TableCell>
                          <TableCell className="text-right tabular-nums">{it.tax_rate}%</TableCell>
                          <TableCell className="text-right tabular-nums">{formatMoney(it.subtotal)}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              )}
            </CardContent>
          </Card>

          <div className="space-y-4">
            <Card>
              <CardContent className="space-y-2 p-5 text-sm">
                <Row label="Subtotal" value={formatMoney(quote.subtotal)} />
                <Row label="Descuento" value={`- ${formatMoney(quote.total_discount)}`} />
                <Row label="IVA" value={formatMoney(quote.total_tax)} />
                <div className="my-1 border-t border-border" />
                <Row label="Total" value={formatMoney(quote.total)} strong />
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className="text-base">Seguimiento</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3 text-sm">
                <Timeline label="Creada" date={quote.created_at ?? quote.issue_date} />
                <Timeline
                  label={quote.sent_to ? `Enviada a ${quote.sent_to}` : "Enviada"}
                  date={quote.sent_at}
                  empty="Aún no se envía al cliente"
                />
                {quote.accepted_at && <Timeline label="Aceptada" date={quote.accepted_at} />}
                {quote.rejected_at && <Timeline label="Rechazada" date={quote.rejected_at} />}
                {quote.converted_at && <Timeline label="Convertida en factura" date={quote.converted_at} />}
              </CardContent>
            </Card>
          </div>
        </div>

        {(quote.payment_terms || quote.notes) && (
          <Card>
            <CardHeader>
              <CardTitle>Condiciones</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2 text-sm">
              {quote.payment_terms && (
                <p>
                  <span className="text-muted-foreground">Forma de pago: </span>
                  {quote.payment_terms}
                </p>
              )}
              {quote.notes && (
                <p className="whitespace-pre-line">
                  <span className="text-muted-foreground">Notas: </span>
                  {quote.notes}
                </p>
              )}
            </CardContent>
          </Card>
        )}
      </div>

      <SendQuoteDialog quote={quote} open={sendOpen} onOpenChange={setSendOpen} />
      <ConvertQuoteDialog quote={quote} open={convertOpen} onOpenChange={setConvertOpen} />
    </div>
  );
}

function SendQuoteDialog({
  quote,
  open,
  onOpenChange,
}: {
  quote: Quote;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}) {
  const send = useSendQuote(quote.id);
  const [email, setEmail] = useState(quote.sent_to ?? quote.customer?.email ?? "");
  const [message, setMessage] = useState("");

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Enviar {quote.quote_number} por correo</DialogTitle>
          <DialogDescription>
            El cliente recibirá la cotización en PDF con el logo de tu empresa.
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="send-email">Correo del destinatario</Label>
            <Input
              id="send-email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="cliente@empresa.com"
              required
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="send-message">Mensaje (opcional)</Label>
            <Textarea
              id="send-message"
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              placeholder="Adjunto la propuesta que conversamos..."
              rows={3}
            />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancelar
          </Button>
          <Button
            disabled={send.isPending || !email}
            onClick={() =>
              send.mutate(
                { email: email || undefined, message: message || undefined },
                {
                  onSuccess: (res) => {
                    toast.success(res.message ?? "Cotización enviada");
                    onOpenChange(false);
                    setMessage("");
                  },
                  onError: (e) => toast.error(errMessage(e)),
                },
              )
            }
          >
            {send.isPending && <Loader2 className="size-4 animate-spin" />}
            Enviar
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function ConvertQuoteDialog({
  quote,
  open,
  onOpenChange,
}: {
  quote: Quote;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}) {
  const router = useRouter();
  const convert = useConvertQuote(quote.id);
  const branchesQ = useCompanyBranches(open ? quote.company_id : null);
  const [emissionPointId, setEmissionPointId] = useState<string>("");
  const [issueDate, setIssueDate] = useState(() => new Date().toISOString().slice(0, 10));
  const [paymentMethod, setPaymentMethod] = useState("20");
  const [sendNow, setSendNow] = useState(true);

  const points = useMemo(
    () =>
      (branchesQ.data ?? []).flatMap((b) =>
        (b.emission_points ?? [])
          .filter((p) => p.is_active !== false)
          .map((p) => ({
            id: p.id,
            label: `${b.code}-${p.code}${b.name ? ` · ${b.name}` : ""}`,
          })),
      ),
    [branchesQ.data],
  );

  if (emissionPointId === "" && points.length === 1) {
    setEmissionPointId(String(points[0].id));
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Convertir {quote.quote_number} en factura</DialogTitle>
          <DialogDescription>
            Se creará la factura electrónica con los mismos ítems y la cotización quedará como facturada.
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-4">
          <div className="space-y-2">
            <Label>Punto de emisión</Label>
            <Select value={emissionPointId} onValueChange={setEmissionPointId}>
              <SelectTrigger>
                <SelectValue placeholder={branchesQ.isLoading ? "Cargando..." : "Selecciona punto de emisión"} />
              </SelectTrigger>
              <SelectContent>
                {points.map((p) => (
                  <SelectItem key={p.id} value={String(p.id)}>
                    {p.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {!branchesQ.isLoading && points.length === 0 && (
              <p className="text-xs text-destructive">
                La empresa no tiene puntos de emisión activos. Configúralos en Empresa → Establecimientos.
              </p>
            )}
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="convert-date">Fecha de emisión</Label>
              <Input
                id="convert-date"
                type="date"
                value={issueDate}
                max={new Date().toISOString().slice(0, 10)}
                onChange={(e) => setIssueDate(e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label>Forma de pago</Label>
              <Select value={paymentMethod} onValueChange={setPaymentMethod}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {PAYMENT_METHODS.map((m) => (
                    <SelectItem key={m.code} value={m.code}>
                      {m.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
          <label className="flex cursor-pointer items-start gap-3 rounded-lg border border-border p-3 text-sm">
            <input
              type="checkbox"
              className="mt-0.5 size-4 accent-primary"
              checked={sendNow}
              onChange={(e) => setSendNow(e.target.checked)}
            />
            <span>
              <span className="font-medium">Enviar al SRI ahora</span>
              <span className="block text-muted-foreground">
                Si lo desmarcas, la factura queda en borrador para revisarla antes de emitirla.
              </span>
            </span>
          </label>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancelar
          </Button>
          <Button
            disabled={convert.isPending || !emissionPointId}
            onClick={() =>
              convert.mutate(
                {
                  emission_point_id: Number(emissionPointId),
                  issue_date: issueDate,
                  payment_method: paymentMethod,
                  send: sendNow,
                },
                {
                  onSuccess: (res) => {
                    if (res.data.send_error) {
                      toast.warning(res.message ?? "Factura creada, pero no se pudo enviar al SRI.");
                    } else {
                      toast.success(res.message ?? "Cotización convertida en factura");
                    }
                    onOpenChange(false);
                    router.push(`/documents/${res.data.document.id}`);
                  },
                  onError: (e) => toast.error(errMessage(e)),
                },
              )
            }
          >
            {convert.isPending && <Loader2 className="size-4 animate-spin" />}
            {sendNow ? "Convertir y enviar" : "Convertir"}
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

function Row({ label, value, strong }: { label: string; value: string; strong?: boolean }) {
  return (
    <div className="flex items-center justify-between">
      <span className={strong ? "font-semibold" : "text-muted-foreground"}>{label}</span>
      <span className={`tabular-nums ${strong ? "text-base font-semibold" : ""}`}>{value}</span>
    </div>
  );
}

function Timeline({ label, date, empty }: { label: string; date?: string | null; empty?: string }) {
  return (
    <div className="flex items-start gap-3">
      <span className={`mt-1.5 size-2 shrink-0 rounded-full ${date ? "bg-primary" : "bg-muted-foreground/30"}`} />
      <div className="min-w-0">
        <p className={date ? "font-medium" : "text-muted-foreground"}>{date ? label : (empty ?? label)}</p>
        {date && <p className="text-xs text-muted-foreground">{formatDate(date)}</p>}
      </div>
    </div>
  );
}
