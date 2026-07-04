"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Loader2, Plus, Send, Trash2 } from "lucide-react";
import { toast } from "sonner";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
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
import { EntityCombobox } from "@/components/forms/entity-combobox";
import { useDocumentGate, DocumentGateBanner } from "@/components/panel/document-gate";
import {
  useCompanies,
  useCompanyBranches,
} from "@/lib/api/queries/companies";
import { useCustomers } from "@/lib/api/queries/customers";
import { documentKeys, useDocuments } from "@/lib/api/queries/documents";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { api, ClientApiError, type ApiSuccess } from "@/lib/api/client";
import { formatDate, formatMoney } from "@/lib/format";
import type { Document, Customer } from "@/lib/api/types";

const TRANSPORTER_ID_TYPES = [
  { code: "04", label: "RUC" },
  { code: "05", label: "Cédula" },
];

type GuideItem = {
  code: string;
  description: string;
  quantity: number;
};

type FormState = {
  companyId: number | null;
  branchId: number | null;
  emissionPointId: number | null;
  customerId: number | null;
  issueDate: string;
  // Transporte
  transporterName: string;
  transporterIdType: string;
  transporterId: string;
  plate: string;
  transportStart: string;
  transportEnd: string;
  departureAddress: string;
  // Destinatario
  arrivalAddress: string;
  transferReason: string;
  supportDocId: number | null;
  items: GuideItem[];
};

function emptyItem(): GuideItem {
  return { code: "", description: "", quantity: 1 };
}

function blankState(): FormState {
  const today = new Date().toISOString().slice(0, 10);
  return {
    companyId: null,
    branchId: null,
    emissionPointId: null,
    customerId: null,
    issueDate: today,
    transporterName: "",
    transporterIdType: "04",
    transporterId: "",
    plate: "",
    transportStart: today,
    transportEnd: today,
    departureAddress: "",
    arrivalAddress: "",
    transferReason: "",
    supportDocId: null,
    items: [emptyItem()],
  };
}

/** El paquete SRI exige fechas de transporte en formato dd/mm/yyyy. */
function toSriDate(isoDate: string): string {
  const [y, m, d] = isoDate.slice(0, 10).split("-");
  return `${d}/${m}/${y}`;
}

function errMessage(err: unknown): string {
  if (err instanceof ClientApiError) {
    const payload = err.payload as
      | { message?: string; errors?: Record<string, string[]> }
      | null;
    const firstFieldError = payload?.errors
      ? Object.values(payload.errors).flat()[0]
      : null;
    return firstFieldError ?? payload?.message ?? err.message;
  }
  return err instanceof Error ? err.message : "Error inesperado";
}

export function GuideForm() {
  const router = useRouter();
  const qc = useQueryClient();

  const [state, setState] = useState<FormState>(blankState);
  const [customerSearch, setCustomerSearch] = useState("");
  const [supportDocSearch, setSupportDocSearch] = useState("");
  const [selectedCustomer, setSelectedCustomer] = useState<Customer | null>(
    null,
  );
  const [selectedSupportDoc, setSelectedSupportDoc] = useState<Document | null>(
    null,
  );

  const setField = <K extends keyof FormState>(key: K, value: FormState[K]) => {
    setState((prev) => ({ ...prev, [key]: value }));
  };

  const updateItem = (idx: number, patch: Partial<GuideItem>) => {
    setState((prev) => ({
      ...prev,
      items: prev.items.map((it, i) => (i === idx ? { ...it, ...patch } : it)),
    }));
  };

  const gate = useDocumentGate();
  const companiesQ = useCompanies();
  const branchesQ = useCompanyBranches(state.companyId);
  const selectedBranch = branchesQ.data?.find((b) => b.id === state.branchId);
  const emissionPointOptions = selectedBranch?.emission_points ?? [];

  // Preselecciona empresa/establecimiento/punto de emisión cuando solo hay
  // una opción disponible.
  if (state.companyId === null && companiesQ.data?.length === 1) {
    setField("companyId", companiesQ.data[0].id);
  }
  if (state.companyId && state.branchId === null && branchesQ.data?.length === 1) {
    setField("branchId", branchesQ.data[0].id);
  }
  if (state.branchId && state.emissionPointId === null && emissionPointOptions.length === 1) {
    setField("emissionPointId", emissionPointOptions[0].id);
  }

  const customersQ = useCustomers({
    search: customerSearch || undefined,
    per_page: 20,
  });
  const supportDocsQ = useDocuments({
    search: supportDocSearch || undefined,
    status: "authorized",
    document_type: "01",
    per_page: 20,
  });

  const [pendingAction, setPendingAction] = useState<"draft" | "send" | null>(
    null,
  );

  const submit = useMutation({
    mutationFn: async (sendToSri: boolean) => {
      if (!state.companyId || !state.emissionPointId || !state.customerId) {
        throw new Error("Selecciona empresa, punto de emisión y destinatario.");
      }
      if (!state.transporterName.trim() || !state.transporterId.trim()) {
        throw new Error("Completa los datos del transportista.");
      }
      if (!state.plate.trim()) {
        throw new Error("Indica la placa del vehículo.");
      }
      if (!state.transportStart || !state.transportEnd) {
        throw new Error("Indica las fechas de inicio y fin del transporte.");
      }
      if (state.transportEnd < state.transportStart) {
        throw new Error(
          "La fecha fin de transporte no puede ser anterior al inicio.",
        );
      }
      if (!state.departureAddress.trim() || !state.arrivalAddress.trim()) {
        throw new Error(
          "Indica la dirección de partida y la dirección de llegada.",
        );
      }
      if (!state.transferReason.trim()) {
        throw new Error("Indica el motivo del traslado.");
      }
      const items = state.items.filter(
        (it) => it.description.trim() && it.quantity > 0,
      );
      if (items.length === 0) {
        throw new Error(
          "Agrega al menos un ítem con descripción y cantidad mayor a cero.",
        );
      }

      // Estructura exigida por el paquete SRI (guiaRemision v1.1.0):
      // destinatarios[].{identificacionDestinatario, razonSocialDestinatario,
      // dirDestinatario, motivoTraslado, codDocSustento?, numDocSustento?,
      // fechaEmisionDocSustento?, detalles[].{codigoInterno, descripcion, cantidad}}
      const destinatario: Record<string, unknown> = {
        identificacionDestinatario:
          selectedCustomer?.identification_number ?? "",
        razonSocialDestinatario: selectedCustomer?.name ?? "",
        dirDestinatario: state.arrivalAddress.trim(),
        motivoTraslado: state.transferReason.trim(),
        detalles: items.map((it, idx) => ({
          codigoInterno: it.code.trim() || `GR-${idx + 1}`,
          descripcion: it.description.trim(),
          cantidad: String(it.quantity),
        })),
      };
      if (selectedSupportDoc?.document_number) {
        destinatario.codDocSustento = "01"; // factura
        destinatario.numDocSustento = selectedSupportDoc.document_number;
        if (selectedSupportDoc.issue_date) {
          destinatario.fechaEmisionDocSustento = toSriDate(
            selectedSupportDoc.issue_date,
          );
        }
        if (selectedSupportDoc.authorization_number) {
          destinatario.numAutDocSustento =
            selectedSupportDoc.authorization_number;
        }
      }

      const payload: Record<string, unknown> = {
        company_id: state.companyId,
        customer_id: state.customerId,
        emission_point_id: state.emissionPointId,
        document_type: "06",
        issue_date: state.issueDate,
        subtotal_no_tax: 0,
        subtotal_0: 0,
        subtotal_5: 0,
        subtotal_12: 0,
        subtotal_15: 0,
        total_discount: 0,
        total_tax: 0,
        total: 0,
        items: items.map((it, idx) => ({
          main_code: it.code.trim() || `GR-${idx + 1}`,
          description: it.description.trim(),
          quantity: it.quantity,
          unit_price: 0,
          discount: 0,
          subtotal: 0,
          tax_rate: 0,
          tax_base: 0,
          tax_value: 0,
        })),
        additional_info: {
          dirPartida: state.departureAddress.trim(),
          razonSocialTransportista: state.transporterName.trim(),
          tipoIdTransportista: state.transporterIdType,
          rucTransportista: state.transporterId.trim(),
          fechaIniTransporte: toSriDate(state.transportStart),
          fechaFinTransporte: toSriDate(state.transportEnd),
          placa: state.plate.trim(),
          destinatarios: [destinatario],
        },
      };

      // Guardar (crear borrador)
      const res = await api.post<ApiSuccess<{ document: Document }>>(
        "documents",
        payload,
      );
      const id = res.data.document.id;

      // Enviar al SRI en el mismo paso. Si falla, el borrador queda a salvo.
      let sendError: string | null = null;
      if (sendToSri) {
        try {
          await api.post<ApiSuccess<unknown>>(`documents/${id}/send`);
        } catch (e) {
          sendError = errMessage(e);
        }
      }
      return { id, sendToSri, sendError };
    },
    onSuccess: ({ id, sendToSri, sendError }) => {
      qc.invalidateQueries({ queryKey: documentKeys.all });
      if (!sendToSri) {
        toast.success("Guía de remisión guardada como borrador");
      } else if (sendError) {
        toast.warning(
          `Borrador guardado, pero el envío al SRI falló: ${sendError}`,
        );
      } else {
        toast.success("Enviado al SRI — procesando autorización");
      }
      router.push(`/documents/${id}`);
    },
    onError: (e) => toast.error(errMessage(e)),
    onSettled: () => setPendingAction(null),
  });

  const saveDraft = () => {
    setPendingAction("draft");
    submit.mutate(false);
  };
  const saveAndSend = () => {
    setPendingAction("send");
    submit.mutate(true);
  };

  const customerOptions =
    customersQ.data?.data.map((c) => ({
      value: c.id,
      label: c.name,
      description: `${c.identification_number}${c.email ? " · " + c.email : ""}`,
      meta: c,
    })) ?? [];

  const supportDocOptions =
    supportDocsQ.data?.data.map((d) => ({
      value: d.id,
      label: d.document_number ?? `#${d.id}`,
      description: `${formatDate(d.issue_date)} · ${d.customer?.name ?? "—"} · ${formatMoney(d.total)}`,
      meta: d,
    })) ?? [];

  const totalUnits = state.items.reduce(
    (acc, it) => acc + (it.quantity > 0 ? it.quantity : 0),
    0,
  );

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault();
        saveDraft();
      }}
      className="mx-auto max-w-5xl space-y-5 pb-24"
    >
      <Card>
        <CardHeader>
          <CardTitle>Datos del documento</CardTitle>
          <p className="mt-1 text-sm text-muted-foreground">
            Emisor, punto de emisión y fecha de la guía.
          </p>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div className="space-y-2">
            <Label>Empresa emisora</Label>
            <EntityCombobox
              value={state.companyId}
              onChange={(v) => {
                setField("companyId", typeof v === "number" ? v : null);
                setField("branchId", null);
                setField("emissionPointId", null);
              }}
              options={
                companiesQ.data?.map((c) => ({
                  value: c.id,
                  label: c.legal_name,
                  description: `RUC ${c.ruc}`,
                })) ?? []
              }
              isLoading={companiesQ.isLoading}
              placeholder="Selecciona empresa..."
            />
          </div>

          <div className="space-y-2">
            <Label>Establecimiento</Label>
            <EntityCombobox
              value={state.branchId}
              onChange={(v) => {
                setField("branchId", typeof v === "number" ? v : null);
                setField("emissionPointId", null);
              }}
              options={
                branchesQ.data?.map((b) => ({
                  value: b.id,
                  label: `${b.code} · ${b.name}`,
                })) ?? []
              }
              isLoading={branchesQ.isLoading}
              placeholder={
                state.companyId ? "Selecciona establecimiento..." : "Primero elige empresa"
              }
            />
          </div>

          <div className="space-y-2">
            <Label>Punto de emisión</Label>
            <EntityCombobox
              value={state.emissionPointId}
              onChange={(v) =>
                setField("emissionPointId", typeof v === "number" ? v : null)
              }
              options={emissionPointOptions.map((e) => ({
                value: e.id,
                label: `${e.code}${e.description ? " · " + e.description : ""}`,
              }))}
              isLoading={branchesQ.isLoading}
              placeholder={
                state.branchId ? "Selecciona punto..." : "Primero elige establecimiento"
              }
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="issue_date">Fecha emisión</Label>
            <Input
              id="issue_date"
              type="date"
              value={state.issueDate}
              onChange={(e) => setField("issueDate", e.target.value)}
            />
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Transporte</CardTitle>
          <p className="mt-1 text-sm text-muted-foreground">
            Transportista, vehículo y ruta del traslado.
          </p>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div className="space-y-2 sm:col-span-2 lg:col-span-1">
            <Label htmlFor="transporter_name">Razón social transportista</Label>
            <Input
              id="transporter_name"
              value={state.transporterName}
              onChange={(e) => setField("transporterName", e.target.value)}
              placeholder="Transportes S.A."
              maxLength={300}
              required
            />
          </div>

          <div className="space-y-2">
            <Label>Tipo de identificación</Label>
            <Select
              value={state.transporterIdType}
              onValueChange={(v) => setField("transporterIdType", v)}
            >
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {TRANSPORTER_ID_TYPES.map((t) => (
                  <SelectItem key={t.code} value={t.code}>
                    {t.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="transporter_id">
              {state.transporterIdType === "04"
                ? "RUC transportista"
                : "Cédula transportista"}
            </Label>
            <Input
              id="transporter_id"
              value={state.transporterId}
              onChange={(e) => setField("transporterId", e.target.value)}
              placeholder={
                state.transporterIdType === "04" ? "1790012345001" : "1712345678"
              }
              maxLength={20}
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="plate">Placa</Label>
            <Input
              id="plate"
              value={state.plate}
              onChange={(e) => setField("plate", e.target.value.toUpperCase())}
              placeholder="PBA-1234"
              maxLength={20}
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="transport_start">Fecha inicio transporte</Label>
            <Input
              id="transport_start"
              type="date"
              value={state.transportStart}
              onChange={(e) => setField("transportStart", e.target.value)}
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="transport_end">Fecha fin transporte</Label>
            <Input
              id="transport_end"
              type="date"
              value={state.transportEnd}
              onChange={(e) => setField("transportEnd", e.target.value)}
              required
            />
          </div>

          <div className="space-y-2 sm:col-span-2 lg:col-span-3">
            <Label htmlFor="departure_address">Dirección de partida</Label>
            <Input
              id="departure_address"
              value={state.departureAddress}
              onChange={(e) => setField("departureAddress", e.target.value)}
              placeholder="Av. Amazonas N34-451 y Av. Atahualpa, Quito"
              maxLength={300}
              required
            />
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Destinatario</CardTitle>
          <p className="mt-1 text-sm text-muted-foreground">
            Quién recibe la mercadería, dónde llega y por qué se traslada.
          </p>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2">
          <div className="space-y-2 sm:col-span-2">
            <Label>Destinatario principal</Label>
            <EntityCombobox
              value={state.customerId}
              onChange={(v, opt) => {
                setField("customerId", typeof v === "number" ? v : null);
                const c = (opt?.meta as Customer) ?? null;
                setSelectedCustomer(c);
                if (c?.address) {
                  setState((prev) => ({
                    ...prev,
                    arrivalAddress: prev.arrivalAddress || c.address || "",
                  }));
                }
              }}
              options={customerOptions}
              isLoading={customersQ.isFetching}
              onSearch={setCustomerSearch}
              placeholder="Buscar cliente por nombre o cédula/RUC..."
              searchPlaceholder="Escribe para buscar..."
              emptyMessage="Sin clientes. Agrega uno primero."
            />
            {selectedCustomer && (
              <div className="flex flex-wrap items-center gap-x-4 gap-y-1 rounded-lg bg-muted/40 px-3 py-2 text-xs text-muted-foreground">
                <span className="font-mono">
                  {selectedCustomer.identification_number}
                </span>
                {selectedCustomer.email && <span>{selectedCustomer.email}</span>}
                {selectedCustomer.phone && <span>{selectedCustomer.phone}</span>}
                {selectedCustomer.address && (
                  <span className="truncate">{selectedCustomer.address}</span>
                )}
              </div>
            )}
          </div>

          <div className="space-y-2 sm:col-span-2">
            <Label htmlFor="arrival_address">Dirección de llegada</Label>
            <Input
              id="arrival_address"
              value={state.arrivalAddress}
              onChange={(e) => setField("arrivalAddress", e.target.value)}
              placeholder="Dirección donde se entrega la mercadería"
              maxLength={300}
              required
            />
          </div>

          <div className="space-y-2 sm:col-span-2">
            <Label htmlFor="transfer_reason">Motivo del traslado</Label>
            <Input
              id="transfer_reason"
              value={state.transferReason}
              onChange={(e) => setField("transferReason", e.target.value)}
              placeholder="Venta, traslado entre bodegas, devolución..."
              maxLength={300}
              required
            />
          </div>

          <div className="space-y-2 sm:col-span-2">
            <Label>Documento de sustento (opcional)</Label>
            <EntityCombobox
              value={state.supportDocId}
              onChange={(v, opt) => {
                setField("supportDocId", typeof v === "number" ? v : null);
                setSelectedSupportDoc((opt?.meta as Document) ?? null);
              }}
              options={supportDocOptions}
              isLoading={supportDocsQ.isFetching}
              onSearch={setSupportDocSearch}
              placeholder="Buscar factura autorizada por número o cliente..."
              searchPlaceholder="Buscar factura..."
              emptyMessage="No hay facturas autorizadas para referenciar."
            />
            <p className="text-xs text-muted-foreground">
              Factura autorizada que respalda el traslado. Se tomará su número y
              fecha de emisión.
            </p>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-start justify-between gap-3">
          <div>
            <CardTitle>Ítems a trasladar</CardTitle>
            <p className="mt-1 text-sm text-muted-foreground">
              Mercadería que viaja en esta guía (sin precios).
            </p>
          </div>
          <Button
            type="button"
            variant="outline"
            size="sm"
            className="shrink-0"
            onClick={() =>
              setState((prev) => ({
                ...prev,
                items: [...prev.items, emptyItem()],
              }))
            }
          >
            <Plus className="size-4" /> Agregar línea
          </Button>
        </CardHeader>
        <CardContent className="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className="w-[160px]">Código</TableHead>
                <TableHead>Descripción</TableHead>
                <TableHead className="w-[110px]">Cantidad</TableHead>
                <TableHead className="w-[40px]"></TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {state.items.map((item, idx) => (
                <TableRow key={idx}>
                  <TableCell>
                    <Input
                      value={item.code}
                      onChange={(e) => updateItem(idx, { code: e.target.value })}
                      placeholder={`GR-${idx + 1}`}
                      maxLength={50}
                    />
                  </TableCell>
                  <TableCell>
                    <Input
                      value={item.description}
                      onChange={(e) =>
                        updateItem(idx, { description: e.target.value })
                      }
                      placeholder="Descripción de la mercadería"
                      maxLength={300}
                    />
                  </TableCell>
                  <TableCell>
                    <Input
                      type="number"
                      step="0.01"
                      min="0"
                      value={item.quantity}
                      onChange={(e) =>
                        updateItem(idx, {
                          quantity: Number(e.target.value) || 0,
                        })
                      }
                    />
                  </TableCell>
                  <TableCell>
                    <Button
                      type="button"
                      size="icon"
                      variant="ghost"
                      onClick={() =>
                        setState((prev) => ({
                          ...prev,
                          items:
                            prev.items.length > 1
                              ? prev.items.filter((_, i) => i !== idx)
                              : prev.items,
                        }))
                      }
                      disabled={state.items.length === 1}
                    >
                      <Trash2 className="size-4" />
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      {/* Barra de acción fija */}
      <div className="fixed inset-x-0 bottom-0 z-20 border-t border-border bg-background/85 backdrop-blur-md lg:left-64">
        <DocumentGateBanner reasons={gate.reasons} />
        <div className="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-3 lg:px-6">
          <div className="flex items-baseline gap-2">
            <span className="text-sm text-muted-foreground">
              {state.items.length} {state.items.length === 1 ? "línea" : "líneas"}
            </span>
            <span className="text-lg font-semibold tabular-nums">
              {totalUnits} unid.
            </span>
          </div>
          <div className="flex flex-wrap justify-end gap-2">
            <Button
              type="button"
              variant="ghost"
              onClick={() => router.back()}
            >
              Cancelar
            </Button>
            <Button
              type="submit"
              variant="outline"
              disabled={submit.isPending || gate.blockCreate}
            >
              {pendingAction === "draft" && (
                <Loader2 className="size-4 animate-spin" />
              )}
              Guardar borrador
            </Button>
            <Button
              type="button"
              disabled={submit.isPending || gate.blockSend}
              onClick={saveAndSend}
            >
              {pendingAction === "send" ? (
                <>
                  <Loader2 className="size-4 animate-spin" />
                  Enviando al SRI…
                </>
              ) : (
                <>
                  <Send className="size-4" />
                  Guardar y enviar al SRI
                </>
              )}
            </Button>
          </div>
        </div>
      </div>
    </form>
  );
}
