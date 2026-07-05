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
  {
    key: "company",
    label: "Empresa",
    icon: Building2,
    desc: "Datos fiscales del emisor",
  },
  {
    key: "certificate",
    label: "Firma electrónica",
    icon: ShieldCheck,
    desc: "Tu certificado .p12",
  },
  {
    key: "establishment",
    label: "Establecimiento",
    icon: Store,
    desc: "Matriz y punto de emisión",
  },
  {
    key: "sequentials",
    label: "Secuenciales",
    icon: ListOrdered,
    desc: "Continuidad de numeración",
  },
  { key: "done", label: "Listo", icon: PartyPopper, desc: "A facturar" },
];

const DOC_TYPES = [
  { code: "01", label: "Factura" },
  { code: "04", label: "Nota de crédito" },
  { code: "05", label: "Nota de débito" },
  { code: "06", label: "Guía de remisión" },
  { code: "07", label: "Retención" },
  { code: "03", label: "Liquidación de compra" },
];

const STEP_TITLES = [
  "Datos de tu empresa",
  "Firma electrónica",
  "Establecimiento y punto de emisión",
  "¿Ya facturabas antes?",
  "¡Todo listo!",
];

const STEP_SUBTITLES = [
  "Los datos que el SRI usa para identificar tus comprobantes.",
  "Sube tu certificado (.p12). Puedes configurarla más tarde si no la tienes a mano.",
  "Tu matriz y el punto desde donde emitirás.",
  "Si ya emitías en otra plataforma, continuamos tus secuenciales.",
  "Tu cuenta está configurada y lista para facturar.",
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

  const progressPct = (step / (STEPS.length - 1)) * 100;

  return (
    <div className="grid min-h-screen lg:grid-cols-[minmax(300px,340px)_1fr]">
      {/* ── Riel lateral de marca (desktop) ─────────────────────────── */}
      <aside className="relative hidden overflow-hidden bg-sidebar text-sidebar-foreground lg:flex lg:flex-col lg:justify-between lg:px-9 lg:py-10">
        {/* Atmósfera */}
        <div
          aria-hidden
          className="pointer-events-none absolute inset-0"
          style={{
            background:
              "radial-gradient(120% 90% at 10% 0%, hsl(172 66% 42% / 0.32), transparent 55%), radial-gradient(90% 70% at 100% 100%, hsl(199 89% 48% / 0.20), transparent 50%)",
          }}
        />
        <div
          aria-hidden
          className="pointer-events-none absolute inset-0 opacity-[0.13]"
          style={{
            backgroundImage:
              "radial-gradient(hsl(210 40% 96% / 0.5) 1px, transparent 1px)",
            backgroundSize: "22px 22px",
            maskImage: "linear-gradient(to bottom, black, transparent 88%)",
          }}
        />

        <div className="relative">
          <div className="flex items-center gap-2.5 text-lg font-semibold tracking-tight">
            <span className="grid size-9 place-items-center rounded-xl bg-sidebar-primary text-sidebar-primary-foreground shadow-lg shadow-black/20">
              <Receipt className="size-5" />
            </span>
            AmePhia Facturación
          </div>
          <p className="mt-8 text-xs font-semibold uppercase tracking-widest text-sidebar-foreground/40">
            Configuración inicial
          </p>
          <h2 className="mt-1.5 text-xl font-semibold tracking-tight">
            Dejá tu cuenta lista para facturar
          </h2>

          {/* Stepper vertical */}
          <ol className="mt-8 space-y-1">
            {STEPS.map((s, i) => {
              const done = i < step;
              const active = i === step;
              const StepIcon = s.icon;
              return (
                <li key={s.key} className="relative flex gap-3.5">
                  {/* Conector */}
                  {i < STEPS.length - 1 && (
                    <span
                      aria-hidden
                      className={`absolute left-[15px] top-8 h-[calc(100%-1rem)] w-px ${
                        done ? "bg-sidebar-primary/60" : "bg-white/10"
                      }`}
                    />
                  )}
                  <span
                    className={`relative z-10 grid size-8 shrink-0 place-items-center rounded-full border transition ${
                      done
                        ? "border-sidebar-primary bg-sidebar-primary text-sidebar-primary-foreground"
                        : active
                          ? "border-sidebar-primary bg-sidebar-primary/15 text-sidebar-primary ring-4 ring-sidebar-primary/10"
                          : "border-white/15 bg-white/[0.03] text-sidebar-foreground/40"
                    }`}
                  >
                    {done ? (
                      <Check className="size-4" />
                    ) : (
                      <StepIcon className="size-4" />
                    )}
                  </span>
                  <div className={`pb-6 pt-1 transition ${active ? "" : "opacity-70"}`}>
                    <p
                      className={`text-sm font-medium leading-none ${
                        active
                          ? "text-sidebar-foreground"
                          : done
                            ? "text-sidebar-foreground/80"
                            : "text-sidebar-foreground/55"
                      }`}
                    >
                      {s.label}
                    </p>
                    <p className="mt-1 text-xs text-sidebar-foreground/45">
                      {s.desc}
                    </p>
                  </div>
                </li>
              );
            })}
          </ol>
        </div>

        <div className="relative flex items-center gap-2 text-xs text-sidebar-foreground/50">
          <ShieldCheck className="size-3.5" />
          Firmamos con tu certificado. Nunca lo compartimos.
        </div>
      </aside>

      {/* ── Panel del formulario ────────────────────────────────────── */}
      <main className="flex min-w-0 flex-col bg-background">
        {/* Barra superior */}
        <header className="flex items-center gap-2 border-b border-border px-4 py-3.5 sm:px-8">
          {/* Logo solo en móvil */}
          <div className="flex items-center gap-2 lg:hidden">
            <span className="grid size-8 place-items-center rounded-lg bg-primary text-primary-foreground">
              <Receipt className="size-4.5" />
            </span>
            <span className="text-sm font-semibold tracking-tight">
              AmePhia
            </span>
          </div>
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
        </header>

        {/* Barra de progreso móvil */}
        <div className="lg:hidden">
          <div className="h-1 w-full bg-muted">
            <div
              className="h-full bg-primary transition-all duration-500"
              style={{ width: `${Math.max(progressPct, 6)}%` }}
            />
          </div>
          <p className="px-4 pt-3 text-xs text-muted-foreground sm:px-8">
            Paso {step + 1} de {STEPS.length} ·{" "}
            <span className="font-medium text-foreground">
              {STEPS[step].label}
            </span>
          </p>
        </div>

        {/* Contenido */}
        <div className="mx-auto w-full max-w-2xl flex-1 px-4 py-8 sm:px-8 lg:py-12">
          <div className="mb-7 flex items-start gap-3.5">
            <span className="grid size-12 shrink-0 place-items-center rounded-2xl bg-primary/10 text-primary ring-1 ring-primary/15">
              <Icon className="size-5.5" />
            </span>
            <div className="pt-0.5">
              <h1 className="text-2xl font-semibold tracking-tight">
                {STEP_TITLES[step]}
              </h1>
              <p className="mt-1 text-sm text-muted-foreground">
                {STEP_SUBTITLES[step]}
              </p>
            </div>
          </div>

          <div className="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
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
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                  <button
                    type="button"
                    onClick={() => setMigrated(false)}
                    className={`rounded-xl border p-4 text-left transition ${
                      migrated === false
                        ? "border-primary bg-accent ring-1 ring-primary/30"
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
                        ? "border-primary bg-accent ring-1 ring-primary/30"
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
                      const nextNum =
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
                            {nextNum ? (
                              <>
                                siguiente:{" "}
                                <span className="font-mono">{nextNum}</span>
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
              <div className="flex flex-col items-center gap-4 py-8 text-center">
                <span className="grid size-16 place-items-center rounded-2xl bg-success/10 text-success ring-1 ring-success/20">
                  <Check className="size-8" />
                </span>
                <div>
                  <p className="text-lg font-semibold">
                    Tu cuenta quedó configurada.
                  </p>
                  <p className="mt-1 text-sm text-muted-foreground">
                    Ya puedes emitir tu primera factura electrónica.
                  </p>
                </div>
              </div>
            )}
          </div>

          {/* Navegación */}
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
      </main>
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
