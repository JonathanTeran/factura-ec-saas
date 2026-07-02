"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import {
  Receipt,
  Building2,
  ShieldCheck,
  Store,
  ListOrdered,
  PartyPopper,
  Loader2,
  ArrowRight,
  ArrowLeft,
  Check,
  Upload,
  FileCheck2,
  AlertTriangle,
  CalendarClock,
  Fingerprint,
  BadgeCheck,
  RotateCcw,
  LogOut,
} from "lucide-react";
import { formatDate } from "@/lib/format";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Field, IconInput } from "@/components/panel/form";
import {
  useOnboardingStatus,
  useSaveCompany,
  useUploadCertificate,
  useSaveEstablishment,
  useSaveSequentials,
  useCompleteOnboarding,
  useRucLookup,
  type CompanyInput,
  type CertificateInfo,
} from "@/lib/api/queries/onboarding";
import { logoutAction } from "@/app/(auth)/actions";

const STEPS = [
  { key: "company", label: "Empresa", icon: Building2 },
  { key: "certificate", label: "Firma", icon: ShieldCheck },
  { key: "establishment", label: "Establecimiento", icon: Store },
  { key: "sequentials", label: "Secuenciales", icon: ListOrdered },
  { key: "done", label: "Listo", icon: PartyPopper },
];

const DOC_TYPES = [
  { code: "01", label: "Factura" },
  { code: "04", label: "Nota de crédito" },
  { code: "05", label: "Nota de débito" },
  { code: "06", label: "Guía de remisión" },
  { code: "07", label: "Retención" },
  { code: "03", label: "Liquidación de compra" },
];

export function OnboardingWizard() {
  const router = useRouter();
  const [step, setStep] = useState(0);

  const [company, setCompany] = useState<CompanyInput>({
    ruc: "",
    business_name: "",
    trade_name: "",
    address: "",
    email: "",
    phone: "",
    taxpayer_type: "natural",
    obligated_accounting: false,
    sri_environment: "1",
  });
  const setC = <K extends keyof CompanyInput>(k: K, v: CompanyInput[K]) =>
    setCompany((c) => ({ ...c, [k]: v }));

  const [certFile, setCertFile] = useState<File | null>(null);
  const [certPassword, setCertPassword] = useState("");
  const [certInfo, setCertInfo] = useState<CertificateInfo | null>(null);

  const [est, setEst] = useState({
    branch_name: "Matriz",
    branch_code: "001",
    branch_address: "",
    ep_code: "001",
    ep_name: "Punto de emisión principal",
  });
  const setE = (k: keyof typeof est, v: string) =>
    setEst((e) => ({ ...e, [k]: v }));
  const [emissionPointId, setEmissionPointId] = useState<number | null>(null);

  const [migrated, setMigrated] = useState<boolean | null>(null);
  const [seq, setSeq] = useState<Record<string, string>>({});

  const saveCompany = useSaveCompany();
  const rucLookup = useRucLookup();
  const uploadCert = useUploadCertificate();

  const [sriLookupDone, setSriLookupDone] = useState(false);

  async function lookupRuc(silent = false) {
    if (!/^[0-9]{13}$/.test(company.ruc)) {
      if (!silent) toast.error("El RUC debe tener 13 dígitos numéricos.");
      return;
    }
    try {
      const res = await rucLookup.mutateAsync(company.ruc);
      const d = res.data;
      const main = d.establishments.find((e) => e.is_main);
      setCompany((c) => ({
        ...c,
        business_name: d.business_name || c.business_name,
        taxpayer_type: d.taxpayer_type === "natural" ? "natural" : "sociedad",
        obligated_accounting: d.obligated_accounting,
        rimpe_type:
          d.regime === "rimpe_emprendedor"
            ? "emprendedor"
            : d.regime === "rimpe_popular"
              ? "negocio_popular"
              : "none",
        trade_name: c.trade_name || main?.trade_name || "",
        address: c.address || main?.address || "",
      }));
      if (main) {
        // La matriz del SRI define el establecimiento principal
        setEst((e) => ({
          ...e,
          branch_code: main.code,
          branch_name: main.trade_name || e.branch_name,
          branch_address: main.address || e.branch_address,
        }));
      }
      setSriLookupDone(true);
      if (d.status === "ACTIVO") {
        toast.success("Datos cargados desde el SRI (RUC activo).");
      } else {
        toast.warning(`El SRI reporta este RUC como ${d.status}.`);
      }
    } catch {
      if (!silent) {
        toast.error(
          "No se pudo consultar el RUC en el SRI. Ingresa los datos manualmente.",
        );
      }
    }
  }
  const saveEst = useSaveEstablishment();
  const saveSeq = useSaveSequentials();
  const complete = useCompleteOnboarding();

  const busy =
    saveCompany.isPending ||
    uploadCert.isPending ||
    saveEst.isPending ||
    saveSeq.isPending ||
    complete.isPending;

  async function next() {
    try {
      if (step === 0) {
        if (!company.ruc || !company.business_name || !company.address || !company.email) {
          toast.error("Completa los campos obligatorios.");
          return;
        }
        await saveCompany.mutateAsync(company);
      } else if (step === 1) {
        if (!certInfo) {
          if (!certFile || !certPassword) {
            toast.error("Sube tu certificado e ingresa la contraseña.");
            return;
          }
          const res = await uploadCert.mutateAsync({
            file: certFile,
            password: certPassword,
          });
          setCertInfo(res.data);
          if (res.data.days_until_expiry <= 30) {
            toast.warning(
              `Tu firma vence en ${res.data.days_until_expiry} días.`,
            );
          } else {
            toast.success("Certificado validado");
          }
          return; // quédate para mostrar el resultado antes de avanzar
        }
      } else if (step === 2) {
        if (!est.branch_address) {
          toast.error("Ingresa la dirección del establecimiento.");
          return;
        }
        const res = await saveEst.mutateAsync({
          ...est,
          import_sri_establishments: sriLookupDone,
        });
        setEmissionPointId(res.data.emission_point.id);
        const imported = res.data.imported_branches?.length ?? 0;
        if (imported > 0) {
          toast.success(
            `Se importaron ${imported} sucursal(es) adicionales desde el SRI.`,
          );
        }
      } else if (step === 3) {
        if (migrated && emissionPointId) {
          const items = DOC_TYPES.map((d) => ({
            document_type: d.code,
            last_number: parseInt(seq[d.code] || "0", 10) || 0,
          })).filter((i) => i.last_number > 0);
          await saveSeq.mutateAsync({
            emission_point_id: emissionPointId,
            sequentials: items,
          });
        }
      }
      setStep((s) => Math.min(STEPS.length - 1, s + 1));
    } catch (e) {
      toast.error(e instanceof Error ? e.message : "Ocurrió un error.");
    }
  }

  async function finish() {
    try {
      await complete.mutateAsync();
      toast.success("¡Cuenta configurada!");
      router.push("/");
    } catch (e) {
      toast.error(e instanceof Error ? e.message : "Ocurrió un error.");
    }
  }

  const Icon = STEPS[step].icon;

  // Con la empresa ya creada se puede ir al panel; el semáforo del dashboard
  // guía lo que falte. Sin empresa aún, solo queda salir de la cuenta.
  const statusQ = useOnboardingStatus();
  const canExitToPanel = step > 0 || (statusQ.data?.has_company ?? false);

  return (
    <div className="mx-auto flex min-h-screen max-w-2xl flex-col px-4 py-8 lg:py-12">
      {/* Header */}
      <div className="mb-8">
        <div className="mb-6 flex items-center gap-2.5">
          <span className="grid size-8 place-items-center rounded-lg bg-primary text-primary-foreground">
            <Receipt className="size-4.5" />
          </span>
          <span className="font-semibold tracking-tight">AmePhia Facturación</span>
          <div className="ml-auto flex items-center gap-1">
            {canExitToPanel && (
              <Button
                type="button"
                variant="ghost"
                size="sm"
                className="text-muted-foreground"
                onClick={() => router.push("/")}
              >
                Completar más tarde
              </Button>
            )}
            <Button
              type="button"
              variant="ghost"
              size="sm"
              className="text-muted-foreground"
              onClick={() => void logoutAction()}
            >
              <LogOut className="size-4" /> Salir
            </Button>
          </div>
        </div>

        {/* Progress: círculos numerados con checkmarks */}
        <div className="flex items-start">
          {STEPS.map((s, i) => {
            const done = i < step;
            const active = i === step;
            return (
              <div
                key={s.key}
                className="flex flex-1 flex-col items-center last:flex-none"
              >
                <div className="flex w-full items-center">
                  <div
                    className={`h-0.5 flex-1 ${i === 0 ? "opacity-0" : i <= step ? "bg-primary" : "bg-border"}`}
                  />
                  <span
                    className={`grid size-8 shrink-0 place-items-center rounded-full border text-xs font-semibold transition ${
                      done
                        ? "border-primary bg-primary text-primary-foreground"
                        : active
                          ? "border-primary bg-primary/10 text-primary"
                          : "border-border bg-card text-muted-foreground"
                    }`}
                  >
                    {done ? <Check className="size-4" /> : i + 1}
                  </span>
                  <div
                    className={`h-0.5 flex-1 ${i === STEPS.length - 1 ? "opacity-0" : i < step ? "bg-primary" : "bg-border"}`}
                  />
                </div>
                <span
                  className={`mt-1.5 hidden text-[11px] sm:block ${
                    active ? "font-medium text-foreground" : "text-muted-foreground"
                  }`}
                >
                  {s.label}
                </span>
              </div>
            );
          })}
        </div>
        <p className="mt-3 text-sm text-muted-foreground sm:hidden">
          Paso {step + 1} de {STEPS.length}: <span className="font-medium text-foreground">{STEPS[step].label}</span>
        </p>
      </div>

      {/* Step content */}
      <div className="flex-1">
        <div className="mb-6 flex items-start gap-3">
          <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-accent text-accent-foreground">
            <Icon className="size-5" />
          </span>
          <div>
            <h1 className="text-xl font-semibold tracking-tight">
              {step === 0 && "Datos de tu empresa"}
              {step === 1 && "Firma electrónica"}
              {step === 2 && "Establecimiento y punto de emisión"}
              {step === 3 && "¿Ya facturabas antes?"}
              {step === 4 && "¡Todo listo!"}
            </h1>
            <p className="mt-0.5 text-sm text-muted-foreground">
              {step === 0 && "Los datos que el SRI usa para identificar tus comprobantes."}
              {step === 1 && "Sube tu certificado (.p12). Puedes configurarla más tarde si no la tienes a mano."}
              {step === 2 && "Tu matriz y el punto desde donde emitirás."}
              {step === 3 && "Si ya emitías en otra plataforma, continuamos tus secuenciales."}
              {step === 4 && "Tu cuenta está configurada y lista para facturar."}
            </p>
          </div>
        </div>

        <div className="rounded-2xl border border-border bg-card p-5 sm:p-6">
          {step === 0 && (
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="RUC" required htmlFor="ruc">
                <div className="flex gap-2">
                  <IconInput
                    id="ruc"
                    icon={Building2}
                    inputMode="numeric"
                    placeholder="1790012345001"
                    value={company.ruc}
                    onChange={(e) => setC("ruc", e.target.value)}
                    onBlur={() => {
                      if (!sriLookupDone) void lookupRuc(true);
                    }}
                    className="flex-1"
                  />
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => lookupRuc()}
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
              <Field label="Razón social" required htmlFor="bn" className="sm:col-span-1">
                <Input
                  id="bn"
                  placeholder="Comercial ABC S.A."
                  value={company.business_name}
                  onChange={(e) => setC("business_name", e.target.value)}
                />
              </Field>
              <Field label="Nombre comercial" htmlFor="tn">
                <Input
                  id="tn"
                  placeholder="ABC"
                  value={company.trade_name ?? ""}
                  onChange={(e) => setC("trade_name", e.target.value)}
                />
              </Field>
              <Field label="Correo" required htmlFor="em">
                <Input
                  id="em"
                  type="email"
                  placeholder="empresa@correo.com"
                  value={company.email}
                  onChange={(e) => setC("email", e.target.value)}
                />
              </Field>
              <Field label="Dirección matriz" required htmlFor="addr" className="sm:col-span-2">
                <Input
                  id="addr"
                  placeholder="Av. Amazonas N34-45 y Av. Atahualpa"
                  value={company.address}
                  onChange={(e) => setC("address", e.target.value)}
                />
              </Field>
              <Field label="Tipo de contribuyente" required>
                <Select
                  value={company.taxpayer_type}
                  onValueChange={(v) => setC("taxpayer_type", v)}
                >
                  <SelectTrigger className="w-full">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="natural">Persona natural</SelectItem>
                    <SelectItem value="sociedad">Sociedad</SelectItem>
                  </SelectContent>
                </Select>
              </Field>
              <Field label="Ambiente SRI" required hint="Usa Pruebas hasta validar tu emisión.">
                <Select
                  value={company.sri_environment}
                  onValueChange={(v) => setC("sri_environment", v)}
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
            </div>
          )}

          {step === 1 && (
            <div className="space-y-4">
              {certInfo ? (
                <div className="space-y-4">
                  {(() => {
                    const d = certInfo.days_until_expiry;
                    const warn = d <= 30;
                    return (
                      <div
                        className={`flex items-start gap-3 rounded-xl border p-4 ${
                          warn
                            ? "border-warning/40 bg-warning/5"
                            : "border-success/30 bg-success/5"
                        }`}
                      >
                        {warn ? (
                          <AlertTriangle className="mt-0.5 size-5 shrink-0 text-warning" />
                        ) : (
                          <FileCheck2 className="mt-0.5 size-5 shrink-0 text-success" />
                        )}
                        <div className="text-sm">
                          <p className="font-medium text-foreground">
                            {warn
                              ? `Tu firma vence en ${d} día${d === 1 ? "" : "s"}`
                              : "Certificado válido"}
                          </p>
                          <p className="text-muted-foreground">
                            {warn
                              ? "Podrás continuar, pero renuévala pronto para no interrumpir tu facturación."
                              : `Vigente por ${d} días más.`}
                          </p>
                        </div>
                      </div>
                    );
                  })()}

                  <dl className="divide-y divide-border rounded-xl border border-border">
                    <CertRow icon={BadgeCheck} label="Titular" value={certInfo.signature_subject} />
                    {certInfo.signature_identification && (
                      <CertRow icon={Fingerprint} label="Identificación" value={certInfo.signature_identification} mono />
                    )}
                    <CertRow icon={ShieldCheck} label="Emisor" value={certInfo.signature_issuer} />
                    {certInfo.signature_valid_from && (
                      <CertRow icon={CalendarClock} label="Válido desde" value={formatDate(certInfo.signature_valid_from)} />
                    )}
                    <CertRow
                      icon={CalendarClock}
                      label="Válido hasta"
                      value={formatDate(certInfo.signature_expires_at)}
                    />
                  </dl>

                  <button
                    type="button"
                    onClick={() => {
                      setCertInfo(null);
                      setCertFile(null);
                      setCertPassword("");
                    }}
                    className="inline-flex items-center gap-1.5 text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                  >
                    <RotateCcw className="size-3.5" />
                    Subir otro certificado
                  </button>
                </div>
              ) : (
                <>
                  <Field label="Certificado (.p12 / .pfx)" required>
                    <label className="flex cursor-pointer items-center gap-3 rounded-lg border border-dashed border-input bg-card px-4 py-4 text-sm transition hover:border-primary/40">
                      <Upload className="size-5 text-muted-foreground" />
                      <span className={certFile ? "font-medium" : "text-muted-foreground"}>
                        {certFile ? certFile.name : "Selecciona tu archivo de firma"}
                      </span>
                      <input
                        type="file"
                        accept=".p12,.pfx"
                        className="hidden"
                        onChange={(e) => setCertFile(e.target.files?.[0] ?? null)}
                      />
                    </label>
                  </Field>
                  <Field label="Contraseña del certificado" required htmlFor="cp">
                    <Input
                      id="cp"
                      type="password"
                      placeholder="••••••••"
                      value={certPassword}
                      onChange={(e) => setCertPassword(e.target.value)}
                    />
                  </Field>
                </>
              )}
            </div>
          )}

          {step === 2 && (
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Nombre del establecimiento" htmlFor="brn">
                <Input id="brn" value={est.branch_name} onChange={(e) => setE("branch_name", e.target.value)} />
              </Field>
              <Field label="Código establecimiento" htmlFor="brc" hint="Normalmente 001.">
                <Input id="brc" value={est.branch_code} onChange={(e) => setE("branch_code", e.target.value)} />
              </Field>
              <Field label="Dirección del establecimiento" required htmlFor="bra" className="sm:col-span-2">
                <Input id="bra" placeholder="Av. Amazonas N34-45" value={est.branch_address} onChange={(e) => setE("branch_address", e.target.value)} />
              </Field>
              <Field label="Punto de emisión" htmlFor="epn">
                <Input id="epn" value={est.ep_name} onChange={(e) => setE("ep_name", e.target.value)} />
              </Field>
              <Field label="Código punto de emisión" htmlFor="epc" hint="Normalmente 001.">
                <Input id="epc" value={est.ep_code} onChange={(e) => setE("ep_code", e.target.value)} />
              </Field>
            </div>
          )}

          {step === 3 && (
            <div className="space-y-5">
              <div className="grid grid-cols-2 gap-3">
                <button
                  type="button"
                  onClick={() => setMigrated(false)}
                  className={`rounded-xl border p-4 text-left transition ${
                    migrated === false
                      ? "border-primary bg-accent"
                      : "border-border hover:border-primary/40"
                  }`}
                >
                  <p className="font-medium">Es mi primera vez</p>
                  <p className="mt-1 text-xs text-muted-foreground">
                    Empezamos en 000000001.
                  </p>
                </button>
                <button
                  type="button"
                  onClick={() => setMigrated(true)}
                  className={`rounded-xl border p-4 text-left transition ${
                    migrated === true
                      ? "border-primary bg-accent"
                      : "border-border hover:border-primary/40"
                  }`}
                >
                  <p className="font-medium">Ya facturaba antes</p>
                  <p className="mt-1 text-xs text-muted-foreground">
                    Vengo de Ecuafact, Contífico, Facturero u otro sistema.
                  </p>
                </button>
              </div>

              {migrated && (
                <div className="space-y-3 rounded-xl border border-border bg-muted/30 p-4">
                  <p className="text-sm text-muted-foreground">
                    Ingresa el <strong>último número emitido</strong> en tu
                    sistema anterior por cada tipo de comprobante (déjalo vacío
                    si no lo usabas). Lo encuentras en tu último comprobante:
                    son los <strong>últimos 9 dígitos</strong> del número, p.
                    ej. de {est.branch_code}-{est.ep_code}-000001234 ingresa
                    1234.
                  </p>
                  {DOC_TYPES.map((d) => {
                    const last = parseInt(seq[d.code] || "", 10);
                    const next =
                      Number.isFinite(last) && last > 0
                        ? `${est.branch_code}-${est.ep_code}-${String(last + 1).padStart(9, "0")}`
                        : null;
                    return (
                      <div key={d.code} className="flex items-center gap-3">
                        <span className="w-40 shrink-0 text-sm">{d.label}</span>
                        <Input
                          inputMode="numeric"
                          placeholder="Ej. 1234"
                          value={seq[d.code] ?? ""}
                          onChange={(e) =>
                            setSeq((s) => ({ ...s, [d.code]: e.target.value }))
                          }
                          className="font-mono"
                        />
                        <span className="hidden w-56 shrink-0 text-xs text-muted-foreground sm:block">
                          {next ? (
                            <>
                              siguiente:{" "}
                              <span className="font-mono">{next}</span>
                            </>
                          ) : (
                            ""
                          )}
                        </span>
                      </div>
                    );
                  })}
                  <p className="text-xs text-muted-foreground">
                    Así tu numeración continúa sin saltos ni duplicados ante el
                    SRI — nadie notará el cambio de sistema.
                  </p>
                </div>
              )}
            </div>
          )}

          {step === 4 && (
            <div className="flex flex-col items-center gap-4 py-6 text-center">
              <span className="grid size-14 place-items-center rounded-2xl bg-success/10 text-success">
                <Check className="size-7" />
              </span>
              <div>
                <p className="font-medium">Tu cuenta quedó configurada.</p>
                <p className="mt-1 text-sm text-muted-foreground">
                  Ya puedes emitir tu primera factura electrónica.
                </p>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Footer nav */}
      <div className="mt-6 flex items-center justify-between">
        <Button
          variant="ghost"
          onClick={() => setStep((s) => Math.max(0, s - 1))}
          disabled={step === 0 || busy}
        >
          <ArrowLeft className="size-4" />
          Atrás
        </Button>

        {step < STEPS.length - 1 ? (
          <div className="flex items-center gap-2">
            {step === 1 && !certInfo && (
              <Button
                variant="ghost"
                onClick={() => setStep((s) => s + 1)}
                disabled={busy}
              >
                Configurar más tarde
              </Button>
            )}
            <Button onClick={next} disabled={busy || (step === 3 && migrated === null)}>
              {busy ? (
                <Loader2 className="size-4 animate-spin" />
              ) : (
                <>
                  {step === 1 && !certInfo ? "Validar certificado" : "Continuar"}
                  <ArrowRight className="size-4" />
                </>
              )}
            </Button>
          </div>
        ) : (
          <Button onClick={finish} disabled={busy}>
            {busy ? <Loader2 className="size-4 animate-spin" /> : "Ir al panel"}
            <ArrowRight className="size-4" />
          </Button>
        )}
      </div>
    </div>
  );
}

function CertRow({
  icon: Icon,
  label,
  value,
  mono,
}: {
  icon: typeof BadgeCheck;
  label: string;
  value: string;
  mono?: boolean;
}) {
  return (
    <div className="flex items-center gap-3 px-4 py-2.5">
      <Icon className="size-4 shrink-0 text-muted-foreground" />
      <span className="w-32 shrink-0 text-sm text-muted-foreground">
        {label}
      </span>
      <span
        className={`min-w-0 flex-1 truncate text-sm font-medium ${mono ? "font-mono" : ""}`}
      >
        {value}
      </span>
    </div>
  );
}
