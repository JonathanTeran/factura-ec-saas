"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { EntityCombobox } from "@/components/forms/entity-combobox";
import {
  useCategories,
  useCategory,
  useCreateCategory,
  useUpdateCategory,
  type CategoryInput,
} from "@/lib/api/queries/categories";
import { ClientApiError } from "@/lib/api/client";
import type { Category } from "@/lib/api/types";

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

const blankCategory: CategoryInput = {
  name: "",
  parent_id: null,
  description: "",
  color: "",
  icon: "",
  sort_order: 0,
  is_active: true,
};

function fromCategory(c: Category): CategoryInput {
  return {
    name: c.name,
    parent_id: c.parent_id,
    description: c.description ?? "",
    color: c.color ?? "",
    icon: c.icon ?? "",
    sort_order: c.sort_order,
    is_active: c.is_active,
  };
}

export function CategoryForm({ id }: { id?: number }) {
  if (!id) return <CategoryFormInner initial={blankCategory} />;
  return <CategoryEditLoader id={id} />;
}

function CategoryEditLoader({ id }: { id: number }) {
  const existing = useCategory(id);
  if (existing.isLoading || !existing.data) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }
  return (
    <CategoryFormInner
      key={existing.data.id}
      id={id}
      initial={fromCategory(existing.data)}
    />
  );
}

function CategoryFormInner({
  id,
  initial,
}: {
  id?: number;
  initial: CategoryInput;
}) {
  const router = useRouter();
  const isEdit = !!id;
  const create = useCreateCategory();
  const update = useUpdateCategory(id ?? 0);
  const mutation = isEdit ? update : create;

  const [form, setForm] = useState<CategoryInput>(initial);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [parentSearch, setParentSearch] = useState("");

  const parentsQ = useCategories({ search: parentSearch || undefined, per_page: 20 });

  const onSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setErrors({});
    mutation.mutate(form, {
      onSuccess: () => {
        toast.success(isEdit ? "Categoría actualizada" : "Categoría creada");
        router.push("/categories");
      },
      onError: (err) => {
        setErrors(fieldErrors(err));
        toast.error(errMessage(err));
      },
    });
  };

  const parentOptions =
    parentsQ.data?.data
      .filter((c) => c.id !== id)
      .map((c) => ({
        value: c.id,
        label: c.name,
        description: c.full_path,
      })) ?? [];

  return (
    <form onSubmit={onSubmit} className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle className="text-base">Datos de la categoría</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2">
          <div className="space-y-2 sm:col-span-2">
            <Label htmlFor="name">Nombre</Label>
            <Input
              id="name"
              value={form.name}
              onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
              required
            />
            <FieldError errors={errors.name} />
          </div>

          <div className="space-y-2 sm:col-span-2">
            <Label>Categoría padre (opcional)</Label>
            <EntityCombobox
              value={form.parent_id ?? null}
              onChange={(v) =>
                setForm((f) => ({
                  ...f,
                  parent_id: typeof v === "number" ? v : null,
                }))
              }
              options={parentOptions}
              isLoading={parentsQ.isFetching}
              onSearch={setParentSearch}
              placeholder="Sin padre (raíz)"
              searchPlaceholder="Buscar categoría..."
            />
          </div>

          <div className="space-y-2 sm:col-span-2">
            <Label htmlFor="description">Descripción</Label>
            <Input
              id="description"
              value={form.description ?? ""}
              onChange={(e) =>
                setForm((f) => ({ ...f, description: e.target.value }))
              }
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="color">Color (#RRGGBB)</Label>
            <Input
              id="color"
              value={form.color ?? ""}
              onChange={(e) => setForm((f) => ({ ...f, color: e.target.value }))}
              placeholder="#3b82f6"
            />
            <FieldError errors={errors.color} />
          </div>

          <div className="space-y-2">
            <Label htmlFor="sort_order">Orden</Label>
            <Input
              id="sort_order"
              type="number"
              min="0"
              value={form.sort_order ?? 0}
              onChange={(e) =>
                setForm((f) => ({
                  ...f,
                  sort_order: Number(e.target.value) || 0,
                }))
              }
            />
          </div>
        </CardContent>
      </Card>

      <div className="flex justify-end gap-2">
        <Button type="button" variant="outline" onClick={() => router.back()}>
          Cancelar
        </Button>
        <Button type="submit" disabled={mutation.isPending}>
          {mutation.isPending && <Loader2 className="size-4 animate-spin" />}
          {isEdit ? "Guardar cambios" : "Crear categoría"}
        </Button>
      </div>
    </form>
  );
}

function FieldError({ errors }: { errors?: string[] }) {
  if (!errors?.length) return null;
  return <p className="text-xs text-destructive">{errors[0]}</p>;
}
