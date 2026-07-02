"use client";

import { useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { Loader2, Plus, Trash2 } from "lucide-react";
import { toast } from "sonner";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
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
import { useCustomers } from "@/lib/api/queries/customers";
import { useProducts } from "@/lib/api/queries/products";
import { useCreateQuote } from "@/lib/api/queries/quotes";
import { ClientApiError } from "@/lib/api/client";
import { formatMoney } from "@/lib/format";

const TAX_RATES = [0, 5, 12, 15];

type ItemDraft = {
  product_id: number | null;
  description: string;
  quantity: number;
  unit_price: number;
  discount: number;
  tax_rate: number;
};

function calc(it: ItemDraft) {
  const gross = round(it.quantity * it.unit_price);
  const sub = Math.max(0, round(gross - it.discount));
  const tax = round(sub * (it.tax_rate / 100));
  return { subtotal: sub, tax_value: tax, total: round(sub + tax) };
}

function round(n: number) {
  return Math.round(n * 100) / 100;
}

function emptyItem(): ItemDraft {
  return {
    product_id: null,
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

export function QuoteForm() {
  const router = useRouter();
  const [companyId, setCompanyId] = useState<number | null>(null);
  const [customerId, setCustomerId] = useState<number | null>(null);
  const [issueDate, setIssueDate] = useState(
    () => new Date().toISOString().slice(0, 10),
  );
  const [expiryDate, setExpiryDate] = useState("");
  const [paymentTerms, setPaymentTerms] = useState("");
  const [notes, setNotes] = useState("");
  const [items, setItems] = useState<ItemDraft[]>([emptyItem()]);
  const [customerSearch, setCustomerSearch] = useState("");
  const [productSearch, setProductSearch] = useState("");

  const companiesQ = useCompanies();
  const customersQ = useCustomers({ search: customerSearch || undefined, per_page: 20 });
  const productsQ = useProducts({ search: productSearch || undefined, per_page: 20 });
  const create = useCreateQuote();

  const totals = useMemo(() => {
    let subtotal = 0;
    let tax = 0;
    let discount = 0;
    for (const it of items) {
      const c = calc(it);
      subtotal += c.subtotal;
      tax += c.tax_value;
      discount += it.discount;
    }
    return {
      subtotal: round(subtotal),
      total_discount: round(discount),
      total_tax: round(tax),
      total: round(subtotal + tax),
    };
  }, [items]);

  const updateItem = (idx: number, patch: Partial<ItemDraft>) => {
    setItems((prev) =>
      prev.map((it, i) => (i === idx ? { ...it, ...patch } : it)),
    );
  };

  const onSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!companyId || !customerId) {
      toast.error("Selecciona empresa y cliente.");
      return;
    }
    if (items.every((it) => calc(it).subtotal === 0)) {
      toast.error("Agrega al menos un item con cantidad y precio.");
      return;
    }
    create.mutate(
      {
        company_id: companyId,
        customer_id: customerId,
        issue_date: issueDate,
        expiry_date: expiryDate || undefined,
        notes: notes || undefined,
        payment_terms: paymentTerms || undefined,
        subtotal: totals.subtotal,
        total_discount: totals.total_discount,
        total_tax: totals.total_tax,
        total: totals.total,
        items: items.map((it) => {
          const c = calc(it);
          return {
            product_id: it.product_id,
            description: it.description,
            quantity: it.quantity,
            unit_price: it.unit_price,
            discount: it.discount,
            tax_rate: it.tax_rate,
            subtotal: c.subtotal,
            tax_value: c.tax_value,
            total: c.total,
          };
        }),
      },
      {
        onSuccess: () => {
          toast.success("Cotización creada");
          router.push("/quotes");
        },
        onError: (e) => toast.error(errMessage(e)),
      },
    );
  };

  return (
    <form onSubmit={onSubmit} className="mx-auto max-w-5xl space-y-5 pb-24">
      <Card>
        <CardHeader>
          <CardTitle>Datos de la cotización</CardTitle>
          <p className="mt-1 text-sm text-muted-foreground">
            Empresa emisora, cliente y condiciones comerciales.
          </p>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2">
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
            <Label>Cliente</Label>
            <EntityCombobox
              value={customerId}
              onChange={(v) => setCustomerId(typeof v === "number" ? v : null)}
              options={
                customersQ.data?.data.map((c) => ({
                  value: c.id,
                  label: c.name,
                  description: c.identification_number,
                })) ?? []
              }
              isLoading={customersQ.isFetching}
              onSearch={setCustomerSearch}
              placeholder="Buscar cliente..."
              searchPlaceholder="Nombre o cédula..."
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
            <Label htmlFor="expiry">Vence el (opcional)</Label>
            <Input
              id="expiry"
              type="date"
              value={expiryDate}
              onChange={(e) => setExpiryDate(e.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="terms">Condiciones de pago</Label>
            <Input
              id="terms"
              value={paymentTerms}
              onChange={(e) => setPaymentTerms(e.target.value)}
              placeholder="50% anticipo, 50% contra entrega"
            />
          </div>
          <div className="space-y-2">
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
        <CardHeader className="flex flex-row items-start justify-between gap-3">
          <div>
            <CardTitle>Productos y servicios</CardTitle>
            <p className="mt-1 text-sm text-muted-foreground">
              Cada línea de la cotización con su IVA.
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
                <TableHead className="w-[28%]">Producto</TableHead>
                <TableHead>Descripción</TableHead>
                <TableHead className="w-[80px]">Cant.</TableHead>
                <TableHead className="w-[110px]">P. unit.</TableHead>
                <TableHead className="w-[100px]">Desc.</TableHead>
                <TableHead className="w-[90px]">IVA</TableHead>
                <TableHead className="w-[110px] text-right">Subtotal</TableHead>
                <TableHead className="w-[40px]"></TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {items.map((item, idx) => (
                <TableRow key={idx}>
                  <TableCell>
                    <EntityCombobox
                      value={item.product_id}
                      onChange={(_, opt) => {
                        const p = opt?.meta as
                          | { id: number; name: string; unit_price: number; tax_rate: number }
                          | undefined;
                        if (p) {
                          updateItem(idx, {
                            product_id: p.id,
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
                      buttonClassName="h-9 text-sm"
                    />
                  </TableCell>
                  <TableCell>
                    <Input
                      value={item.description}
                      onChange={(e) =>
                        updateItem(idx, { description: e.target.value })
                      }
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
                    {formatMoney(calc(item).subtotal)}
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
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <div className="grid gap-6 lg:grid-cols-3">
        <Card className="bg-muted/30 lg:col-start-3">
          <CardHeader>
            <CardTitle>Resumen</CardTitle>
          </CardHeader>
          <CardContent className="space-y-1 text-sm">
            <Row label="Subtotal" value={totals.subtotal} />
            <Row label="Descuento" value={totals.total_discount} />
            <Row label="IVA" value={totals.total_tax} />
            <div className="mt-3 flex items-baseline justify-between border-t border-border pt-3">
              <span className="font-medium">Total</span>
              <span className="text-2xl font-semibold tabular-nums">
                {formatMoney(totals.total)}
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
              {formatMoney(totals.total)}
            </span>
          </div>
          <div className="flex gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => router.back()}
            >
              Cancelar
            </Button>
            <Button type="submit" disabled={create.isPending}>
              {create.isPending && <Loader2 className="size-4 animate-spin" />}
              Crear cotización
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
      <span>{formatMoney(value)}</span>
    </div>
  );
}
