"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import {
  Loader2,
  Send,
  Ban,
  FileText,
  FileCode,
  Mail,
  RefreshCw,
  Pencil,
  Receipt,
  Copy,
  Check,
  UserRound,
  ShieldCheck,
  ListPlus,
  Building2,
  Truck,
  CreditCard,
  AlertTriangle,
} from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import {
  useDocument,
  useSendDocument,
  useVoidDocument,
  useResendEmail,
  useCheckStatus,
  useDeleteDocument,
  downloadDocumentRide,
  downloadDocumentXml,
} from "@/lib/api/queries/documents";
import { ClientApiError } from "@/lib/api/client";
import { documentStatusMeta } from "@/lib/status";
import { companyInitials, formatDate, formatMoney } from "@/lib/format";
import { DeleteConfirmButton } from "@/components/forms/delete-confirm-button";
import { PaymentsCard } from "./payments-card";

function errMessage(err: unknown): string {
  if (err instanceof ClientApiError) return err.message;
  return err instanceof Error ? err.message : "Error inesperado";
}

const PAYMENT_METHOD_LABELS: Record<string, string> = {
  "01": "Sin utilización del sistema financiero",
  "15": "Compensación de deudas",
  "16": "Tarjeta de débito",
  "17": "Dinero electrónico",
  "18": "Tarjeta prepago",
  "19": "Tarjeta de crédito",
  "20": "Otros con utilización del sistema financiero",
  "21": "Endoso de títulos",
};

type SriMessage = {
  identificador?: string;
  mensaje?: string;
  informacionAdicional?: string;
  tipo?: string;
};

function normalizeSriMessages(raw: unknown): SriMessage[] {
  if (!raw) return [];
  const list = Array.isArray(raw) ? raw : [raw];
  return list.filter((m): m is SriMessage => typeof m === "object" && m !== null);
}

export function DocumentDetail({ id }: { id: number }) {
  const router = useRouter();
  const { data, isLoading, error } = useDocument(id);
  const send = useSendDocument(id);
  const voidDoc = useVoidDocument(id);
  const resendEmail = useResendEmail(id);
  const checkStatus = useCheckStatus(id);
  const deleteDoc = useDeleteDocument();

  const [voidReason, setVoidReason] = useState("");
  const [voidOpen, setVoidOpen] = useState(false);
  const [emailOpen, setEmailOpen] = useState(false);
  const [emailValue, setEmailValue] = useState("");
  const [copied, setCopied] = useState(false);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-6 text-sm text-destructive">
        Error cargando documento: {errMessage(error)}
      </div>
    );
  }

  const doc = data?.data.document;
  if (!doc) return null;

  const meta = documentStatusMeta(doc.status);
  const canSend = doc.status === "draft";
  const canRetry = doc.status === "failed" || doc.status === "rejected";
  const canVoid =
    doc.status === "authorized" || doc.status === "draft" || doc.status === "sent";
  const canDelete = doc.status === "draft";
  const isAuthorized = doc.status === "authorized";
  const canDownloadXml = doc.has_xml;
  const isGuide = String(doc.document_type) === "06";
  const isRetention = String(doc.document_type) === "07";
  const info = doc.additional_info ?? null;
  // Solo valores escalares: la guía guarda aquí estructuras anidadas
  // (destinatarios/transporte) que se muestran en su propia tarjeta.
  const infoEntries = info
    ? Object.entries(info).filter(
        (e): e is [string, string | number | boolean] =>
          ["string", "number", "boolean"].includes(typeof e[1]) &&
          e[1] !== "" &&
          !(isGuide && GUIDE_TRANSPORT_KEYS.has(e[0])),
      )
    : [];
  const sriMessages = normalizeSriMessages(doc.sri_messages);
  // Detalle completo de errores (fatal + mensajes del SRI), armado por el API.
  const errorDetails: string[] = Array.isArray(
    (doc as { error_details?: unknown }).error_details,
  )
    ? ((doc as { error_details?: string[] }).error_details as string[])
    : [];
  const payments = doc.payment_methods ?? [];
  const withholdings = doc.withholding_details ?? [];

  const copyAccessKey = async () => {
    if (!doc.access_key) return;
    try {
      await navigator.clipboard.writeText(doc.access_key);
      setCopied(true);
      setTimeout(() => setCopied(false), 1500);
    } catch {
      toast.error("No se pudo copiar.");
    }
  };

  return (
    <div className="mx-auto max-w-6xl space-y-5 p-4 pb-12 lg:p-6">
      {/* Hero */}
      <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div className="flex items-start gap-3.5">
          <span className="grid size-12 shrink-0 place-items-center rounded-xl bg-accent text-accent-foreground">
            <Receipt className="size-6" />
          </span>
          <div>
            <div className="flex flex-wrap items-center gap-2.5">
              <h1 className="text-xl font-semibold tracking-tight">
                {doc.document_type_label ?? doc.document_type ?? "Documento"}
              </h1>
              <Badge variant="outline" className={meta.className}>
                {doc.status_label ?? meta.label}
              </Badge>
            </div>
            <p className="mt-0.5 font-mono text-sm text-muted-foreground">
              {doc.document_number ?? `#${doc.id}`}
            </p>
            <p className="mt-0.5 text-sm text-muted-foreground">
              Emitido {formatDate(doc.issue_date)}
              {doc.environment_label ? ` · ${doc.environment_label}` : ""}
            </p>
          </div>
        </div>

        {/* Toolbar */}
        <div className="flex flex-wrap items-center gap-2">
          {canSend && (
            <Button
              disabled={send.isPending}
              onClick={() =>
                send.mutate(undefined, {
                  onSuccess: () => toast.success("Documento enviado al SRI"),
                  onError: (e) => toast.error(errMessage(e)),
                })
              }
            >
              {send.isPending ? (
                <Loader2 className="size-4 animate-spin" />
              ) : (
                <Send className="size-4" />
              )}
              Enviar al SRI
            </Button>
          )}
          {canRetry && (
            <Button
              disabled={send.isPending}
              onClick={() =>
                send.mutate(undefined, {
                  onSuccess: () =>
                    toast.success("Reenviado al SRI. Esperá la autorización."),
                  onError: (e) => toast.error(errMessage(e)),
                })
              }
            >
              {send.isPending ? (
                <Loader2 className="size-4 animate-spin" />
              ) : (
                <RefreshCw className="size-4" />
              )}
              Reintentar envío
            </Button>
          )}

          <Button
            variant="outline"
            title={
              isAuthorized
                ? "Descargar RIDE (PDF)"
                : "Vista previa del PDF con marca de agua de borrador"
            }
            onClick={async () => {
              try {
                await downloadDocumentRide(doc.id);
              } catch (e) {
                toast.error(errMessage(e));
              }
            }}
          >
            <FileText className="size-4" />
            {isAuthorized ? "RIDE" : "Vista previa PDF"}
          </Button>

          <Button
            variant="outline"
            disabled={!canDownloadXml}
            onClick={async () => {
              try {
                await downloadDocumentXml(doc.id, `${doc.access_key}.xml`);
              } catch (e) {
                toast.error(errMessage(e));
              }
            }}
          >
            <FileCode className="size-4" /> XML
          </Button>

          <Dialog open={emailOpen} onOpenChange={setEmailOpen}>
            <DialogTrigger asChild>
              <Button
                variant="outline"
                disabled={doc.status !== "authorized"}
                onClick={() => setEmailValue(doc.customer?.email ?? "")}
              >
                <Mail className="size-4" /> Email
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Reenviar documento por email</DialogTitle>
                <DialogDescription>
                  Se enviará el RIDE y XML al correo indicado.
                </DialogDescription>
              </DialogHeader>
              <div className="space-y-2">
                <Label htmlFor="resend-email">Correo electrónico</Label>
                <Input
                  id="resend-email"
                  type="email"
                  value={emailValue}
                  onChange={(e) => setEmailValue(e.target.value)}
                />
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={() => setEmailOpen(false)}>
                  Cancelar
                </Button>
                <Button
                  disabled={resendEmail.isPending}
                  onClick={() =>
                    resendEmail.mutate(emailValue || undefined, {
                      onSuccess: () => {
                        toast.success("Documento enviado");
                        setEmailOpen(false);
                      },
                      onError: (e) => toast.error(errMessage(e)),
                    })
                  }
                >
                  {resendEmail.isPending && (
                    <Loader2 className="size-4 animate-spin" />
                  )}
                  Enviar
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>

          <Button
            variant="outline"
            disabled={checkStatus.isPending}
            onClick={() =>
              checkStatus.mutate(undefined, {
                onSuccess: () => toast.success("Estado actualizado"),
                onError: (e) => toast.error(errMessage(e)),
              })
            }
          >
            {checkStatus.isPending ? (
              <Loader2 className="size-4 animate-spin" />
            ) : (
              <RefreshCw className="size-4" />
            )}
            Estado
          </Button>

          {canDelete && (
            <Button variant="outline" asChild>
              <Link href={`/documents/${doc.id}/edit`}>
                <Pencil className="size-4" /> Editar
              </Link>
            </Button>
          )}

          {canDelete && (
            <DeleteConfirmButton
              onConfirm={async () => {
                await deleteDoc.mutateAsync(doc.id);
                router.push("/documents");
              }}
              isPending={deleteDoc.isPending}
              title="¿Eliminar borrador?"
              description="Esta acción solo se puede ejecutar en documentos en borrador."
              successMessage="Borrador eliminado"
              triggerVariant="outline"
              triggerSize="default"
              triggerLabel="Eliminar"
            />
          )}

          <Dialog open={voidOpen} onOpenChange={setVoidOpen}>
            <DialogTrigger asChild>
              <Button
                variant="outline"
                disabled={!canVoid}
                className="border-destructive/30 text-destructive hover:bg-destructive/5 hover:text-destructive"
              >
                <Ban className="size-4" /> Anular
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Anular documento</DialogTitle>
                <DialogDescription>
                  Esta acción no se puede deshacer. Indica el motivo.
                </DialogDescription>
              </DialogHeader>
              <div className="space-y-2">
                <Label htmlFor="void-reason">Motivo de anulación</Label>
                <Input
                  id="void-reason"
                  value={voidReason}
                  onChange={(e) => setVoidReason(e.target.value)}
                  placeholder="Error en datos, cliente solicitó cancelación..."
                />
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={() => setVoidOpen(false)}>
                  Cancelar
                </Button>
                <Button
                  variant="destructive"
                  disabled={!voidReason || voidDoc.isPending}
                  onClick={() =>
                    voidDoc.mutate(voidReason, {
                      onSuccess: () => {
                        toast.success("Documento anulado");
                        setVoidOpen(false);
                        setVoidReason("");
                      },
                      onError: (e) => toast.error(errMessage(e)),
                    })
                  }
                >
                  {voidDoc.isPending && (
                    <Loader2 className="size-4 animate-spin" />
                  )}
                  Confirmar anulación
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      {/* Contenido */}
      <div className="grid gap-5 lg:grid-cols-3">
        <div className="space-y-5 lg:col-span-2">
          {/* Detalle de ítems (o retenciones) + totales */}
          <Card>
            <CardHeader>
              <CardTitle>Detalle</CardTitle>
            </CardHeader>
            <CardContent>
              {isRetention ? (
                <div className="overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow className="hover:bg-transparent">
                        <TableHead>Sustento</TableHead>
                        <TableHead>Impuesto</TableHead>
                        <TableHead>Código</TableHead>
                        <TableHead className="text-right">Base imponible</TableHead>
                        <TableHead className="text-right">%</TableHead>
                        <TableHead className="text-right">Retenido</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {withholdings.map((w) => (
                        <TableRow key={w.id}>
                          <TableCell className="font-mono text-xs">
                            {w.support_doc_number}
                            {w.support_doc_date
                              ? ` · ${formatDate(w.support_doc_date)}`
                              : ""}
                          </TableCell>
                          <TableCell className="uppercase">{w.tax_type}</TableCell>
                          <TableCell className="font-mono text-xs">
                            {w.retention_code}
                          </TableCell>
                          <TableCell className="text-right tabular-nums">
                            {formatMoney(w.tax_base)}
                          </TableCell>
                          <TableCell className="text-right tabular-nums">
                            {w.retention_rate}%
                          </TableCell>
                          <TableCell className="text-right font-medium tabular-nums">
                            {formatMoney(w.retained_value)}
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              ) : (
                <div className="overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow className="hover:bg-transparent">
                        <TableHead>Código</TableHead>
                        <TableHead>Descripción</TableHead>
                        <TableHead className="text-right">Cant.</TableHead>
                        <TableHead className="text-right">P. unit.</TableHead>
                        <TableHead className="text-right">Desc.</TableHead>
                        {!isGuide && (
                          <TableHead className="text-right">IVA</TableHead>
                        )}
                        <TableHead className="text-right">Subtotal</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {(doc.items ?? []).map((it) => (
                        <TableRow key={it.id}>
                          <TableCell className="font-mono text-xs">
                            {it.main_code}
                          </TableCell>
                          <TableCell className="font-medium">
                            {it.description}
                          </TableCell>
                          <TableCell className="text-right tabular-nums">
                            {it.quantity}
                          </TableCell>
                          <TableCell className="text-right tabular-nums">
                            {formatMoney(it.unit_price)}
                          </TableCell>
                          <TableCell className="text-right tabular-nums">
                            {formatMoney(it.discount)}
                          </TableCell>
                          {!isGuide && (
                            <TableCell className="text-right tabular-nums">
                              {it.tax_rate}%
                            </TableCell>
                          )}
                          <TableCell className="text-right font-medium tabular-nums">
                            {formatMoney(it.subtotal)}
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              )}

              {!isGuide && (
                <div className="ml-auto mt-5 max-w-xs space-y-1.5 text-sm">
                  {isRetention ? (
                    <div className="flex items-baseline justify-between border-t border-border pt-3">
                      <span className="font-medium">Total retenido</span>
                      <span className="text-2xl font-semibold tabular-nums">
                        {formatMoney(doc.total)}
                      </span>
                    </div>
                  ) : (
                    <>
                      <Row label="Subtotal sin impuesto" value={doc.subtotal_no_tax} />
                      <Row label="Subtotal 0%" value={doc.subtotal_0} />
                      {!!doc.subtotal_5 && (
                        <Row label="Subtotal 5%" value={doc.subtotal_5} />
                      )}
                      {!!doc.subtotal_12 && (
                        <Row label="Subtotal 12%" value={doc.subtotal_12} />
                      )}
                      {!!doc.subtotal_15 && (
                        <Row label="Subtotal 15%" value={doc.subtotal_15} />
                      )}
                      <Row label="Descuento" value={doc.total_discount} />
                      <Row label="IVA" value={doc.total_tax} />
                      <Row label="Propina" value={doc.tip} />
                      <div className="mt-3 flex items-baseline justify-between border-t border-border pt-3">
                        <span className="font-medium">Total</span>
                        <span className="text-2xl font-semibold tabular-nums">
                          {formatMoney(doc.total)}
                        </span>
                      </div>
                    </>
                  )}
                </div>
              )}
            </CardContent>
          </Card>

          {/* Guía de remisión: transporte y destinatarios */}
          {isGuide && <GuideTransportCard info={info} />}

          {/* Detalle del error (incluye errores fatales de firma/envío) */}
          {errorDetails.length > 0 && (
            <Card className="border-destructive/30">
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-sm">
                  <AlertTriangle className="size-4 text-destructive" />
                  Detalle del error
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                {errorDetails.map((m, i) => (
                  <div
                    key={i}
                    className="rounded-lg border border-destructive/20 bg-destructive/5 p-3 text-sm"
                  >
                    {m}
                  </div>
                ))}
              </CardContent>
            </Card>
          )}

          {/* Mensajes del SRI (rechazos / advertencias) — fallback */}
          {errorDetails.length === 0 && sriMessages.length > 0 && (
            <Card className="border-destructive/30">
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-sm">
                  <AlertTriangle className="size-4 text-destructive" />
                  Respuesta del SRI
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                {sriMessages.map((m, i) => (
                  <div key={i} className="rounded-lg border border-destructive/20 bg-destructive/5 p-3 text-sm">
                    <p className="font-medium">
                      {m.identificador ? `${m.identificador} · ` : ""}
                      {m.mensaje ?? "Mensaje del SRI"}
                    </p>
                    {m.informacionAdicional && (
                      <p className="mt-1 text-xs text-muted-foreground">
                        {m.informacionAdicional}
                      </p>
                    )}
                  </div>
                ))}
              </CardContent>
            </Card>
          )}
        </div>

        {/* Panel lateral */}
        <div className="space-y-5">
          {doc.company && (
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-sm">
                  <Building2 className="size-4 text-primary" />
                  Emisor
                </CardTitle>
              </CardHeader>
              <CardContent className="flex items-start gap-3">
                {doc.company.logo_url ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img
                    src={doc.company.logo_url}
                    alt="Logo"
                    className="size-11 shrink-0 rounded-lg border border-border bg-white object-contain p-0.5"
                  />
                ) : (
                  <span className="grid size-11 shrink-0 place-items-center rounded-lg bg-primary text-sm font-semibold text-primary-foreground">
                    {companyInitials(
                      doc.company.trade_name || doc.company.business_name,
                    )}
                  </span>
                )}
                <div className="min-w-0 space-y-0.5">
                  <p className="font-medium">{doc.company.business_name}</p>
                  <p className="font-mono text-xs text-muted-foreground">
                    RUC {doc.company.ruc}
                  </p>
                  {doc.company.address && (
                    <p className="text-sm text-muted-foreground">
                      {doc.company.address}
                    </p>
                  )}
                </div>
              </CardContent>
            </Card>
          )}

          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-sm">
                <UserRound className="size-4 text-primary" />
                {isRetention
                  ? "Sujeto retenido"
                  : isGuide
                    ? "Destinatario"
                    : String(doc.document_type) === "03"
                      ? "Proveedor"
                      : "Cliente"}
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-1">
              <p className="font-medium">{doc.customer?.name ?? "—"}</p>
              <p className="font-mono text-xs text-muted-foreground">
                {doc.customer?.identification_number ?? "—"}
              </p>
              {doc.customer?.email && (
                <p className="text-sm text-muted-foreground">
                  {doc.customer.email}
                </p>
              )}
              {doc.customer?.address && (
                <p className="text-sm text-muted-foreground">
                  {doc.customer.address}
                </p>
              )}
            </CardContent>
          </Card>

          {payments.length > 0 && (
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-sm">
                  <CreditCard className="size-4 text-primary" />
                  Forma de pago
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-2">
                {payments.map((pm, i) => (
                  <div key={i} className="flex items-center justify-between gap-2 text-sm">
                    <span className="text-muted-foreground">
                      {PAYMENT_METHOD_LABELS[pm.code] ?? `Código ${pm.code}`}
                      {pm.term ? ` · ${pm.term} días` : ""}
                    </span>
                    <span className="font-medium tabular-nums">
                      {formatMoney(pm.amount ?? doc.total)}
                    </span>
                  </div>
                ))}
              </CardContent>
            </Card>
          )}

          {doc.status === "authorized" &&
            ["01", "05"].includes(String(doc.document_type)) && (
              <PaymentsCard documentId={id} />
            )}

          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-sm">
                <ShieldCheck className="size-4 text-primary" />
                Autorización SRI
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-2.5">
              {doc.access_key ? (
                <div>
                  <p className="mb-1 text-xs text-muted-foreground">
                    Clave de acceso
                  </p>
                  <button
                    type="button"
                    onClick={copyAccessKey}
                    title="Copiar clave de acceso"
                    className="group flex w-full items-start gap-2 rounded-lg border border-border bg-muted/40 p-2.5 text-left transition hover:border-primary/40"
                  >
                    <span className="min-w-0 flex-1 break-all font-mono text-[11px] leading-relaxed">
                      {doc.access_key}
                    </span>
                    {copied ? (
                      <Check className="mt-0.5 size-3.5 shrink-0 text-success" />
                    ) : (
                      <Copy className="mt-0.5 size-3.5 shrink-0 text-muted-foreground group-hover:text-foreground" />
                    )}
                  </button>
                </div>
              ) : (
                <p className="text-sm text-muted-foreground">
                  Sin clave de acceso todavía.
                </p>
              )}
              {doc.authorization_number && (
                <div>
                  <p className="text-xs text-muted-foreground">Autorización</p>
                  <p className="break-all font-mono text-[11px]">
                    {doc.authorization_number}
                  </p>
                </div>
              )}
              {doc.authorization_date && (
                <p className="text-xs text-muted-foreground">
                  Autorizado {formatDate(doc.authorization_date)}
                </p>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-sm">
                <Mail className="size-4 text-primary" />
                Envío por correo
              </CardTitle>
            </CardHeader>
            <CardContent>
              {doc.email_sent ? (
                <div className="space-y-2">
                  <Badge
                    variant="outline"
                    className="border-transparent bg-success/10 text-success"
                  >
                    <Check className="size-3" />
                    Enviado
                  </Badge>
                  <div className="text-sm">
                    <p className="text-xs text-muted-foreground">Destinatario</p>
                    <p className="break-all font-medium">
                      {doc.email_sent_to ?? doc.customer?.email ?? "—"}
                    </p>
                  </div>
                  {doc.email_sent_at && (
                    <p className="text-xs text-muted-foreground">
                      Enviado {formatDate(doc.email_sent_at)}
                    </p>
                  )}
                </div>
              ) : (
                <div className="space-y-1.5">
                  <Badge
                    variant="outline"
                    className="border-transparent bg-muted text-muted-foreground"
                  >
                    No enviado
                  </Badge>
                  <p className="text-xs text-muted-foreground">
                    {doc.status === "authorized"
                      ? "Usa el botón Email para enviarlo al cliente."
                      : "Se enviará al autorizarse el documento."}
                  </p>
                </div>
              )}
            </CardContent>
          </Card>

          {infoEntries.length > 0 && (
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-sm">
                  <ListPlus className="size-4 text-primary" />
                  Información adicional
                </CardTitle>
              </CardHeader>
              <CardContent>
                <dl className="space-y-2">
                  {infoEntries.map(([k, v]) => (
                    <div key={k} className="text-sm">
                      <dt className="text-xs text-muted-foreground">{k}</dt>
                      <dd className="font-medium">{v}</dd>
                    </div>
                  ))}
                </dl>
              </CardContent>
            </Card>
          )}
        </div>
      </div>
    </div>
  );
}

function Row({ label, value }: { label: string; value?: number }) {
  if (!value) return null;
  return (
    <div className="flex items-center justify-between text-muted-foreground">
      <span>{label}</span>
      <span className="tabular-nums">{formatMoney(value)}</span>
    </div>
  );
}

const GUIDE_TRANSPORT_KEYS = new Set([
  "dirPartida",
  "razonSocialTransportista",
  "tipoIdTransportista",
  "rucTransportista",
  "fechaIniTransporte",
  "fechaFinTransporte",
  "placa",
  "destinatarios",
]);

type GuideRecipient = {
  razonSocialDestinatario?: string;
  identificacionDestinatario?: string;
  dirDestinatario?: string;
  motivoTraslado?: string;
  numDocSustento?: string;
  detalles?: Array<{
    codigoInterno?: string;
    descripcion?: string;
    cantidad?: string;
  }>;
};

function GuideTransportCard({ info }: { info: Record<string, unknown> | null }) {
  if (!info) return null;
  const str = (k: string) => (typeof info[k] === "string" ? (info[k] as string) : "—");
  const recipients = Array.isArray(info.destinatarios)
    ? (info.destinatarios as GuideRecipient[])
    : [];

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2 text-sm">
          <Truck className="size-4 text-primary" />
          Transporte y destinatarios
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <dl className="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
          <div>
            <dt className="text-xs text-muted-foreground">Transportista</dt>
            <dd className="font-medium">{str("razonSocialTransportista")}</dd>
            <dd className="font-mono text-xs text-muted-foreground">
              {str("rucTransportista")}
            </dd>
          </div>
          <div>
            <dt className="text-xs text-muted-foreground">Placa</dt>
            <dd className="font-mono font-medium">{str("placa")}</dd>
          </div>
          <div>
            <dt className="text-xs text-muted-foreground">Dirección de partida</dt>
            <dd>{str("dirPartida")}</dd>
          </div>
          <div>
            <dt className="text-xs text-muted-foreground">Transporte</dt>
            <dd>
              {str("fechaIniTransporte")} — {str("fechaFinTransporte")}
            </dd>
          </div>
        </dl>

        {recipients.map((r, i) => (
          <div key={i} className="rounded-lg border border-border p-3">
            <p className="font-medium">{r.razonSocialDestinatario ?? "—"}</p>
            <p className="font-mono text-xs text-muted-foreground">
              {r.identificacionDestinatario ?? "—"}
            </p>
            <p className="mt-1 text-sm text-muted-foreground">
              {r.dirDestinatario ?? "—"} · {r.motivoTraslado ?? "—"}
            </p>
            {(r.detalles ?? []).length > 0 && (
              <ul className="mt-2 space-y-1 border-t border-border pt-2 text-sm">
                {(r.detalles ?? []).map((d, j) => (
                  <li key={j} className="flex justify-between gap-3">
                    <span>
                      <span className="font-mono text-xs text-muted-foreground">
                        {d.codigoInterno}
                      </span>{" "}
                      {d.descripcion}
                    </span>
                    <span className="tabular-nums text-muted-foreground">
                      × {d.cantidad}
                    </span>
                  </li>
                ))}
              </ul>
            )}
          </div>
        ))}
      </CardContent>
    </Card>
  );
}
