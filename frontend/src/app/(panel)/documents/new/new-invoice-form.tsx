"use client";

import { useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { Loader2, Plus, Trash2, Send } from "lucide-react";
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
import {
  useCompanies,
  useEmissionPoints,
} from "@/lib/api/queries/companies";
import { useCustomers } from "@/lib/api/queries/customers";
import { useProducts } from "@/lib/api/queries/products";
import {
  documentKeys,
  useDocuments,
  useUpdateDocument,
} from "@/lib/api/queries/documents";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import {
  api,
  ClientApiError,
  type ApiSuccess,
  type ApiPaginated,
} from "@/lib/api/client";
import {
  buildItemPayload,
  calcItem,
  calcTotals,
  type DraftItem,
} from "@/lib/document-calc";
import { formatMoney, formatDate } from "@/lib/format";
import type { Document, Customer } from "@/lib/api/types";

const TAX_RATES = [0, 5, 12, 15];

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

const DOCUMENT_TYPE_LABELS: Record<string, string> = {
  "01": "Factura",
  "03": "Liquidación de compra",
  "04": "Nota de crédito",
  "05": "Nota de débito",
};

function emptyItem(): DraftItem {
  return {
    product_id: null,
    main_code: "",
    description: "",
    quantity: 1,
    unit_price: 0,
    discount: 0,
    tax_rate: 15,
  };
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

type InfoRow = { key: string; value: string };

type FormState = {
  companyId: number | null;
  emissionPointId: number | null;
  customerId: number | null;
  issueDate: string;
  paymentMethod: string;
  paymentTerm: number;
  tip: number;
  items: DraftItem[];
  additionalInfo: InfoRow[];
  referenceDocId: number | null;
  modificationReason: string;
};

function blankState(): FormState {
  return {
    companyId: null,
    emissionPointId: null,
    customerId: null,
    issueDate: new Date().toISOString().slice(0, 10),
    paymentMethod: "20",
    paymentTerm: 0,
    tip: 0,
    items: [emptyItem()],
    additionalInfo: [{ key: "", value: "" }],
    referenceDocId: null,
    modificationReason: "",
  };
}

function fromDocument(doc: Document): FormState {
  return {
    companyId: null, // company id is not exposed in DocumentResource directly
    emissionPointId: null,
    customerId: doc.customer?.id ?? null,
    issueDate: (doc.issue_date ?? new Date().toISOString().slice(0, 10)).slice(0, 10),
    paymentMethod: doc.payment_methods?.[0]?.code ?? "20",
    paymentTerm: doc.payment_methods?.[0]?.term ?? 0,
    tip: Number(doc.tip ?? 0),
    items:
      doc.items?.map((it) => ({
        product_id: it.product_id ?? null,
        main_code: it.main_code,
        description: it.description,
        quantity: Number(it.quantity),
        unit_price: Number(it.unit_price),
        discount: Number(it.discount ?? 0),
        tax_rate: Number(it.tax_rate ?? 15),
      })) ?? [emptyItem()],
    additionalInfo: [{ key: "", value: "" }],
    referenceDocId: null,
    modificationReason: "",
  };
}

export function NewInvoiceForm({
  documentType = "01",
  existingDocument,
}: {
  documentType?: "01" | "03" | "04" | "05";
  existingDocument?: Document;
}) {
  const router = useRouter();
  const qc = useQueryClient();
  const isCreditNote = documentType === "04" || documentType === "05";
  const isDebitNote = documentType === "05";
  const isLiquidacion = documentType === "03";
  const isEdit = !!existingDocument;

  const [state, setState] = useState<FormState>(() =>
    existingDocument ? fromDocument(existingDocument) : blankState(),
  );
  const [customerSearch, setCustomerSearch] = useState("");
  const [productSearch, setProductSearch] = useState("");
  const [refDocSearch, setRefDocSearch] = useState("");
  const [selectedCustomer, setSelectedCustomer] = useState<Customer | null>(null);

  const updateInfo = (idx: number, patch: Partial<InfoRow>) =>
    setState((prev) => ({
      ...prev,
      additionalInfo: prev.additionalInfo.map((r, i) =>
        i === idx ? { ...r, ...patch } : r,
      ),
    }));

  const fillCustomerInfo = () => {
    if (!selectedCustomer) {
      toast.error("Primero selecciona un cliente.");
      return;
    }
    const rows: InfoRow[] = [];
    if (selectedCustomer.address)
      rows.push({ key: "Dirección", value: selectedCustomer.address });
    if (selectedCustomer.phone)
      rows.push({ key: "Teléfono", value: selectedCustomer.phone });
    if (selectedCustomer.email)
      rows.push({ key: "Email", value: selectedCustomer.email });
    if (rows.length === 0) {
      toast.error("El cliente no tiene dirección, teléfono ni correo.");
      return;
    }
    setState((prev) => {
      const existing = prev.additionalInfo.filter(
        (r) => r.key.trim() || r.value.trim(),
      );
      const keys = new Set(existing.map((r) => r.key.toLowerCase()));
      const merged = [
        ...existing,
        ...rows.filter((r) => !keys.has(r.key.toLowerCase())),
      ];
      return { ...prev, additionalInfo: merged.length ? merged : [{ key: "", value: "" }] };
    });
  };

  const setField = <K extends keyof FormState>(key: K, value: FormState[K]) => {
    setState((prev) => ({ ...prev, [key]: value }));
  };

  const companiesQ = useCompanies();
  const emissionsQ = useEmissionPoints(state.companyId);
  const customersQ = useCustomers({ search: customerSearch || undefined, per_page: 20 });
  const productsQ = useProducts({ search: productSearch || undefined, per_page: 20 });
  const refDocsQ = useDocuments({
    search: refDocSearch || undefined,
    status: "authorized",
    document_type: "01",
    per_page: 20,
  });

  const calculated = useMemo(() => state.items.map(calcItem), [state.items]);
  const totals = useMemo(() => calcTotals(calculated), [calculated]);
  const grandTotal = totals.total + state.tip;

  // Consumidor final (ventas sin identificar): busca o crea el cliente 999...9
  const consumidorFinal = useMutation({
    mutationFn: async (): Promise<Customer> => {
      const list = await api.get<ApiPaginated<Customer>>("customers", {
        query: { search: "9999999999999", per_page: 1 },
      });
      const found = list.data?.[0];
      if (found) return found;
      const created = await api.post<
        ApiSuccess<{ customer: Customer } | Customer>
      >("customers", {
        identification_type: "07",
        identification_number: "9999999999999",
        name: "CONSUMIDOR FINAL",
        is_active: true,
      });
      const d = created.data as { customer?: Customer } & Customer;
      return d.customer ?? d;
    },
    onSuccess: (c) => {
      setField("customerId", c.id);
      setSelectedCustomer(c);
      setCustomerSearch("9999999999999");
    },
    onError: (e) => toast.error(errMessage(e)),
  });

  const updateMut = useUpdateDocument(existingDocument?.id ?? 0);

  const [pendingAction, setPendingAction] = useState<"draft" | "send" | null>(
    null,
  );

  const submit = useMutation({
    mutationFn: async (sendToSri: boolean) => {
      if (!state.companyId || !state.emissionPointId || !state.customerId) {
        throw new Error("Selecciona empresa, punto de emisión y cliente.");
      }
      if (calculated.length === 0 || calculated.every((i) => i.subtotal === 0)) {
        throw new Error("Agrega al menos un item con cantidad y precio.");
      }
      if (isCreditNote && !isEdit) {
        if (!state.referenceDocId) {
          throw new Error("Selecciona el documento de referencia.");
        }
        if (!state.modificationReason || state.modificationReason.length < 3) {
          throw new Error("Indica el motivo de la modificación.");
        }
      }
      const payload: Record<string, unknown> = {
        company_id: state.companyId,
        customer_id: state.customerId,
        emission_point_id: state.emissionPointId,
        document_type: documentType,
        issue_date: state.issueDate,
        subtotal_no_tax: totals.subtotal_no_tax,
        subtotal_0: totals.subtotal_0,
        subtotal_5: totals.subtotal_5,
        subtotal_12: totals.subtotal_12,
        subtotal_15: totals.subtotal_15,
        total_discount: totals.total_discount,
        total_tax: totals.total_tax,
        tip: state.tip,
        total: totals.total + state.tip,
        payment_methods: [
          {
            code: state.paymentMethod,
            amount: totals.total + state.tip,
            term: state.paymentTerm,
            time_unit: "dias",
          },
        ],
        items: calculated.map(buildItemPayload),
      };
      const info = state.additionalInfo.reduce<Record<string, string>>(
        (acc, r) => {
          const k = r.key.trim();
          const v = r.value.trim();
          if (k && v) acc[k] = v;
          return acc;
        },
        {},
      );
      if (Object.keys(info).length > 0) {
        payload.additional_info = info;
      }
      if (isCreditNote) {
        if (state.referenceDocId)
          payload.reference_document_id = state.referenceDocId;
        if (state.modificationReason)
          payload.modification_reason = state.modificationReason;
      }

      // Guardar (crear o actualizar borrador)
      const res =
        isEdit && existingDocument
          ? await updateMut.mutateAsync(payload)
          : await api.post<ApiSuccess<{ document: Document }>>(
              "documents",
              payload,
            );
      const id = (res as ApiSuccess<{ document: Document }>).data.document.id;

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
        toast.success(
          isEdit
            ? "Borrador actualizado"
            : `${DOCUMENT_TYPE_LABELS[documentType]} guardado como borrador`,
        );
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

  const updateItem = (idx: number, patch: Partial<DraftItem>) => {
    setState((prev) => ({
      ...prev,
      items: prev.items.map((it, i) => (i === idx ? { ...it, ...patch } : it)),
    }));
  };

  const customerOptions =
    customersQ.data?.data.map((c) => ({
      value: c.id,
      label: c.name,
      description: `${c.identification_number}${c.email ? " · " + c.email : ""}`,
      meta: c,
    })) ?? [];

  const productOptions =
    productsQ.data?.data.map((p) => ({
      value: p.id,
      label: p.name,
      description: `${p.code} · ${formatMoney(p.unit_price)} · IVA ${p.tax_rate}%`,
      meta: p,
    })) ?? [];

  const refDocOptions =
    refDocsQ.data?.data.map((d) => ({
      value: d.id,
      label: d.document_number ?? `#${d.id}`,
      description: `${formatDate(d.issue_date)} · ${d.customer?.name ?? "—"} · ${formatMoney(d.total)}`,
      meta: d,
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
          <CardTitle>
            {isEdit ? "Editando borrador" : "Datos del documento"}
          </CardTitle>
          <p className="mt-1 text-sm text-muted-foreground">
            Emisor, cliente y fecha del comprobante.
          </p>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div className="space-y-2">
            <Label>Empresa emisora</Label>
            <EntityCombobox
              value={state.companyId}
              onChange={(v) => {
                setField("companyId", typeof v === "number" ? v : null);
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
            <Label>Punto de emisión</Label>
            <EntityCombobox
              value={state.emissionPointId}
              onChange={(v) =>
                setField("emissionPointId", typeof v === "number" ? v : null)
              }
              options={
                emissionsQ.data?.map((e) => ({
                  value: e.id,
                  label: `${e.code}${e.description ? " · " + e.description : ""}`,
                })) ?? []
              }
              isLoading={emissionsQ.isLoading}
              placeholder={
                state.companyId ? "Selecciona punto..." : "Primero elige empresa"
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

          <div className="space-y-2 sm:col-span-2 lg:col-span-3">
            <div className="flex items-center justify-between">
              <Label>{isLiquidacion ? "Proveedor" : "Cliente"}</Label>
              {documentType === "01" && (
                <button
                  type="button"
                  onClick={() => consumidorFinal.mutate()}
                  disabled={consumidorFinal.isPending}
                  className="inline-flex items-center gap-1.5 text-xs font-medium text-primary underline-offset-4 hover:underline disabled:opacity-50"
                >
                  {consumidorFinal.isPending && (
                    <Loader2 className="size-3 animate-spin" />
                  )}
                  Usar consumidor final
                </button>
              )}
            </div>
            <EntityCombobox
              value={state.customerId}
              onChange={(v, opt) => {
                setField("customerId", typeof v === "number" ? v : null);
                setSelectedCustomer((opt?.meta as Customer) ?? null);
              }}
              options={customerOptions}
              isLoading={customersQ.isFetching}
              onSearch={setCustomerSearch}
              placeholder={
                isLiquidacion
                  ? "Buscar proveedor por nombre o cédula/RUC..."
                  : "Buscar cliente por nombre o cédula/RUC..."
              }
              searchPlaceholder="Escribe para buscar..."
              emptyMessage={
                isLiquidacion
                  ? "Sin registros. Crea al proveedor como cliente primero."
                  : "Sin clientes. Agrega uno primero."
              }
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

          {isCreditNote && (
            <>
              <div className="space-y-2 sm:col-span-2 lg:col-span-3">
                <Label>Documento de referencia (factura autorizada)</Label>
                <EntityCombobox
                  value={state.referenceDocId}
                  onChange={(v) =>
                    setField(
                      "referenceDocId",
                      typeof v === "number" ? v : null,
                    )
                  }
                  options={refDocOptions}
                  isLoading={refDocsQ.isFetching}
                  onSearch={setRefDocSearch}
                  placeholder="Buscar factura por número o cliente..."
                  searchPlaceholder="Buscar factura..."
                  emptyMessage="No hay facturas autorizadas para referenciar."
                />
              </div>
              <div className="space-y-2 sm:col-span-2 lg:col-span-3">
                <Label htmlFor="reason">Motivo de la modificación</Label>
                <Input
                  id="reason"
                  value={state.modificationReason}
                  onChange={(e) =>
                    setField("modificationReason", e.target.value)
                  }
                  placeholder="Devolución parcial, ajuste de precio, etc."
                  maxLength={300}
                  required
                />
              </div>
            </>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-start justify-between gap-3">
          <div>
            <CardTitle>
              {isDebitNote ? "Cargos" : "Productos y servicios"}
            </CardTitle>
            <p className="mt-1 text-sm text-muted-foreground">
              {isDebitNote
                ? "Razones de modificación con su valor (formato SRI para notas de débito)."
                : "Cada línea del comprobante con su IVA."}
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
          {isDebitNote ? (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Razón de la modificación</TableHead>
                  <TableHead className="w-[140px]">Valor</TableHead>
                  <TableHead className="w-[110px]">IVA</TableHead>
                  <TableHead className="w-[120px] text-right">
                    Subtotal
                  </TableHead>
                  <TableHead className="w-[40px]"></TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {state.items.map((item, idx) => {
                  const calc = calculated[idx];
                  return (
                    <TableRow key={idx}>
                      <TableCell>
                        <Input
                          value={item.description}
                          onChange={(e) =>
                            updateItem(idx, {
                              description: e.target.value,
                              main_code: "ND",
                              quantity: 1,
                              discount: 0,
                            })
                          }
                          placeholder="Ej. Interés por mora, gastos de cobranza..."
                        />
                      </TableCell>
                      <TableCell>
                        <Input
                          type="number"
                          step="0.01"
                          min="0"
                          value={item.unit_price}
                          onChange={(e) =>
                            updateItem(idx, {
                              unit_price: Number(e.target.value) || 0,
                              main_code: "ND",
                              quantity: 1,
                              discount: 0,
                            })
                          }
                        />
                      </TableCell>
                      <TableCell>
                        <Select
                          value={String(item.tax_rate)}
                          onValueChange={(v) =>
                            updateItem(idx, { tax_rate: Number(v) })
                          }
                        >
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            {TAX_RATES.map((r) => (
                              <SelectItem key={r} value={String(r)}>
                                {r}%
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </TableCell>
                      <TableCell className="text-right font-medium tabular-nums">
                        {formatMoney(calc.subtotal)}
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
                  );
                })}
              </TableBody>
            </Table>
          ) : (
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className="w-[28%]">Producto</TableHead>
                <TableHead>Descripción</TableHead>
                <TableHead className="w-[80px]">Cant.</TableHead>
                <TableHead className="w-[110px]">P. unit.</TableHead>
                <TableHead className="w-[100px]">Desc.</TableHead>
                <TableHead className="w-[100px]">IVA</TableHead>
                <TableHead className="w-[110px] text-right">Subtotal</TableHead>
                <TableHead className="w-[40px]"></TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {state.items.map((item, idx) => {
                const calc = calculated[idx];
                return (
                  <TableRow key={idx}>
                    <TableCell>
                      <EntityCombobox
                        value={item.product_id}
                        onChange={(_, opt) => {
                          const p = opt?.meta as
                            | {
                                id: number;
                                code: string;
                                name: string;
                                unit_price: number;
                                tax_rate: number;
                              }
                            | undefined;
                          if (p) {
                            updateItem(idx, {
                              product_id: p.id,
                              main_code: p.code,
                              description: p.name,
                              unit_price: p.unit_price,
                              tax_rate: p.tax_rate ?? 15,
                            });
                          } else {
                            updateItem(idx, { product_id: null });
                          }
                        }}
                        options={productOptions}
                        isLoading={productsQ.isFetching}
                        onSearch={setProductSearch}
                        placeholder="Buscar..."
                        searchPlaceholder="Código o nombre..."
                        emptyMessage="Sin productos."
                        buttonClassName="h-9 text-sm"
                      />
                    </TableCell>
                    <TableCell>
                      <Input
                        value={item.description}
                        onChange={(e) =>
                          updateItem(idx, { description: e.target.value })
                        }
                        placeholder="Descripción"
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
                      <Input
                        type="number"
                        step="0.01"
                        min="0"
                        value={item.unit_price}
                        onChange={(e) =>
                          updateItem(idx, {
                            unit_price: Number(e.target.value) || 0,
                          })
                        }
                      />
                    </TableCell>
                    <TableCell>
                      <Input
                        type="number"
                        step="0.01"
                        min="0"
                        value={item.discount}
                        onChange={(e) =>
                          updateItem(idx, {
                            discount: Number(e.target.value) || 0,
                          })
                        }
                      />
                    </TableCell>
                    <TableCell>
                      <Select
                        value={String(item.tax_rate)}
                        onValueChange={(v) =>
                          updateItem(idx, { tax_rate: Number(v) })
                        }
                      >
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          {TAX_RATES.map((r) => (
                            <SelectItem key={r} value={String(r)}>
                              {r}%
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </TableCell>
                    <TableCell className="text-right font-medium">
                      {formatMoney(calc.subtotal)}
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
                );
              })}
            </TableBody>
          </Table>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-start justify-between gap-3">
          <div>
            <CardTitle className="text-base">Información adicional</CardTitle>
            <p className="mt-1 text-sm text-muted-foreground">
              Campos que exige/permite el SRI (dirección, teléfono, email,
              observaciones…). Aparecen en el comprobante.
            </p>
          </div>
          <div className="flex shrink-0 flex-wrap justify-end gap-2">
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={fillCustomerInfo}
            >
              Autocompletar del cliente
            </Button>
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() =>
                setState((prev) => ({
                  ...prev,
                  additionalInfo: [...prev.additionalInfo, { key: "", value: "" }],
                }))
              }
            >
              <Plus className="size-4" /> Agregar campo
            </Button>
          </div>
        </CardHeader>
        <CardContent className="space-y-2.5">
          {state.additionalInfo.map((row, idx) => (
            <div key={idx} className="flex items-center gap-2.5">
              <Input
                value={row.key}
                onChange={(e) => updateInfo(idx, { key: e.target.value })}
                placeholder="Campo (ej. Dirección)"
                maxLength={300}
                className="sm:max-w-[240px]"
              />
              <Input
                value={row.value}
                onChange={(e) => updateInfo(idx, { value: e.target.value })}
                placeholder="Valor"
                maxLength={300}
              />
              <Button
                type="button"
                size="icon"
                variant="ghost"
                onClick={() =>
                  setState((prev) => ({
                    ...prev,
                    additionalInfo:
                      prev.additionalInfo.length > 1
                        ? prev.additionalInfo.filter((_, i) => i !== idx)
                        : [{ key: "", value: "" }],
                  }))
                }
              >
                <Trash2 className="size-4" />
              </Button>
            </div>
          ))}
        </CardContent>
      </Card>

      <div className="grid gap-6 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>Forma de pago</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label>Método</Label>
              <Select
                value={state.paymentMethod}
                onValueChange={(v) => setField("paymentMethod", v)}
              >
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
            <div className="space-y-2">
              <Label>Plazo (días)</Label>
              <Input
                type="number"
                min="0"
                value={state.paymentTerm}
                onChange={(e) =>
                  setField("paymentTerm", Number(e.target.value) || 0)
                }
              />
            </div>
            <div className="space-y-2">
              <Label>Propina (opcional)</Label>
              <Input
                type="number"
                step="0.01"
                min="0"
                value={state.tip}
                onChange={(e) => setField("tip", Number(e.target.value) || 0)}
              />
              <p className="text-xs text-muted-foreground">
                Se suma al total del comprobante (SRI la contempla).
              </p>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-muted/30">
          <CardHeader>
            <CardTitle>Resumen</CardTitle>
          </CardHeader>
          <CardContent className="space-y-1 text-sm">
            {!!totals.subtotal_no_tax && (
              <Row label="Sin impuesto" value={totals.subtotal_no_tax} />
            )}
            {!!totals.subtotal_0 && (
              <Row label="Subtotal 0%" value={totals.subtotal_0} />
            )}
            {!!totals.subtotal_5 && (
              <Row label="Subtotal 5%" value={totals.subtotal_5} />
            )}
            {!!totals.subtotal_12 && (
              <Row label="Subtotal 12%" value={totals.subtotal_12} />
            )}
            {!!totals.subtotal_15 && (
              <Row label="Subtotal 15%" value={totals.subtotal_15} />
            )}
            {!!totals.total_discount && (
              <Row label="Descuento" value={totals.total_discount} />
            )}
            <Row label="IVA" value={totals.total_tax} />
            {!!state.tip && <Row label="Propina" value={state.tip} />}
            <div className="mt-3 flex items-baseline justify-between border-t border-border pt-3">
              <span className="font-medium">Total a pagar</span>
              <span className="text-2xl font-semibold tabular-nums">
                {formatMoney(grandTotal)}
              </span>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Barra de acción fija */}
      <div className="fixed inset-x-0 bottom-0 z-20 border-t border-border bg-background/85 backdrop-blur-md lg:left-64">
        <div className="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-3 lg:px-6">
          <div className="flex items-baseline gap-2">
            <span className="text-sm text-muted-foreground">Total</span>
            <span className="text-lg font-semibold tabular-nums">
              {formatMoney(grandTotal)}
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
              disabled={submit.isPending}
            >
              {pendingAction === "draft" && (
                <Loader2 className="size-4 animate-spin" />
              )}
              {isEdit ? "Guardar cambios" : "Guardar borrador"}
            </Button>
            <Button
              type="button"
              disabled={submit.isPending}
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

function Row({ label, value }: { label: string; value: number }) {
  return (
    <div className="flex items-center justify-between text-muted-foreground">
      <span>{label}</span>
      <span>{formatMoney(value)}</span>
    </div>
  );
}
