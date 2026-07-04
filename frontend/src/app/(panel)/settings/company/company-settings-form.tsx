"use client";

import { useState } from "react";
import { Building2, Loader2 } from "lucide-react";
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
import { Field, IconInput } from "@/components/panel/form";
import {
  useCompanies,
  useCompanyDetail,
  useUpdateCompany,
  useUploadCompanyLogo,
  useDeleteCompanyLogo,
  type CompanyDetail,
  type CompanyUpdateInput,
} from "@/lib/api/queries/companies";
import { useRucLookup } from "@/lib/api/queries/onboarding";
import { ClientApiError } from "@/lib/api/client";
import { companyInitials } from "@/lib/format";

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

function LogoSection({ company }: { company: CompanyDetail }) {
  const upload = useUploadCompanyLogo(company.id);
  const remove = useDeleteCompanyLogo(company.id);
  const busy = upload.isPending || remove.isPending;

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Logo</CardTitle>
      </CardHeader>
      <CardContent className="flex flex-wrap items-center gap-4">
        {company.logo_url ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={company.logo_url}
            alt="Logo de la empresa"
            className="size-16 rounded-xl border border-border object-contain bg-white p-1"
          />
        ) : (
          <span className="grid size-16 shrink-0 place-items-center rounded-xl bg-primary text-xl font-semibold text-primary-foreground">
            {companyInitials(company.trade_name || company.business_name)}
          </span>
        )}
        <div className="min-w-0 flex-1">
          <p className="text-sm font-medium">
            {company.logo_url
              ? "Aparece en tus comprobantes (RIDE) y en el panel."
              : "Aún no subes un logo — mostramos las iniciales de tu empresa."}
          </p>
          <p className="text-xs text-muted-foreground">
            PNG, JPG o WebP · máximo 2 MB.
          </p>
        </div>
        <div className="flex gap-2">
          <label className="inline-flex h-9 cursor-pointer items-center gap-2 rounded-lg border border-input bg-card px-3.5 text-sm font-medium shadow-xs transition hover:bg-muted/50">
            {busy && <Loader2 className="size-4 animate-spin" />}
            {company.logo_url ? "Cambiar logo" : "Subir logo"}
            <input
              type="file"
              accept="image/png,image/jpeg,image/webp"
              className="hidden"
              disabled={busy}
              onChange={(e) => {
                const file = e.target.files?.[0];
                e.target.value = "";
                if (!file) return;
                upload.mutate(file, {
                  onSuccess: () => toast.success("Logo actualizado."),
                  onError: (err) => toast.error(errMessage(err)),
                });
              }}
            />
          </label>
          {company.logo_url && (
            <Button
              type="button"
              variant="ghost"
              disabled={busy}
              onClick={() =>
                remove.mutate(undefined, {
                  onSuccess: () => toast.success("Logo eliminado."),
                  onError: (err) => toast.error(errMessage(err)),
                })
              }
            >
              Quitar
            </Button>
          )}
        </div>
      </CardContent>
    </Card>
  );
}

export function CompanySettingsForm() {
  const companiesQ = useCompanies();
  const companyId = companiesQ.data?.[0]?.id ?? null;
  const detailQ = useCompanyDetail(companyId);

  if (companiesQ.isLoading || detailQ.isLoading) {
    return (
      <div className="flex justify-center py-24">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (!companyId || !detailQ.data) {
    return (
      <p className="text-sm text-muted-foreground">
        Aún no has configurado tu empresa. Completa el onboarding primero.
      </p>
    );
  }

  return (
    <FormInner
      key={detailQ.data.id}
      companyId={companyId}
      company={detailQ.data}
    />
  );
}

function FormInner({
  companyId,
  company,
}: {
  companyId: number;
  company: CompanyDetail;
}) {
  const update = useUpdateCompany(companyId);
  const rucLookup = useRucLookup();

  const [form, setForm] = useState<CompanyUpdateInput>({
    ruc: company.ruc,
    business_name: company.business_name,
    trade_name: company.trade_name ?? "",
    taxpayer_type: company.taxpayer_type,
    rimpe_type: company.rimpe_type ?? "none",
    address: company.address,
    special_taxpayer: company.is_special_taxpayer,
    special_taxpayer_number: company.special_taxpayer_number ?? "",
    retention_agent_number: company.retention_agent_number ?? "",
    obligated_accounting: company.is_accounting_required,
    sri_environment: company.sri_environment,
    email: company.email,
    phone: company.phone ?? "",
    sri_password: "",
  });
  const set = <K extends keyof CompanyUpdateInput>(
    k: K,
    v: CompanyUpdateInput[K],
  ) => setForm((f) => ({ ...f, [k]: v }));

  async function lookupSri() {
    if (!/^[0-9]{13}$/.test(form.ruc)) {
      toast.error("El RUC debe tener 13 dígitos numéricos.");
      return;
    }
    try {
      const res = await rucLookup.mutateAsync(form.ruc);
      const d = res.data;
      setForm((f) => ({
        ...f,
        business_name: d.business_name || f.business_name,
        taxpayer_type: d.taxpayer_type,
        obligated_accounting: d.obligated_accounting,
        special_taxpayer: d.special_taxpayer,
        rimpe_type:
          d.regime === "rimpe_emprendedor"
            ? "emprendedor"
            : d.regime === "rimpe_popular"
              ? "negocio_popular"
              : "none",
      }));
      if (d.status === "ACTIVO") {
        toast.success("Datos tributarios actualizados desde el SRI.");
      } else {
        toast.warning(`El SRI reporta este RUC como ${d.status}.`);
      }
    } catch {
      toast.error("No se pudo consultar el SRI. Intenta más tarde.");
    }
  }

  const onSave = () => {
    const payload: CompanyUpdateInput = {
      ...form,
      trade_name: form.trade_name || undefined,
      phone: form.phone || null,
      special_taxpayer_number: form.special_taxpayer
        ? form.special_taxpayer_number || null
        : null,
      retention_agent_number: form.retention_agent_number || null,
      sri_password: form.sri_password || undefined,
    };
    update.mutate(payload, {
      onSuccess: () => {
        toast.success("Datos del emisor actualizados.");
        set("sri_password", "");
      },
      onError: (e) => toast.error(errMessage(e)),
    });
  };

  return (
    <div className="max-w-3xl space-y-6">
      <LogoSection company={company} />

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Identificación</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2">
          <Field label="RUC" required htmlFor="ruc">
            <div className="flex gap-2">
              <IconInput
                id="ruc"
                icon={Building2}
                inputMode="numeric"
                value={form.ruc}
                onChange={(e) => set("ruc", e.target.value)}
                className="flex-1"
              />
              <Button
                type="button"
                variant="outline"
                onClick={lookupSri}
                disabled={rucLookup.isPending}
                className="shrink-0"
              >
                {rucLookup.isPending ? (
                  <Loader2 className="size-4 animate-spin" />
                ) : (
                  "Consultar SRI"
                )}
              </Button>
            </div>
          </Field>
          <Field label="Razón social" required htmlFor="bn">
            <Input
              id="bn"
              value={form.business_name}
              onChange={(e) => set("business_name", e.target.value)}
            />
          </Field>
          <Field label="Nombre comercial" htmlFor="tn">
            <Input
              id="tn"
              value={form.trade_name ?? ""}
              onChange={(e) => set("trade_name", e.target.value)}
            />
          </Field>
          <Field label="Correo del emisor" required htmlFor="em">
            <Input
              id="em"
              type="email"
              value={form.email}
              onChange={(e) => set("email", e.target.value)}
            />
          </Field>
          <Field label="Teléfono" htmlFor="ph">
            <Input
              id="ph"
              value={form.phone ?? ""}
              onChange={(e) => set("phone", e.target.value)}
            />
          </Field>
          <Field label="Dirección matriz" required htmlFor="addr">
            <Input
              id="addr"
              value={form.address}
              onChange={(e) => set("address", e.target.value)}
            />
          </Field>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Información tributaria</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Tipo de contribuyente" required>
              <Select
                value={form.taxpayer_type}
                onValueChange={(v) => set("taxpayer_type", v)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="natural">Persona natural</SelectItem>
                  <SelectItem value="juridical">Sociedad</SelectItem>
                </SelectContent>
              </Select>
            </Field>
            <Field label="Régimen">
              <Select
                value={form.rimpe_type ?? "none"}
                onValueChange={(v) => set("rimpe_type", v)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">Régimen general</SelectItem>
                  <SelectItem value="emprendedor">
                    RIMPE Emprendedor
                  </SelectItem>
                  <SelectItem value="negocio_popular">
                    RIMPE Negocio Popular
                  </SelectItem>
                </SelectContent>
              </Select>
            </Field>
          </div>

          <div className="flex items-center gap-2">
            <input
              type="checkbox"
              id="acc"
              checked={!!form.obligated_accounting}
              onChange={(e) => set("obligated_accounting", e.target.checked)}
              className="size-4 accent-primary"
            />
            <Label htmlFor="acc" className="font-normal">
              Obligado a llevar contabilidad
            </Label>
          </div>

          <div className="flex items-center gap-2">
            <input
              type="checkbox"
              id="special"
              checked={!!form.special_taxpayer}
              onChange={(e) => set("special_taxpayer", e.target.checked)}
              className="size-4 accent-primary"
            />
            <Label htmlFor="special" className="font-normal">
              Contribuyente especial
            </Label>
          </div>
          {form.special_taxpayer && (
            <Field label="No. de resolución (contribuyente especial)" htmlFor="stn">
              <Input
                id="stn"
                value={form.special_taxpayer_number ?? ""}
                onChange={(e) => set("special_taxpayer_number", e.target.value)}
              />
            </Field>
          )}

          <Field
            label="No. de resolución agente de retención"
            htmlFor="ran"
            hint="Déjalo vacío si no eres agente de retención."
          >
            <Input
              id="ran"
              value={form.retention_agent_number ?? ""}
              onChange={(e) => set("retention_agent_number", e.target.value)}
            />
          </Field>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">SRI</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2">
          <Field
            label="Ambiente"
            required
            hint={
              form.sri_environment === "2"
                ? "Tus comprobantes tienen validez tributaria."
                : "En Pruebas los comprobantes no tienen validez."
            }
          >
            <Select
              value={form.sri_environment}
              onValueChange={(v) => set("sri_environment", v)}
            >
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="1">Pruebas</SelectItem>
                <SelectItem value="2">Producción</SelectItem>
              </SelectContent>
            </Select>
          </Field>
          <Field
            label="Clave SRI"
            htmlFor="sripass"
            hint={
              company.has_sri_password
                ? "Ya hay una clave guardada; escribe solo si quieres reemplazarla."
                : "Necesaria para consultar comprobantes recibidos."
            }
          >
            <Input
              id="sripass"
              type="password"
              placeholder="••••••••"
              value={form.sri_password ?? ""}
              onChange={(e) => set("sri_password", e.target.value)}
              autoComplete="new-password"
            />
          </Field>
        </CardContent>
      </Card>

      <div className="flex justify-end">
        <Button onClick={onSave} disabled={update.isPending}>
          {update.isPending && <Loader2 className="size-4 animate-spin" />}
          Guardar cambios
        </Button>
      </div>
    </div>
  );
}
