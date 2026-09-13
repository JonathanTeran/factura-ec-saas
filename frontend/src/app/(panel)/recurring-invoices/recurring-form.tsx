"use client";

import { useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { Loader2, Plus, Trash2 } from "lucide-react";
import { toast } from "sonner";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
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
import { useCompanies, useCompanyBranches } from "@/lib/api/queries/companies";
import { useCustomers } from "@/lib/api/queries/customers";
import { useProducts } from "@/lib/api/queries/products";
import {
  FREQUENCIES,
  useCreateRecurringInvoice,
  useUpdateRecurringInvoice,
  type RecurringInvoiceInput,
} from "@/lib/api/queries/recurring-invoices";
import { ClientApiError } from "@/lib/api/client";
import type { RecurringInvoice } from "@/lib/api/types";
import { TAX_OPTIONS, calcItem, calcTotals, effectiveTaxCode } from "@/lib/document-calc";
import { formatMoney } from "@/lib/format";

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

type ItemDraft = {
  product_id: number | null;
  product_label?: string;
  main_code: string;
  description: string;
  quantity: number;
  unit_price: number;
  discount: number;
  tax_rate: number;
  tax_percentage_code: string;
};

function emptyItem(): ItemDraft {
  return {
    product_id: null,
    main_code: "",
    description: "",
    quantity: 1,
    unit_price: 0,
    discount: 0,
    tax_rate: 15,
    tax_percentage_code: "4",
  };
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

const today = () => new Date().toISOString().slice(0, 10);

/**
 * Alta y edición de facturas recurrentes: plantilla de la factura
 * (emisor, cliente, ítems, forma de pago) + calendario y avisos.
 */
export function RecurringForm({ recurring }: { recurring?: RecurringInvoice }) {
  const router = useRouter();
  const isEdit = !!recurring;

  const [name, setName] = useState(recurring?.name ?? "");
  const [companyId, setCompanyId] = useState<number | null>(recurring?.company_id ?? null);
  const [branchId, setBranchId] = useState<number | null>(recurring?.branch_id ?? null);
  const [emissionPointId, setEmissionPointId] = useState<number | null>(
    recurring?.emission_point_id ?? null,
  );
  const [customerId, setCustomerId] = useState<number | null>(recurring?.customer_id ?? null);
  const [frequency, setFrequency] = useState(recurring?.frequency ?? "monthly");
  const [startDate, setStartDate] = useState(recurring?.start_date ?? today());
  const [nextIssueDate, setNextIssueDate] = useState(recurring?.next_issue_date ?? "");
  const [endDate, setEndDate] = useState(recurring?.end_date ?? "");
  const [maxIssues, setMaxIssues] = useState(
    recurring?.max_issues != null ? String(recurring.max_issues) : "",
  );
  const [paymentMethod, setPaymentMethod] = useState(
    recurring?.payment_methods?.[0]?.code ?? "20",
  );
  const [paymentTerm, setPaymentTerm] = useState(
    String(recurring?.payment_methods?.[0]?.term ?? 0),
  );
  const [autoSend, setAutoSend] = useState(recurring?.auto_send ?? true);
  const [notify, setNotify] = useState(recurring?.notify_before_issue ?? true);
  const [notifyDays, setNotifyDays] = useState(String(recurring?.notify_days_before ?? 1));
  const [notes, setNotes] = useState(recurring?.notes ?? "");
  const [items, setItems] = useState<ItemDraft[]>(() =>
    recurring?.items?.length
      ? recurring.items.map((it) => ({
          product_id: it.product_id ?? null,
          product_label: it.description,
          main_code: it.main_code ?? "",
          description: it.description,
          quantity: it.quantity,
          unit_price: it.unit_price,
          discount: it.discount ?? 0,
          tax_rate: it.tax_rate ?? 15,
          tax_percentage_code: effectiveTaxCode({
            tax_percentage_code: it.tax_percentage_code ?? undefined,
            tax_rate: it.tax_rate ?? 15,
          }),
        }))
      : [emptyItem()],
  );
  const [customerSearch, setCustomerSearch] = useState("");
  const [productSearch, setProductSearch] = useState("");

  const companiesQ = useCompanies();
  const branchesQ = useCompanyBranches(companyId);
  const customersQ = useCustomers({ search: customerSearch || undefined, per_page: 20 });
  const productsQ = useProducts({ search: productSearch || undefined, per_page: 20 });
  const create = useCreateRecurringInvoice();
  const update = useUpdateRecurringInvoice(recurring?.id ?? 0);
  const isPending = create.isPending || update.isPending;

  const selectedBranch = branchesQ.data?.find((b) => b.id === branchId);
  const emissionPointOptions = (selectedBranch?.emission_points ?? []).filter(
    (p) => p.is_active !== false || p.id === emissionPointId,
  );

  // Preselección cuando solo hay una opción (mismo patrón que Nueva factura).
  if (companyId === null && companiesQ.data?.length === 1) {
    setCompanyId(companiesQ.data[0].id);
  }
  if (companyId && branchId === null && branchesQ.data?.length === 1) {
    setBranchId(branchesQ.data[0].id);
  }
  if (branchId && emissionPointId === null && emissionPointOptions.length === 1) {
    setEmissionPointId(emissionPointOptions[0].id);
  }

  const totals = useMemo(
    () =>
      calcTotals(
        items.map((it) =>
          calcItem({
            product_id: it.product_id,
            main_code: it.main_code,
            description: it.description,
            quantity: it.quantity,
            unit_price: it.unit_price,
            discount: it.discount,
            tax_rate: it.tax_rate,
            tax_percentage_code: it.tax_percentage_code,
          }),
        ),
      ),
    [items],
  );

  const customerOptions = (() => {
    const opts =
      customersQ.data?.data.map((c) => ({
        value: c.id,
        label: c.name,
        description: c.identification_number,
      })) ?? [];
    if (recurring?.customer && !opts.some((o) => o.value === recurring.customer!.id)) {
      opts.unshift({
        value: recurring.customer.id,
        label: recurring.customer.name,
        description: recurring.customer.identification_number,
      });
    }
    return opts;
  })();

  const productOptions = useMemo(
    () =>
      productsQ.data?.data.map((p) => ({
        value: p.id,
        label: p.name,
        description: `${p.code} · ${formatMoney(p.unit_price)}`,
        meta: p,
      })) ?? [],
    [productsQ.data],
  );

  const updateItem = (idx: number, patch: Partial<ItemDraft>) => {
    setItems((prev) => prev.map((it, i) => (i === idx ? { ...it, ...patch } : it)));
  };

  const onSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!companyId || !branchId || !emissionPointId || !customerId) {
      toast.error("Selecciona empresa, establecimiento, punto de emisión y cliente.");
      return;
    }
    if (items.some((it) => !it.description.trim())) {
      toast.error("Cada línea necesita una descripción.");
      return;
    }
    if (totals.total <= 0) {
      toast.error("Agrega al menos un ítem con cantidad y precio.");
      return;
    }

    const payload: RecurringInvoiceInput = {
      name: name || null,
      company_id: companyId,
      branch_id: branchId,
      emission_point_id: emissionPointId,
      customer_id: customerId,
      frequency,
      start_date: startDate,
      next_issue_date: isEdit ? nextIssueDate || null : null,
      end_date: endDate || null,
      max_issues: maxIssues ? Number(maxIssues) : null,
      payment_methods: [{ code: paymentMethod, term: Number(paymentTerm) || 0, time_unit: "dias" }],
      notes: notes || null,
      notify_before_issue: notify,
      notify_days_before: notify ? Number(notifyDays) || 0 : 0,
      auto_send: autoSend,
      items: items.map((it) => ({
        product_id: it.product_id,
        main_code: it.main_code || null,
        description: it.description,
        quantity: it.quantity,
        unit_price: it.unit_price,
        discount: it.discount,
        tax_rate: it.tax_rate,
        tax_percentage_code: it.tax_percentage_code,
      })),
    };

    if (isEdit) {
      update.mutate(payload, {
        onSuccess: () => {
          toast.success("Recurrente actualizada");
          router.push(`/recurring-invoices/${recurring.id}`);
        },
        onError: (err) => toast.error(errMessage(err)),
      });
      return;
    }

    create.mutate(payload, {
      onSuccess: (res) => {
        toast.success("Recurrente creada");
        router.push(`/recurring-invoices/${res.data.recurring_invoice.id}`);
      },
      onError: (err) => toast.error(errMessage(err)),
    });
  };

  return (
    <form onSubmit={onSubmit} className="mx-auto max-w-5xl space-y-5 pb-24">
      <Card>
        <CardHeader>
          <CardTitle>{isEdit ? "Editando recurrente" : "Plantilla de la factura"}</CardTitle>
          <p className="mt-1 text-sm text-muted-foreground">
            Cada emisión creará una factura con estos datos, numerada en el punto de emisión elegido.
          </p>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div className="space-y-2 sm:col-span-2 lg:col-span-4">
            <Label htmlFor="rec-name">Nombre (para identificarla)</Label>
            <Input
              id="rec-name"
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="Hosting mensual · Cliente X"
              maxLength={120}
            />
          </div>
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
                branchesQ.data?.map((b) => ({ value: b.id, label: `${b.code} · ${b.name}` })) ?? []
              }
              isLoading={branchesQ.isLoading}
              placeholder={companyId ? "Selecciona establecimiento..." : "Primero elige empresa"}
            />
          </div>
          <div className="space-y-2">
            <Label>Punto de emisión</Label>
            <EntityCombobox
              value={emissionPointId}
              onChange={(v) => setEmissionPointId(typeof v === "number" ? v : null)}
              options={emissionPointOptions.map((p) => ({
                value: p.id,
                label: `${p.code}${p.description ? " · " + p.description : ""}`,
              }))}
              isLoading={branchesQ.isLoading}
              placeholder={branchId ? "Selecciona punto..." : "Primero elige establecimiento"}
            />
          </div>
          <div className="space-y-2">
            <Label>Cliente</Label>
            <EntityCombobox
              value={customerId}
              onChange={(v) => setCustomerId(typeof v === "number" ? v : null)}
              options={customerOptions}
              isLoading={customersQ.isFetching}
              onSearch={setCustomerSearch}
              placeholder="Buscar cliente..."
              searchPlaceholder="Nombre o cédula..."
            />
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-start justify-between gap-3">
          <div>
            <CardTitle>Productos y servicios</CardTitle>
            <p className="mt-1 text-sm text-muted-foreground">
              Los subtotales e IVA se calculan en cada emisión con la tarifa elegida.
            </p>
          </div>
          <Button
            type="button"
            variant="outline"
            size="sm"
            className="shrink-0"
            onClick={() => setItems((prev) => [...prev, emptyItem()])}
          >
            <Plus className="size-4" /> Agregar línea
          </Button>
        </CardHeader>
        <CardContent className="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className="w-[24%]">Producto</TableHead>
                <TableHead>Descripción</TableHead>
                <TableHead className="w-[80px]">Cant.</TableHead>
                <TableHead className="w-[110px]">P. unit.</TableHead>
                <TableHead className="w-[90px]">Desc.</TableHead>
                <TableHead className="w-[130px]">IVA</TableHead>
                <TableHead className="w-[110px] text-right">Subtotal</TableHead>
                <TableHead className="w-[40px]"></TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {items.map((item, idx) => {
                const options =
                  item.product_id && !productOptions.some((o) => o.value === item.product_id)
                    ? [
                        { value: item.product_id, label: item.product_label ?? item.description, meta: undefined },
                        ...productOptions,
                      ]
                    : productOptions;
                const line = calcItem({
                  product_id: item.product_id,
                  main_code: item.main_code,
                  description: item.description,
                  quantity: item.quantity,
                  unit_price: item.unit_price,
                  discount: item.discount,
                  tax_rate: item.tax_rate,
                  tax_percentage_code: item.tax_percentage_code,
                });
                return (
                  <TableRow key={idx}>
                    <TableCell>
                      <EntityCombobox
                        value={item.product_id}
                        onChange={(_, opt) => {
                          const p = opt?.meta as
                            | { id: number; name: string; code: string; unit_price: number; tax_rate: number; tax_percentage_code?: string | null }
                            | undefined;
                          if (p) {
                            updateItem(idx, {
                              product_id: p.id,
                              product_label: p.name,
                              main_code: p.code,
                              description: p.name,
                              unit_price: p.unit_price,
                              tax_rate: p.tax_rate ?? 15,
                              tax_percentage_code: effectiveTaxCode({
                                tax_percentage_code: p.tax_percentage_code ?? undefined,
                                tax_rate: p.tax_rate ?? 15,
                              }),
                            });
                          } else {
                            updateItem(idx, { product_id: null, product_label: undefined, main_code: "" });
                          }
                        }}
                        options={options}
                        isLoading={productsQ.isFetching}
                        onSearch={setProductSearch}
                        placeholder="Buscar..."
                        buttonClassName="h-9 text-sm"
                      />
                    </TableCell>
                    <TableCell>
                      <Input
                        value={item.description}
                        onChange={(e) => updateItem(idx, { description: e.target.value })}
                        placeholder="Descripción"
                      />
                    </TableCell>
                    <TableCell>
                      <Input
                        type="number"
                        step="0.01"
                        min="0"
                        value={item.quantity}
                        onChange={(e) => updateItem(idx, { quantity: Number(e.target.value) || 0 })}
                      />
                    </TableCell>
                    <TableCell>
                      <Input
                        type="number"
                        step="0.01"
                        min="0"
                        value={item.unit_price}
                        onChange={(e) => updateItem(idx, { unit_price: Number(e.target.value) || 0 })}
                      />
                    </TableCell>
                    <TableCell>
                      <Input
                        type="number"
                        step="0.01"
                        min="0"
                        value={item.discount}
                        onChange={(e) => updateItem(idx, { discount: Number(e.target.value) || 0 })}
                      />
                    </TableCell>
                    <TableCell>
                      <Select
                        value={item.tax_percentage_code}
                        onValueChange={(code) => {
                          const opt = TAX_OPTIONS.find((o) => o.code === code);
                          updateItem(idx, { tax_percentage_code: code, tax_rate: opt?.rate ?? 0 });
                        }}
                      >
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          {TAX_OPTIONS.map((o) => (
                            <SelectItem key={o.code} value={o.code}>
                              {o.label}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </TableCell>
                    <TableCell className="text-right font-medium tabular-nums">
                      {formatMoney(line.subtotal)}
                    </TableCell>
                    <TableCell>
                      <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        onClick={() =>
                          setItems((prev) => (prev.length > 1 ? prev.filter((_, i) => i !== idx) : prev))
                        }
                        disabled={items.length === 1}
                      >
                        <Trash2 className="size-4" />
                      </Button>
                    </TableCell>
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <div className="grid gap-5 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>Programación</CardTitle>
            <p className="mt-1 text-sm text-muted-foreground">
              El lote diario (06:00) emite las facturas cuya fecha llegó. También puedes emitir antes desde el detalle.
            </p>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label>Frecuencia</Label>
              <Select value={frequency} onValueChange={setFrequency}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {FREQUENCIES.map((f) => (
                    <SelectItem key={f.value} value={f.value}>
                      {f.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="rec-start">{isEdit ? "Fecha de inicio" : "Primera emisión"}</Label>
              <Input
                id="rec-start"
                type="date"
                value={startDate}
                onChange={(e) => setStartDate(e.target.value)}
                required
              />
            </div>
            {isEdit && (
              <div className="space-y-2">
                <Label htmlFor="rec-next">Próxima emisión</Label>
                <Input
                  id="rec-next"
                  type="date"
                  value={nextIssueDate}
                  onChange={(e) => setNextIssueDate(e.target.value)}
                />
              </div>
            )}
            <div className="space-y-2">
              <Label htmlFor="rec-end">Fecha de fin (opcional)</Label>
              <Input
                id="rec-end"
                type="date"
                value={endDate}
                min={startDate}
                onChange={(e) => setEndDate(e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="rec-max">Máximo de emisiones (opcional)</Label>
              <Input
                id="rec-max"
                type="number"
                min="1"
                value={maxIssues}
                onChange={(e) => setMaxIssues(e.target.value)}
                placeholder="Sin límite"
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
            <div className="space-y-2">
              <Label htmlFor="rec-term">Plazo de pago (días)</Label>
              <Input
                id="rec-term"
                type="number"
                min="0"
                value={paymentTerm}
                onChange={(e) => setPaymentTerm(e.target.value)}
              />
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="rec-notes">Notas en la factura (opcional)</Label>
              <Textarea
                id="rec-notes"
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                rows={2}
                className="min-h-10"
              />
            </div>
            <label className="flex cursor-pointer items-start gap-3 rounded-lg border border-border p-3 text-sm sm:col-span-2">
              <input
                type="checkbox"
                className="mt-0.5 size-4 accent-primary"
                checked={autoSend}
                onChange={(e) => setAutoSend(e.target.checked)}
              />
              <span>
                <span className="font-medium">Enviar al SRI automáticamente</span>
                <span className="block text-muted-foreground">
                  Si lo desmarcas, cada factura queda en borrador para que la revises y la envíes tú.
                </span>
              </span>
            </label>
            <label className="flex cursor-pointer items-start gap-3 rounded-lg border border-border p-3 text-sm sm:col-span-2">
              <input
                type="checkbox"
                className="mt-0.5 size-4 accent-primary"
                checked={notify}
                onChange={(e) => setNotify(e.target.checked)}
              />
              <span className="flex-1">
                <span className="font-medium">Avisarme antes de cada emisión</span>
                <span className="mt-2 flex items-center gap-2 text-muted-foreground">
                  <Input
                    type="number"
                    min="1"
                    max="30"
                    value={notifyDays}
                    disabled={!notify}
                    onChange={(e) => setNotifyDays(e.target.value)}
                    className="h-8 w-20"
                  />
                  días antes, por correo y en el panel.
                </span>
              </span>
            </label>
          </CardContent>
        </Card>

        <Card className="bg-muted/30">
          <CardHeader>
            <CardTitle>Cada factura</CardTitle>
          </CardHeader>
          <CardContent className="space-y-1 text-sm">
            <Row label="Subtotal" value={totals.subtotal_0 + totals.subtotal_5 + totals.subtotal_8 + totals.subtotal_12 + totals.subtotal_13 + totals.subtotal_15 + totals.subtotal_no_tax} />
            <Row label="Descuento" value={totals.total_discount} />
            <Row label="IVA" value={totals.total_tax} />
            <div className="mt-3 flex items-baseline justify-between border-t border-border pt-3">
              <span className="font-medium">Total</span>
              <span className="text-2xl font-semibold tabular-nums">{formatMoney(totals.total)}</span>
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="fixed inset-x-0 bottom-0 z-20 border-t border-border bg-background/85 backdrop-blur-md lg:left-64">
        <div className="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-3 lg:px-6">
          <div className="flex items-baseline gap-2">
            <span className="text-sm text-muted-foreground">Total por emisión</span>
            <span className="text-lg font-semibold tabular-nums">{formatMoney(totals.total)}</span>
          </div>
          <div className="flex gap-2">
            <Button type="button" variant="outline" onClick={() => router.back()}>
              Cancelar
            </Button>
            <Button type="submit" disabled={isPending}>
              {isPending && <Loader2 className="size-4 animate-spin" />}
              {isEdit ? "Guardar cambios" : "Crear recurrente"}
            </Button>
          </div>
        </div>
      </div>
    </form>
  );
}

function Row({ label, value }: { label: string; value: number }) {
  if (!value) return null;
  return (
    <div className="flex justify-between text-muted-foreground">
      <span>{label}</span>
      <span className="tabular-nums">{formatMoney(value)}</span>
    </div>
  );
}
