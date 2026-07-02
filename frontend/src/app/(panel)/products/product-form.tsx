"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import {
  Loader2,
  Hash,
  Barcode,
  Package,
  DollarSign,
  Boxes,
  Tag,
} from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Field, IconInput, FormSection } from "@/components/panel/form";
import {
  useCreateProduct,
  useProduct,
  useUpdateProduct,
  type ProductInput,
} from "@/lib/api/queries/products";
import { ClientApiError } from "@/lib/api/client";
import type { Product } from "@/lib/api/types";
import { StockAdjustDialog } from "./stock-adjust-dialog";

const TAX_RATES = [0, 5, 12, 15];

function fieldErrors(err: unknown): Record<string, string[]> {
  if (err instanceof ClientApiError) {
    const p = err.payload as { errors?: Record<string, string[]> } | null;
    return p?.errors ?? {};
  }
  return {};
}

function errMessage(err: unknown): string {
  if (err instanceof ClientApiError) return err.message;
  return err instanceof Error ? err.message : "Error inesperado";
}

const blankProduct: ProductInput = {
  code: "",
  sku: "",
  name: "",
  description: "",
  type: "product",
  unit_price: 0,
  cost: 0,
  tax_rate: 15,
  track_inventory: true,
  stock: 0,
  min_stock: 0,
  is_active: true,
};

function fromProduct(p: Product): ProductInput {
  return {
    code: p.code,
    sku: p.sku ?? "",
    name: p.name,
    description: p.description ?? "",
    type: p.type,
    unit_price: p.unit_price,
    cost: p.cost ?? 0,
    tax_rate: p.tax_rate,
    track_inventory: p.track_inventory,
    stock: p.stock ?? 0,
    min_stock: p.min_stock ?? 0,
    is_active: p.is_active,
  };
}

export function ProductForm({ id }: { id?: number }) {
  if (!id) return <ProductFormInner initial={blankProduct} />;
  return <ProductEditLoader id={id} />;
}

function StockActions({
  productId,
  currentStock,
}: {
  productId: number;
  currentStock: number;
}) {
  return (
    <div className="mx-auto flex max-w-4xl items-center justify-between rounded-xl border border-border bg-muted/30 px-4 py-3">
      <div>
        <p className="text-sm font-medium">Stock actual</p>
        <p className="text-2xl font-semibold tabular-nums">{currentStock}</p>
      </div>
      <StockAdjustDialog productId={productId} currentStock={currentStock} />
    </div>
  );
}

function ProductEditLoader({ id }: { id: number }) {
  const existing = useProduct(id);
  if (existing.isLoading || !existing.data) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }
  return (
    <div className="space-y-4">
      {existing.data.track_inventory && (
        <StockActions
          productId={existing.data.id}
          currentStock={existing.data.stock ?? 0}
        />
      )}
      <ProductFormInner
        key={existing.data.id}
        id={id}
        initial={fromProduct(existing.data)}
      />
    </div>
  );
}

function ProductFormInner({
  id,
  initial,
}: {
  id?: number;
  initial: ProductInput;
}) {
  const router = useRouter();
  const isEdit = !!id;
  const create = useCreateProduct();
  const update = useUpdateProduct(id ?? 0);
  const mutation = isEdit ? update : create;

  const [form, setForm] = useState<ProductInput>(initial);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const set = <K extends keyof ProductInput>(k: K, v: ProductInput[K]) =>
    setForm((f) => ({ ...f, [k]: v }));

  const onSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setErrors({});
    mutation.mutate(form, {
      onSuccess: () => {
        toast.success(isEdit ? "Producto actualizado" : "Producto creado");
        router.push("/products");
      },
      onError: (err) => {
        setErrors(fieldErrors(err));
        toast.error(errMessage(err));
      },
    });
  };

  const isService = form.type === "service";

  return (
    <form onSubmit={onSubmit} className="mx-auto max-w-4xl pb-24">
      <Card className="px-6 py-2">
        <FormSection
          title="Identificación"
          description="Código, nombre y descripción del producto o servicio."
        >
          <Field
            label="Código principal"
            htmlFor="code"
            required
            error={errors.code?.[0]}
            hint="Código interno único."
          >
            <IconInput
              id="code"
              icon={Hash}
              placeholder="PROD-001"
              value={form.code}
              onChange={(e) => set("code", e.target.value)}
              required
            />
          </Field>

          <Field label="SKU / código auxiliar" htmlFor="sku">
            <IconInput
              id="sku"
              icon={Barcode}
              placeholder="7891234567890"
              value={form.sku ?? ""}
              onChange={(e) => set("sku", e.target.value)}
            />
          </Field>

          <Field
            label="Nombre"
            htmlFor="name"
            required
            className="sm:col-span-2"
            error={errors.name?.[0]}
          >
            <IconInput
              id="name"
              icon={Package}
              placeholder="Ej. Camiseta algodón talla M"
              value={form.name}
              onChange={(e) => set("name", e.target.value)}
              required
            />
          </Field>

          <Field label="Descripción" htmlFor="description" className="sm:col-span-2">
            <Input
              id="description"
              placeholder="Detalle opcional que aparece en el comprobante"
              value={form.description ?? ""}
              onChange={(e) => set("description", e.target.value)}
            />
          </Field>
        </FormSection>

        <FormSection
          title="Precio e impuesto"
          description="Valores que se usan al facturar."
        >
          <Field label="Tipo" required>
            <Select
              value={form.type}
              onValueChange={(v) => set("type", v as "product" | "service")}
            >
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="product">Bien</SelectItem>
                <SelectItem value="service">Servicio</SelectItem>
              </SelectContent>
            </Select>
          </Field>

          <Field label="IVA" required hint="Tarifa aplicada al emitir.">
            <Select
              value={String(form.tax_rate ?? 15)}
              onValueChange={(v) => set("tax_rate", Number(v))}
            >
              <SelectTrigger className="w-full">
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
          </Field>

          <Field
            label="Precio unitario"
            htmlFor="unit_price"
            required
            error={errors.unit_price?.[0]}
          >
            <IconInput
              id="unit_price"
              icon={DollarSign}
              type="number"
              step="0.01"
              min="0"
              value={form.unit_price}
              onChange={(e) => set("unit_price", Number(e.target.value) || 0)}
              required
            />
          </Field>

          <Field label="Costo" htmlFor="cost" hint="Para calcular tu margen.">
            <IconInput
              id="cost"
              icon={Tag}
              type="number"
              step="0.01"
              min="0"
              value={form.cost ?? 0}
              onChange={(e) => set("cost", Number(e.target.value) || 0)}
            />
          </Field>
        </FormSection>

        <FormSection
          title="Inventario"
          description={
            isService
              ? "Los servicios normalmente no controlan stock."
              : "Controla existencias y alertas de stock bajo."
          }
        >
          <Field label="Manejo de stock" className="sm:col-span-2">
            <Select
              value={form.track_inventory ? "1" : "0"}
              onValueChange={(v) => set("track_inventory", v === "1")}
            >
              <SelectTrigger className="w-full sm:max-w-xs">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="1">Sí, controlar stock</SelectItem>
                <SelectItem value="0">No (servicio o sin stock)</SelectItem>
              </SelectContent>
            </Select>
          </Field>

          <Field label="Stock inicial" htmlFor="stock">
            <IconInput
              id="stock"
              icon={Boxes}
              type="number"
              min="0"
              value={form.stock ?? 0}
              onChange={(e) => set("stock", Number(e.target.value) || 0)}
              disabled={!form.track_inventory}
            />
          </Field>

          <Field label="Stock mínimo" htmlFor="min_stock" hint="Alerta al bajar de aquí.">
            <IconInput
              id="min_stock"
              icon={Boxes}
              type="number"
              min="0"
              value={form.min_stock ?? 0}
              onChange={(e) => set("min_stock", Number(e.target.value) || 0)}
              disabled={!form.track_inventory}
            />
          </Field>
        </FormSection>
      </Card>

      {/* Barra de acción fija */}
      <div className="fixed inset-x-0 bottom-0 z-20 border-t border-border bg-background/85 backdrop-blur-md lg:left-64">
        <div className="mx-auto flex max-w-4xl items-center justify-between gap-3 px-6 py-3">
          <p className="hidden text-sm text-muted-foreground sm:block">
            {isEdit ? "Editando producto" : "Nuevo producto"}
          </p>
          <div className="flex flex-1 justify-end gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => router.back()}
            >
              Cancelar
            </Button>
            <Button type="submit" disabled={mutation.isPending}>
              {mutation.isPending && <Loader2 className="size-4 animate-spin" />}
              {isEdit ? "Guardar cambios" : "Crear producto"}
            </Button>
          </div>
        </div>
      </div>
    </form>
  );
}
