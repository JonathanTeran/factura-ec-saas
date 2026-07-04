"use client";

import { useMemo, useState } from "react";
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
import { useCompanies, useCompanyBranches } from "@/lib/api/queries/companies";
import { useCustomers } from "@/lib/api/queries/customers";
import {
  documentKeys,
  useRetentionCodes,
  type RetentionCode,
} from "@/lib/api/queries/documents";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { api, ClientApiError, type ApiSuccess } from "@/lib/api/client";
import { formatMoney } from "@/lib/format";
import type { Document, Customer } from "@/lib/api/types";

const SUPPORT_DOC_TYPES = [
  { code: "01", label: "01 · Factura" },
  { code: "03", label: "03 · Liquidación de compra" },
];

type TaxType = "renta" | "iva";

type RetentionRow = {
  tax_type: TaxType;
  retention_code: string;
  tax_base: number;
  retention_rate: number;
  retained_value: number;
};

function emptyRow(taxType: TaxType = "renta"): RetentionRow {
  return {
    tax_type: taxType,
    retention_code: "",
    tax_base: 0,
    retention_rate: 0,
    retained_value: 0,
  };
}

function round2(n: number): number {
  return Math.round((n + Number.EPSILON) * 100) / 100;
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

export function RetentionForm() {
  const router = useRouter();
  const qc = useQueryClient();

  const [companyId, setCompanyId] = useState<number | null>(null);
  const [branchId, setBranchId] = useState<number | null>(null);
  const [emissionPointId, setEmissionPointId] = useState<number | null>(null);
  const [customerId, setCustomerId] = useState<number | null>(null);
  const [selectedCustomer, setSelectedCustomer] = useState<Customer | null>(null);
  const [customerSearch, setCustomerSearch] = useState("");
  const [issueDate, setIssueDate] = useState(
    new Date().toISOString().slice(0, 10),
  );

  // Documento sustento (compartido por todas las líneas)
  const [supportDocCode, setSupportDocCode] = useState("01");
  const [supportDocNumber, setSupportDocNumber] = useState("");
  const [supportDocDate, setSupportDocDate] = useState(
    new Date().toISOString().slice(0, 10),
  );
  const [supportDocTotal, setSupportDocTotal] = useState(0);

  const [rows, setRows] = useState<RetentionRow[]>([emptyRow()]);

  const gate = useDocumentGate();
  const companiesQ = useCompanies();
  const branchesQ = useCompanyBranches(companyId);
  const selectedBranch = branchesQ.data?.find((b) => b.id === branchId);
  const emissionPointOptions = selectedBranch?.emission_points ?? [];

  // Preselecciona empresa/establecimiento/punto de emisión cuando solo hay
  // una opción disponible.
  if (companyId === null && companiesQ.data?.length === 1) {
    setCompanyId(companiesQ.data[0].id);
  }
  if (companyId && branchId === null && branchesQ.data?.length === 1) {
    setBranchId(branchesQ.data[0].id);
  }
  if (branchId && emissionPointId === null && emissionPointOptions.length === 1) {
    setEmissionPointId(emissionPointOptions[0].id);
  }

  const customersQ = useCustomers({
    search: customerSearch || undefined,
    per_page: 20,
  });
  const codesQ = useRetentionCodes();

  const catalog = codesQ.data?.data.retention_codes;
  const codesFor = (taxType: TaxType): RetentionCode[] =>
    catalog?.[taxType] ?? [];

  // Periodo fiscal: el SRI lo deriva del mes de la fecha de emisión (mm/yyyy).
  const fiscalPeriod = useMemo(() => {
    const [y, m] = issueDate.split("-");
    return y && m ? `${y}-${m}` : "";
  }, [issueDate]);

  const totalRetenido = useMemo(
    () => round2(rows.reduce((sum, r) => sum + (r.retained_value || 0), 0)),
    [rows],
  );

  const updateRow = (idx: number, patch: Partial<RetentionRow>) => {
    setRows((prev) =>
      prev.map((r, i) => {
        if (i !== idx) return r;
        const next = { ...r, ...patch };
        // Recalcula el valor retenido salvo edición manual directa.
        if (!("retained_value" in patch)) {
          next.retained_value = round2(
            (next.tax_base * next.retention_rate) / 100,
          );
        }
        return next;
      }),
    );
  };

  const onSelectCode = (idx: number, taxType: TaxType, code: string) => {
    const match = codesFor(taxType).find((c) => c.code === code);
    updateRow(idx, {
      retention_code: code,
      ...(match?.percentage != null ? { retention_rate: match.percentage } : {}),
    });
  };

  const [pendingAction, setPendingAction] = useState<"draft" | "send" | null>(
    null,
  );

  const submit = useMutation({
    mutationFn: async (sendToSri: boolean) => {
      if (!companyId || !emissionPointId || !customerId) {
        throw new Error("Selecciona empresa, punto de emisión y proveedor.");
      }
      if (!supportDocNumber.trim()) {
        throw new Error("Ingresa el número del documento sustento.");
      }
      if (!/^\d{3}-\d{3}-\d{9}$/.test(supportDocNumber.trim())) {
        throw new Error(
          "El número de sustento debe tener el formato 001-001-000000123.",
        );
      }
      if (rows.some((r) => !r.retention_code)) {
        throw new Error("Selecciona el código de retención en cada línea.");
      }
      if (rows.some((r) => r.tax_base <= 0)) {
        throw new Error("La base imponible debe ser mayor a 0 en cada línea.");
      }
      const payload = {
        company_id: companyId,
        customer_id: customerId,
        emission_point_id: emissionPointId,
        document_type: "07",
        issue_date: issueDate,
        subtotal_no_tax: 0,
        subtotal_0: 0,
        subtotal_5: 0,
        subtotal_12: 0,
        subtotal_15: 0,
        total_discount: 0,
        total_tax: 0,
        total: totalRetenido,
        withholding_details: rows.map((r) => ({
          support_doc_code: supportDocCode,
          support_doc_number: supportDocNumber.trim(),
          support_doc_date: supportDocDate,
          support_doc_total: supportDocTotal || 0,
          support_reason_code: "01",
          tax_type: r.tax_type,
          retention_code: r.retention_code,
          tax_base: r.tax_base,
          retention_rate: r.retention_rate,
          retained_value: r.retained_value,
        })),
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
        toast.success("Retención guardada como borrador");
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
            Agente de retención, punto de emisión y fecha del comprobante.
          </p>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div className="space-y-2">
            <Label>Empresa emisora</Label>
            <EntityCombobox
              value={companyId}
              onChange={(v) => {
                setCompanyId(typeof v === "number" ? v : null);
                setBranchId(null);
                setEmissionPointId(null);
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
              value={branchId}
              onChange={(v) => {
                setBranchId(typeof v === "number" ? v : null);
                setEmissionPointId(null);
              }}
              options={
                branchesQ.data?.map((b) => ({
                  value: b.id,
                  label: `${b.code} · ${b.name}`,
                })) ?? []
              }
              isLoading={branchesQ.isLoading}
              placeholder={
                companyId ? "Selecciona establecimiento..." : "Primero elige empresa"
              }
            />
          </div>

          <div className="space-y-2">
            <Label>Punto de emisión</Label>
            <EntityCombobox
              value={emissionPointId}
              onChange={(v) =>
                setEmissionPointId(typeof v === "number" ? v : null)
              }
              options={emissionPointOptions.map((e) => ({
                value: e.id,
                label: `${e.code}${e.description ? " · " + e.description : ""}`,
              }))}
              isLoading={branchesQ.isLoading}
              placeholder={
                branchId ? "Selecciona punto..." : "Primero elige establecimiento"
              }
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="issue_date">Fecha emisión</Label>
            <Input
              id="issue_date"
              type="date"
              value={issueDate}
              onChange={(e) => setIssueDate(e.target.value)}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="fiscal_period">Periodo fiscal</Label>
            <Input
              id="fiscal_period"
              type="month"
              value={fiscalPeriod}
              disabled
              readOnly
            />
            <p className="text-xs text-muted-foreground">
              El SRI toma el mes de la fecha de emisión.
            </p>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Sujeto retenido (proveedor)</CardTitle>
          <p className="mt-1 text-sm text-muted-foreground">
            Proveedor al que se le practica la retención.
          </p>
        </CardHeader>
        <CardContent className="space-y-2">
          <EntityCombobox
            value={customerId}
            onChange={(v, opt) => {
              setCustomerId(typeof v === "number" ? v : null);
              setSelectedCustomer((opt?.meta as Customer) ?? null);
            }}
            options={customerOptions}
            isLoading={customersQ.isFetching}
            onSearch={setCustomerSearch}
            placeholder="Buscar proveedor por nombre o cédula/RUC..."
            searchPlaceholder="Escribe para buscar..."
            emptyMessage="Sin registros. Crea al proveedor como cliente primero."
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
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Documento sustento</CardTitle>
          <p className="mt-1 text-sm text-muted-foreground">
            Comprobante del proveedor que origina la retención. Aplica a todas
            las líneas.
          </p>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div className="space-y-2">
            <Label>Tipo de comprobante</Label>
            <Select value={supportDocCode} onValueChange={setSupportDocCode}>
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {SUPPORT_DOC_TYPES.map((t) => (
                  <SelectItem key={t.code} value={t.code}>
                    {t.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="support_number">Número</Label>
            <Input
              id="support_number"
              value={supportDocNumber}
              onChange={(e) => setSupportDocNumber(e.target.value)}
              placeholder="001-001-000000123"
              maxLength={20}
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="support_date">Fecha</Label>
            <Input
              id="support_date"
              type="date"
              value={supportDocDate}
              onChange={(e) => setSupportDocDate(e.target.value)}
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="support_total">Total del sustento</Label>
            <Input
              id="support_total"
              type="number"
              step="0.01"
              min="0"
              value={supportDocTotal}
              onChange={(e) => setSupportDocTotal(Number(e.target.value) || 0)}
            />
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-start justify-between gap-3">
          <div>
            <CardTitle>Retenciones</CardTitle>
            <p className="mt-1 text-sm text-muted-foreground">
              Líneas de retención de renta e IVA sobre el documento sustento.
            </p>
          </div>
          <Button
            type="button"
            variant="outline"
            size="sm"
            className="shrink-0"
            onClick={() => setRows((prev) => [...prev, emptyRow()])}
          >
            <Plus className="size-4" /> Agregar línea
          </Button>
        </CardHeader>
        <CardContent className="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className="w-[120px]">Impuesto</TableHead>
                <TableHead className="w-[36%]">Código</TableHead>
                <TableHead className="w-[130px]">Base imponible</TableHead>
                <TableHead className="w-[90px]">%</TableHead>
                <TableHead className="w-[130px] text-right">
                  Valor retenido
                </TableHead>
                <TableHead className="w-[40px]"></TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {rows.map((row, idx) => (
                <TableRow key={idx}>
                  <TableCell>
                    <Select
                      value={row.tax_type}
                      onValueChange={(v) =>
                        updateRow(idx, {
                          tax_type: v as TaxType,
                          retention_code: "",
                          retention_rate: 0,
                        })
                      }
                    >
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="renta">Renta</SelectItem>
                        <SelectItem value="iva">IVA</SelectItem>
                      </SelectContent>
                    </Select>
                  </TableCell>
                  <TableCell>
                    <Select
                      value={row.retention_code || undefined}
                      onValueChange={(v) => onSelectCode(idx, row.tax_type, v)}
                    >
                      <SelectTrigger>
                        <SelectValue
                          placeholder={
                            codesQ.isLoading ? "Cargando..." : "Código..."
                          }
                        />
                      </SelectTrigger>
                      <SelectContent>
                        {codesFor(row.tax_type).map((c) => (
                          <SelectItem key={c.code} value={c.code}>
                            {c.code} · {c.name}
                            {c.percentage != null ? ` (${c.percentage}%)` : ""}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </TableCell>
                  <TableCell>
                    <Input
                      type="number"
                      step="0.01"
                      min="0"
                      value={row.tax_base}
                      onChange={(e) =>
                        updateRow(idx, {
                          tax_base: Number(e.target.value) || 0,
                        })
                      }
                    />
                  </TableCell>
                  <TableCell>
                    <Input
                      type="number"
                      step="0.01"
                      min="0"
                      max="100"
                      value={row.retention_rate}
                      onChange={(e) =>
                        updateRow(idx, {
                          retention_rate: Number(e.target.value) || 0,
                        })
                      }
                    />
                  </TableCell>
                  <TableCell>
                    <Input
                      type="number"
                      step="0.01"
                      min="0"
                      value={row.retained_value}
                      onChange={(e) =>
                        updateRow(idx, {
                          retained_value: Number(e.target.value) || 0,
                        })
                      }
                      className="text-right"
                    />
                  </TableCell>
                  <TableCell>
                    <Button
                      type="button"
                      size="icon"
                      variant="ghost"
                      onClick={() =>
                        setRows((prev) =>
                          prev.length > 1
                            ? prev.filter((_, i) => i !== idx)
                            : prev,
                        )
                      }
                      disabled={rows.length === 1}
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

      <Card className="bg-muted/30">
        <CardHeader>
          <CardTitle>Resumen</CardTitle>
        </CardHeader>
        <CardContent className="space-y-1 text-sm">
          <div className="flex items-center justify-between text-muted-foreground">
            <span>Retención renta</span>
            <span>
              {formatMoney(
                round2(
                  rows
                    .filter((r) => r.tax_type === "renta")
                    .reduce((s, r) => s + (r.retained_value || 0), 0),
                ),
              )}
            </span>
          </div>
          <div className="flex items-center justify-between text-muted-foreground">
            <span>Retención IVA</span>
            <span>
              {formatMoney(
                round2(
                  rows
                    .filter((r) => r.tax_type === "iva")
                    .reduce((s, r) => s + (r.retained_value || 0), 0),
                ),
              )}
            </span>
          </div>
          <div className="mt-3 flex items-baseline justify-between border-t border-border pt-3">
            <span className="font-medium">Total retenido</span>
            <span className="text-2xl font-semibold tabular-nums">
              {formatMoney(totalRetenido)}
            </span>
          </div>
        </CardContent>
      </Card>

      {/* Barra de acción fija */}
      <div className="fixed inset-x-0 bottom-0 z-20 border-t border-border bg-background/85 backdrop-blur-md lg:left-64">
        <DocumentGateBanner reasons={gate.reasons} />
        <div className="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-3 lg:px-6">
          <div className="flex items-baseline gap-2">
            <span className="text-sm text-muted-foreground">
              Total retenido
            </span>
            <span className="text-lg font-semibold tabular-nums">
              {formatMoney(totalRetenido)}
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
