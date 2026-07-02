"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import {
  Loader2,
  Hash,
  Building2,
  Store,
  Mail,
  Phone,
  MapPin,
  Map,
  StickyNote,
} from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Field, IconInput, FormSection } from "@/components/panel/form";
import {
  useCreateSupplier,
  useSupplier,
  useUpdateSupplier,
  type SupplierInput,
} from "@/lib/api/queries/suppliers";
import { ClientApiError } from "@/lib/api/client";
import type { Supplier } from "@/lib/api/types";

const ID_TYPES = [
  { value: "04", label: "RUC" },
  { value: "05", label: "Cédula" },
  { value: "06", label: "Pasaporte" },
  { value: "08", label: "Identif. exterior" },
];

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

const blank: SupplierInput = {
  identification_type: "04",
  identification: "",
  business_name: "",
  commercial_name: "",
  email: "",
  phone: "",
  address: "",
  city: "",
  is_withholding_agent: false,
  accounting_account: "",
  notes: "",
};

function fromSupplier(s: Supplier): SupplierInput {
  return {
    identification_type: s.identification_type,
    identification: s.identification,
    business_name: s.business_name,
    commercial_name: s.commercial_name ?? "",
    email: s.email ?? "",
    phone: s.phone ?? "",
    address: s.address ?? "",
    city: s.city ?? "",
    is_withholding_agent: !!s.is_withholding_agent,
    accounting_account: s.accounting_account ?? "",
    notes: s.notes ?? "",
  };
}

export function SupplierForm({ id }: { id?: number }) {
  if (!id) return <Inner initial={blank} />;
  return <Loader id={id} />;
}

function Loader({ id }: { id: number }) {
  const existing = useSupplier(id);
  if (existing.isLoading || !existing.data) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }
  return (
    <Inner
      key={existing.data.id}
      id={id}
      initial={fromSupplier(existing.data)}
    />
  );
}

function Inner({ id, initial }: { id?: number; initial: SupplierInput }) {
  const router = useRouter();
  const isEdit = !!id;
  const create = useCreateSupplier();
  const update = useUpdateSupplier(id ?? 0);
  const mutation = isEdit ? update : create;

  const [form, setForm] = useState<SupplierInput>(initial);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const set = <K extends keyof SupplierInput>(k: K, v: SupplierInput[K]) =>
    setForm((f) => ({ ...f, [k]: v }));

  const onSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setErrors({});
    mutation.mutate(form, {
      onSuccess: () => {
        toast.success(isEdit ? "Proveedor actualizado" : "Proveedor creado");
        router.push("/suppliers");
      },
      onError: (err) => {
        setErrors(fieldErrors(err));
        toast.error(errMessage(err));
      },
    });
  };

  return (
    <form onSubmit={onSubmit} className="mx-auto max-w-4xl pb-24">
      <Card className="px-6 py-2">
        <FormSection
          title="Identificación"
          description="Cómo identifica el SRI a este proveedor."
        >
          <Field label="Tipo de identificación" required>
            <Select
              value={form.identification_type}
              onValueChange={(v) => set("identification_type", v)}
            >
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {ID_TYPES.map((t) => (
                  <SelectItem key={t.value} value={t.value}>
                    {t.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </Field>

          <Field
            label="Número"
            htmlFor="identification"
            required
            error={errors.identification?.[0]}
            hint="Cédula (10) o RUC (13 dígitos)."
          >
            <IconInput
              id="identification"
              icon={Hash}
              inputMode="numeric"
              placeholder="1790012345001"
              value={form.identification}
              onChange={(e) => set("identification", e.target.value)}
              required
            />
          </Field>

          <Field
            label="Razón social"
            htmlFor="business_name"
            required
            className="sm:col-span-2"
            error={errors.business_name?.[0]}
          >
            <IconInput
              id="business_name"
              icon={Building2}
              placeholder="Ej. Distribuidora Andina Cía. Ltda."
              value={form.business_name}
              onChange={(e) => set("business_name", e.target.value)}
              required
            />
          </Field>

          <Field
            label="Nombre comercial"
            htmlFor="commercial_name"
            className="sm:col-span-2"
            error={errors.commercial_name?.[0]}
          >
            <IconInput
              id="commercial_name"
              icon={Store}
              placeholder="Ej. Distribuidora Andina"
              value={form.commercial_name ?? ""}
              onChange={(e) => set("commercial_name", e.target.value)}
            />
          </Field>
        </FormSection>

        <FormSection
          title="Contacto"
          description="Datos para comunicarte con el proveedor (opcional)."
        >
          <Field label="Correo" htmlFor="email" error={errors.email?.[0]}>
            <IconInput
              id="email"
              type="email"
              icon={Mail}
              placeholder="proveedor@correo.com"
              value={form.email ?? ""}
              onChange={(e) => set("email", e.target.value)}
            />
          </Field>

          <Field label="Teléfono" htmlFor="phone" error={errors.phone?.[0]}>
            <IconInput
              id="phone"
              icon={Phone}
              inputMode="tel"
              placeholder="022345678"
              value={form.phone ?? ""}
              onChange={(e) => set("phone", e.target.value)}
            />
          </Field>

          <Field label="Ciudad" htmlFor="city" error={errors.city?.[0]}>
            <IconInput
              id="city"
              icon={Map}
              placeholder="Quito"
              value={form.city ?? ""}
              onChange={(e) => set("city", e.target.value)}
            />
          </Field>

          <Field
            label="Dirección"
            htmlFor="address"
            className="sm:col-span-2"
            error={errors.address?.[0]}
          >
            <IconInput
              id="address"
              icon={MapPin}
              placeholder="Av. 10 de Agosto N24-120 y Colón"
              value={form.address ?? ""}
              onChange={(e) => set("address", e.target.value)}
            />
          </Field>
        </FormSection>

        <FormSection
          title="Datos adicionales"
          description="Retenciones y observaciones internas."
        >
          <Field
            label="Agente de retención"
            error={errors.is_withholding_agent?.[0]}
          >
            <Select
              value={form.is_withholding_agent ? "1" : "0"}
              onValueChange={(v) => set("is_withholding_agent", v === "1")}
            >
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="0">No</SelectItem>
                <SelectItem value="1">Sí</SelectItem>
              </SelectContent>
            </Select>
          </Field>

          <Field
            label="Notas"
            htmlFor="notes"
            className="sm:col-span-2"
            error={errors.notes?.[0]}
          >
            <IconInput
              id="notes"
              icon={StickyNote}
              placeholder="Ej. Entrega los días lunes, crédito 30 días"
              value={form.notes ?? ""}
              onChange={(e) => set("notes", e.target.value)}
            />
          </Field>
        </FormSection>
      </Card>

      {/* Barra de acción fija */}
      <div className="fixed inset-x-0 bottom-0 z-20 border-t border-border bg-background/85 backdrop-blur-md lg:left-64">
        <div className="mx-auto flex max-w-4xl items-center justify-between gap-3 px-6 py-3">
          <p className="hidden text-sm text-muted-foreground sm:block">
            {isEdit ? "Editando proveedor" : "Nuevo proveedor"}
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
              {mutation.isPending && (
                <Loader2 className="size-4 animate-spin" />
              )}
              {isEdit ? "Guardar cambios" : "Crear proveedor"}
            </Button>
          </div>
        </div>
      </div>
    </form>
  );
}
