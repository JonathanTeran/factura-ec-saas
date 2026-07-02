"use client";

import { useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { Loader2, Plus, Trash2 } from "lucide-react";
import { toast } from "sonner";
import { useMutation, useQueryClient } from "@tanstack/react-query";
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
import { useCompanies } from "@/lib/api/queries/companies";
import { useSuppliers } from "@/lib/api/queries/suppliers";
import { useProducts } from "@/lib/api/queries/products";
import {
  purchaseKeys,
  useCreatePurchase,
} from "@/lib/api/queries/purchases";
import { formatMoney } from "@/lib/format";
import { ClientApiError } from "@/lib/api/client";
import { calcItem, calcTotals } from "@/lib/document-calc";

const TAX_RATES = [0, 5, 12, 15];

const DOC_TYPES = [
  { value: "01", label: "Factura" },
  { value: "03", label: "Liquidación de compra" },
  { value: "04", label: "Nota de crédito" },
  { value: "05", label: "Nota de débito" },
  { value: "06", label: "Guía de remisión" },
  { value: "07", label: "Comprobante de retención" },
];

type ItemDraft = {
  product_id: number | null;
  main_code: string;
  description: string;
  quantity: number;
  unit_price: number;
  discount: number;
  tax_rate: number;
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

export function PurchaseForm() {
  const router = useRouter();
  const qc = useQueryClient();

  const [companyId, setCompanyId] = useState<number | null>(null);
  const [supplierId, setSupplierId] = useState<number | null>(null);
  const [docType, setDocType] = useState("01");
  const [docNumber, setDocNumber] = useState("");
  const [authorization, setAuthorization] = useState("");
  const [issueDate, setIssueDate] = useState(
    () => new Date().toISOString().slice(0, 10),
  );
  const [authDate, setAuthDate] = useState("");
  const [notes, setNotes] = useState("");
  const [items, setItems] = useState<ItemDraft[]>([emptyItem()]);
  const [supplierSearch, setSupplierSearch] = useState("");
  const [productSearch, setProductSearch] = useState("");

  const companiesQ = useCompanies();
  const suppliersQ = useSuppliers({ search: supplierSearch || undefined, per_page: 20 });
  const productsQ = useProducts({ search: productSearch || undefined, per_page: 20 });
  const create = useCreatePurchase();

  const calculated = useMemo(() => items.map(calcItem), [items]);
  const totals = useMemo(() => calcTotals(calculated), [calculated]);

  const submit = useMutation({
    mutationFn: () => {
      if (!companyId || !supplierId) {
        throw new Error("Selecciona empresa y proveedor.");
      }
      if (!docNumber) {
        throw new Error("Indica el número del comprobante del proveedor.");
      }
      if (calculated.every((it) => it.subtotal === 0)) {
        throw new Error("Agrega al menos un item con cantidad y precio.");
      }
      return create.mutateAsync({
        company_id: companyId,
        supplier_id: supplierId,
        document_type: docType,
        supplier_document_number: docNumber,
        supplier_authorization: authorization || undefined,
        issue_date: issueDate,
        authorization_date: authDate || undefined,
        notes: notes || undefined,
        items: calculated.map((it) => ({
          product_id: it.product_id,
          main_code: it.main_code || undefined,
          description: it.description,
          quantity: it.quantity,
          unit_price: it.unit_price,
          discount: it.discount,
          tax_rate: it.tax_rate,
        })),
      });
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: purchaseKeys.all });
      toast.success("Compra registrada");
      router.push("/purchases");
    },
    onError: (e) => toast.error(errMessage(e)),
  });

  const updateItem = (idx: number, patch: Partial<ItemDraft>) => {
    setItems((prev) =>
      prev.map((it, i) => (i === idx ? { ...it, ...patch } : it)),
    );
  };

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault();
        submit.mutate();
      }}
      className="space-y-6"
    >
      <Card>
        <CardHeader>
          <CardTitle className="text-base">Datos del comprobante</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div className="space-y-2">
            <Label>Empresa</Label>
            <EntityCombobox
              value={companyId}
              onChange={(v) => setCompanyId(typeof v === "number" ? v : null)}
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
            <Label>Proveedor</Label>
            <EntityCombobox
              value={supplierId}
              onChange={(v) => setSupplierId(typeof v === "number" ? v : null)}
              options={
                suppliersQ.data?.data.map((s) => ({
                  value: s.id,
                  label: s.business_name,
                  description: s.identification,
                })) ?? []
              }
              isLoading={suppliersQ.isFetching}
              onSearch={setSupplierSearch}
              placeholder="Buscar proveedor..."
              searchPlaceholder="Nombre o RUC..."
            />
          </div>

          <div className="space-y-2">
            <Label>Tipo</Label>
            <Select value={docType} onValueChange={setDocType}>
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {DOC_TYPES.map((d) => (
                  <SelectItem key={d.value} value={d.value}>
                    {d.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="doc-number">Número (001-001-000000001)</Label>
            <Input
              id="doc-number"
              value={docNumber}
              onChange={(e) => setDocNumber(e.target.value)}
              maxLength={17}
              placeholder="001-001-000000001"
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="auth">Autorización SRI (opcional)</Label>
            <Input
              id="auth"
              value={authorization}
              onChange={(e) => setAuthorization(e.target.value)}
              maxLength={49}
              placeholder="49 dígitos"
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="issue">Fecha emisión</Label>
            <Input
              id="issue"
              type="date"
              value={issueDate}
              onChange={(e) => setIssueDate(e.target.value)}
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="auth-date">Fecha autorización (opcional)</Label>
            <Input
              id="auth-date"
              type="date"
              value={authDate}
              onChange={(e) => setAuthDate(e.target.value)}
            />
          </div>

          <div className="space-y-2 sm:col-span-2">
            <Label htmlFor="notes">Notas</Label>
            <Input
              id="notes"
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
            />
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle className="text-base">Items</CardTitle>
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={() => setItems((prev) => [...prev, emptyItem()])}
          >
            <Plus className="size-4" /> Agregar línea
          </Button>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className="w-[28%]">Producto (opcional)</TableHead>
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
              {items.map((item, idx) => {
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
                        options={
                          productsQ.data?.data.map((p) => ({
                            value: p.id,
                            label: p.name,
                            description: `${p.code} · ${formatMoney(p.unit_price)}`,
                            meta: p,
                          })) ?? []
                        }
                        isLoading={productsQ.isFetching}
                        onSearch={setProductSearch}
                        placeholder="Buscar..."
                        searchPlaceholder="Código..."
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
                          setItems((prev) =>
                            prev.length > 1
                              ? prev.filter((_, i) => i !== idx)
                              : prev,
                          )
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

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="lg:col-span-2"></div>
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Resumen</CardTitle>
          </CardHeader>
          <CardContent className="space-y-1 text-sm">
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
            <div className="flex items-center justify-between border-t pt-2 mt-2 font-semibold text-base">
              <span>Total</span>
              <span>{formatMoney(totals.total)}</span>
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="flex justify-end gap-2">
        <Button type="button" variant="outline" onClick={() => router.back()}>
          Cancelar
        </Button>
        <Button type="submit" disabled={submit.isPending}>
          {submit.isPending && <Loader2 className="size-4 animate-spin" />}
          Registrar compra
        </Button>
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
