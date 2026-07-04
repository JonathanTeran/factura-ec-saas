"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import {
  Loader2,
  Hash,
  Building2,
  Mail,
  Phone,
  MapPin,
  UserRound,
  Search,
  Briefcase,
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
import { useSriIdentificationLookup } from "@/lib/api/queries/sri";
import {
  useCreateCustomer,
  useCustomer,
  useUpdateCustomer,
  type CustomerInput,
} from "@/lib/api/queries/customers";
import { ClientApiError } from "@/lib/api/client";
import type { Customer } from "@/lib/api/types";

const ID_TYPES = [
  { value: "04", label: "RUC" },
  { value: "05", label: "Cédula" },
  { value: "06", label: "Pasaporte" },
  { value: "07", label: "Consumidor final" },
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

const blankCustomer: CustomerInput = {
  identification_type: "05",
  identification_number: "",
  name: "",
  email: "",
  additional_emails: [],
  phone: "",
  address: "",
  economic_activity: "",
  is_active: true,
};

function fromCustomer(c: Customer): CustomerInput {
  return {
    identification_type: c.identification_type,
    identification_number: c.identification_number,
    name: c.name,
    email: c.email ?? "",
    additional_emails: c.additional_emails ?? [],
    phone: c.phone ?? "",
    address: c.address ?? "",
    economic_activity: c.economic_activity ?? "",
    is_active: c.is_active,
  };
}

function parseAdditionalEmails(text: string): string[] {
  return text
    .split(",")
    .map((e) => e.trim())
    .filter(Boolean);
}

export function CustomerForm({ id }: { id?: number }) {
  if (!id) return <CustomerFormInner initial={blankCustomer} />;
  return <CustomerEditLoader id={id} />;
}

function CustomerEditLoader({ id }: { id: number }) {
  const existing = useCustomer(id);
  if (existing.isLoading || !existing.data) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }
  return (
    <CustomerFormInner
      key={existing.data.id}
      id={id}
      initial={fromCustomer(existing.data)}
    />
  );
}

function CustomerFormInner({
  id,
  initial,
}: {
  id?: number;
  initial: CustomerInput;
}) {
  const router = useRouter();
  const isEdit = !!id;
  const create = useCreateCustomer();
  const update = useUpdateCustomer(id ?? 0);
  const mutation = isEdit ? update : create;

  const [form, setForm] = useState<CustomerInput>(initial);
  const [additionalEmailsText, setAdditionalEmailsText] = useState(
    (initial.additional_emails ?? []).join(", "),
  );
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const set = <K extends keyof CustomerInput>(k: K, v: CustomerInput[K]) =>
    setForm((f) => ({ ...f, [k]: v }));

  const sriLookup = useSriIdentificationLookup();

  async function lookupSri(opts: { silent?: boolean } = {}) {
    if (!/^([0-9]{10}|[0-9]{13})$/.test(form.identification_number)) {
      if (!opts.silent) {
        toast.error("Ingresa una cédula (10 dígitos) o RUC (13 dígitos) válidos.");
      }
      return;
    }
    try {
      const res = await sriLookup.mutateAsync(form.identification_number);
      const d = res.data;
      setForm((f) => ({
        ...f,
        name: f.name || d.business_name,
        address: f.address || d.address || f.address,
        economic_activity: f.economic_activity || d.main_activity || f.economic_activity,
      }));
      toast.success("Datos cargados automáticamente desde el SRI.");
    } catch {
      // Sin registro en el catastro (p. ej. cédula sin RUC): se ingresa manual
      if (!opts.silent) {
        toast.error("No se encontró esa identificación en el catastro del SRI.");
      }
    }
  }

  const onSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setErrors({});
    const payload: CustomerInput = {
      ...form,
      additional_emails: parseAdditionalEmails(additionalEmailsText),
    };
    mutation.mutate(payload, {
      onSuccess: () => {
        toast.success(isEdit ? "Cliente actualizado" : "Cliente creado");
        router.push("/customers");
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
          description="Cómo identifica el SRI a este cliente."
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
            htmlFor="identification_number"
            required
            error={errors.identification_number?.[0]}
            hint="Cédula (10) o RUC (13 dígitos). Se completan los datos desde el SRI."
          >
            <div className="flex gap-2">
              <IconInput
                id="identification_number"
                icon={Hash}
                inputMode="numeric"
                placeholder="1712345678"
                value={form.identification_number}
                onChange={(e) => set("identification_number", e.target.value)}
                onBlur={() => {
                  if (
                    ["04", "05"].includes(form.identification_type) &&
                    !form.name
                  ) {
                    void lookupSri({ silent: true });
                  }
                }}
                required
                className="flex-1"
              />
              <Button
                type="button"
                variant="outline"
                size="icon"
                onClick={() => void lookupSri()}
                disabled={sriLookup.isPending}
                title="Buscar en el SRI"
              >
                {sriLookup.isPending ? (
                  <Loader2 className="size-4 animate-spin" />
                ) : (
                  <Search className="size-4" />
                )}
              </Button>
            </div>
          </Field>

          <Field
            label="Razón social / Nombre"
            htmlFor="name"
            required
            className="sm:col-span-2"
            error={errors.name?.[0]}
          >
            <IconInput
              id="name"
              icon={form.identification_type === "04" ? Building2 : UserRound}
              placeholder="Ej. Comercial ABC S.A."
              value={form.name}
              onChange={(e) => set("name", e.target.value)}
              required
            />
          </Field>
        </FormSection>

        <FormSection
          title="Contacto"
          description="Para enviar comprobantes y notificaciones (opcional)."
        >
          <Field label="Correo" htmlFor="email" error={errors.email?.[0]}>
            <IconInput
              id="email"
              type="email"
              icon={Mail}
              placeholder="cliente@correo.com"
              value={form.email ?? ""}
              onChange={(e) => set("email", e.target.value)}
            />
          </Field>

          <Field
            label="Correos adicionales"
            htmlFor="additional_emails"
            className="sm:col-span-2"
            error={
              Object.entries(errors).find(([k]) =>
                k.startsWith("additional_emails"),
              )?.[1]?.[0]
            }
            hint="Separados por coma. Recibirán copia del comprobante."
          >
            <IconInput
              id="additional_emails"
              icon={Mail}
              placeholder="contabilidad@correo.com, gerencia@correo.com"
              value={additionalEmailsText}
              onChange={(e) => setAdditionalEmailsText(e.target.value)}
            />
          </Field>

          <Field label="Teléfono" htmlFor="phone" error={errors.phone?.[0]}>
            <IconInput
              id="phone"
              icon={Phone}
              inputMode="tel"
              placeholder="0991234567"
              value={form.phone ?? ""}
              onChange={(e) => set("phone", e.target.value)}
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
              placeholder="Av. Amazonas N34-45 y Av. Atahualpa"
              value={form.address ?? ""}
              onChange={(e) => set("address", e.target.value)}
            />
          </Field>

          <Field
            label="Actividad económica"
            htmlFor="economic_activity"
            className="sm:col-span-2"
            error={errors.economic_activity?.[0]}
            hint="Detectada automáticamente desde el SRI; puedes editarla."
          >
            <IconInput
              id="economic_activity"
              icon={Briefcase}
              placeholder="Ej. Venta al por menor de prendas de vestir"
              value={form.economic_activity ?? ""}
              onChange={(e) => set("economic_activity", e.target.value)}
            />
          </Field>
        </FormSection>
      </Card>

      {/* Barra de acción fija */}
      <div className="fixed inset-x-0 bottom-0 z-20 border-t border-border bg-background/85 backdrop-blur-md lg:left-64">
        <div className="mx-auto flex max-w-4xl items-center justify-between gap-3 px-6 py-3">
          <p className="hidden text-sm text-muted-foreground sm:block">
            {isEdit ? "Editando cliente" : "Nuevo cliente"}
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
              {isEdit ? "Guardar cambios" : "Crear cliente"}
            </Button>
          </div>
        </div>
      </div>
    </form>
  );
}
