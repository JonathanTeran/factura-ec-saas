"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { EntityCombobox } from "@/components/forms/entity-combobox";
import {
  useAccount,
  useAccounts,
  useCreateAccount,
  useUpdateAccount,
  type AccountInput,
} from "@/lib/api/queries/accounting";
import { ClientApiError } from "@/lib/api/client";
import type { AccountingAccount } from "@/lib/api/types";

const TYPES = [
  { value: "activo", label: "Activo" },
  { value: "pasivo", label: "Pasivo" },
  { value: "patrimonio", label: "Patrimonio" },
  { value: "ingreso", label: "Ingreso" },
  { value: "costo", label: "Costo" },
  { value: "gasto", label: "Gasto" },
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

const blank: AccountInput = {
  code: "",
  name: "",
  account_type: "activo",
  account_nature: "debit",
  parent_id: null,
  allows_movement: true,
  description: "",
};

function fromAccount(a: AccountingAccount): AccountInput {
  return {
    code: a.code,
    name: a.name,
    account_type: a.account_type,
    account_nature: a.account_nature,
    parent_id: a.parent_id,
    allows_movement: a.allows_movement,
    tax_form_code: a.tax_form_code ?? "",
    description: a.description ?? "",
  };
}

export function AccountForm({ id }: { id?: number }) {
  if (!id) return <Inner initial={blank} />;
  return <Loader id={id} />;
}

function Loader({ id }: { id: number }) {
  const existing = useAccount(id);
  if (existing.isLoading || !existing.data) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }
  return <Inner key={existing.data.id} id={id} initial={fromAccount(existing.data)} />;
}

function Inner({ id, initial }: { id?: number; initial: AccountInput }) {
  const router = useRouter();
  const isEdit = !!id;
  const create = useCreateAccount();
  const update = useUpdateAccount(id ?? 0);
  const mutation = isEdit ? update : create;

  const [form, setForm] = useState<AccountInput>(initial);
  const [parentSearch, setParentSearch] = useState("");
  const parentsQ = useAccounts({ search: parentSearch || undefined, per_page: 50 });

  const onSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    mutation.mutate(form, {
      onSuccess: () => {
        toast.success(isEdit ? "Cuenta actualizada" : "Cuenta creada");
        router.push("/accounting/accounts");
      },
      onError: (e) => toast.error(errMessage(e)),
    });
  };

  return (
    <form onSubmit={onSubmit} className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle className="text-base">Datos de la cuenta</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2">
          <div className="space-y-2">
            <Label htmlFor="code">Código</Label>
            <Input
              id="code"
              value={form.code}
              onChange={(e) => setForm((f) => ({ ...f, code: e.target.value }))}
              required
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="name">Nombre</Label>
            <Input
              id="name"
              value={form.name}
              onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
              required
            />
          </div>
          <div className="space-y-2">
            <Label>Tipo</Label>
            <Select
              value={form.account_type}
              onValueChange={(v) =>
                setForm((f) => ({
                  ...f,
                  account_type: v as AccountingAccount["account_type"],
                }))
              }
            >
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {TYPES.map((t) => (
                  <SelectItem key={t.value} value={t.value}>
                    {t.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-2">
            <Label>Naturaleza</Label>
            <Select
              value={form.account_nature}
              onValueChange={(v) =>
                setForm((f) => ({
                  ...f,
                  account_nature: v as "debit" | "credit",
                }))
              }
            >
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="debit">Deudora</SelectItem>
                <SelectItem value="credit">Acreedora</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-2 sm:col-span-2">
            <Label>Cuenta padre (opcional)</Label>
            <EntityCombobox
              value={form.parent_id ?? null}
              onChange={(v) =>
                setForm((f) => ({
                  ...f,
                  parent_id: typeof v === "number" ? v : null,
                }))
              }
              options={
                parentsQ.data?.data
                  .filter((a) => a.id !== id)
                  .map((a) => ({
                    value: a.id,
                    label: `${a.code} · ${a.name}`,
                    description: a.account_type,
                  })) ?? []
              }
              isLoading={parentsQ.isFetching}
              onSearch={setParentSearch}
              placeholder="Sin padre (cuenta raíz)"
              searchPlaceholder="Buscar cuenta..."
            />
          </div>
          <div className="space-y-2">
            <Label>Permite movimientos</Label>
            <Select
              value={form.allows_movement ? "1" : "0"}
              onValueChange={(v) =>
                setForm((f) => ({ ...f, allows_movement: v === "1" }))
              }
            >
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="1">Sí (cuenta de detalle)</SelectItem>
                <SelectItem value="0">No (solo agrupadora)</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-2">
            <Label htmlFor="tax-code">Código formulario tributario</Label>
            <Input
              id="tax-code"
              value={form.tax_form_code ?? ""}
              onChange={(e) =>
                setForm((f) => ({ ...f, tax_form_code: e.target.value }))
              }
              placeholder="ej: 503, 511..."
              maxLength={10}
            />
          </div>
          <div className="space-y-2 sm:col-span-2">
            <Label htmlFor="desc">Descripción</Label>
            <Input
              id="desc"
              value={form.description ?? ""}
              onChange={(e) =>
                setForm((f) => ({ ...f, description: e.target.value }))
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
          {isEdit ? "Guardar cambios" : "Crear cuenta"}
        </Button>
      </div>
    </form>
  );
}
