"use client";

import { useState } from "react";
import {
  ShieldCheck,
  ShieldX,
  AlertTriangle,
  Upload,
  Loader2,
  CalendarClock,
  Fingerprint,
  BadgeCheck,
} from "lucide-react";
import { toast } from "sonner";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Field } from "@/components/panel/form";
import {
  useSignatureStatus,
  useUploadCertificate,
  type CertificateInfo,
} from "@/lib/api/queries/onboarding";
import { formatDate } from "@/lib/format";

const STATUS_META: Record<
  string,
  { label: string; className: string; tone: "ok" | "warn" | "bad" }
> = {
  valid: {
    label: "Vigente",
    className: "border-transparent bg-success/10 text-success",
    tone: "ok",
  },
  expiring_soon: {
    label: "Por vencer",
    className: "border-transparent bg-warning/10 text-warning",
    tone: "warn",
  },
  expired: {
    label: "Vencida",
    className: "border-transparent bg-destructive/10 text-destructive",
    tone: "bad",
  },
};

export function FirmaManager() {
  const { data, isLoading } = useSignatureStatus();
  const upload = useUploadCertificate();

  const [file, setFile] = useState<File | null>(null);
  const [password, setPassword] = useState("");
  const [justUploaded, setJustUploaded] = useState<CertificateInfo | null>(null);

  const configured =
    data && data.status !== "missing" && data.status !== "unknown";
  const meta = data ? STATUS_META[data.status] : undefined;

  async function submit() {
    if (!file || !password) {
      toast.error("Selecciona el certificado e ingresa la contraseña.");
      return;
    }
    try {
      const res = await upload.mutateAsync({ file, password });
      setJustUploaded(res.data);
      setFile(null);
      setPassword("");
      if (res.data.days_until_expiry <= 30) {
        toast.warning(`Firma actualizada. Vence en ${res.data.days_until_expiry} días.`);
      } else {
        toast.success("Firma electrónica actualizada.");
      }
    } catch (e) {
      toast.error(e instanceof Error ? e.message : "No se pudo validar el certificado.");
    }
  }

  return (
    <div className="mx-auto max-w-3xl space-y-4">
      {/* Estado actual */}
      <Card>
        <CardHeader className="flex-row items-center justify-between">
          <CardTitle>Estado de la firma</CardTitle>
          {meta && (
            <Badge variant="outline" className={meta.className}>
              {meta.label}
            </Badge>
          )}
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="flex justify-center py-6">
              <Loader2 className="size-5 animate-spin text-muted-foreground" />
            </div>
          ) : configured ? (
            <div className="space-y-4">
              <div
                className={`flex items-start gap-3 rounded-xl border p-4 ${
                  meta?.tone === "ok"
                    ? "border-success/30 bg-success/5"
                    : meta?.tone === "warn"
                      ? "border-warning/40 bg-warning/5"
                      : "border-destructive/30 bg-destructive/5"
                }`}
              >
                {meta?.tone === "ok" ? (
                  <ShieldCheck className="mt-0.5 size-5 shrink-0 text-success" />
                ) : meta?.tone === "warn" ? (
                  <AlertTriangle className="mt-0.5 size-5 shrink-0 text-warning" />
                ) : (
                  <ShieldX className="mt-0.5 size-5 shrink-0 text-destructive" />
                )}
                <div className="text-sm">
                  <p className="font-medium text-foreground">
                    {data?.status === "expired"
                      ? "Tu firma electrónica venció"
                      : data?.days_remaining != null
                        ? `Tu firma vence en ${data.days_remaining} día${data.days_remaining === 1 ? "" : "s"}`
                        : "Firma electrónica configurada"}
                  </p>
                  <p className="text-muted-foreground">
                    {data?.status === "expired"
                      ? "No podrás emitir comprobantes hasta renovarla."
                      : "Súbela de nuevo abajo cuando la renueves."}
                  </p>
                </div>
              </div>

              <dl className="divide-y divide-border rounded-xl border border-border">
                <Row icon={BadgeCheck} label="Titular" value={data?.subject ?? "—"} />
                <Row
                  icon={CalendarClock}
                  label="Válido hasta"
                  value={data?.expires_at ? formatDate(data.expires_at) : "—"}
                />
              </dl>
            </div>
          ) : (
            <div className="flex items-start gap-3 rounded-xl border border-primary/30 bg-primary/5 p-4 text-sm">
              <ShieldX className="mt-0.5 size-5 shrink-0 text-primary" />
              <div>
                <p className="font-medium text-foreground">
                  Aún no configuras tu firma electrónica
                </p>
                <p className="text-muted-foreground">
                  Súbela abajo para poder emitir comprobantes al SRI.
                </p>
              </div>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Subir / renovar */}
      <Card>
        <CardHeader>
          <CardTitle>{configured ? "Renovar certificado" : "Subir certificado"}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <Field label="Certificado (.p12 / .pfx)" required>
            <label className="flex cursor-pointer items-center gap-3 rounded-lg border border-dashed border-input bg-card px-4 py-4 text-sm transition hover:border-primary/40">
              <Upload className="size-5 text-muted-foreground" />
              <span className={file ? "font-medium" : "text-muted-foreground"}>
                {file ? file.name : "Selecciona tu archivo de firma"}
              </span>
              <input
                type="file"
                accept=".p12,.pfx"
                className="hidden"
                onChange={(e) => setFile(e.target.files?.[0] ?? null)}
              />
            </label>
          </Field>
          <Field label="Contraseña del certificado" required htmlFor="cp">
            <Input
              id="cp"
              type="password"
              placeholder="••••••••"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
            />
          </Field>

          {justUploaded && (
            <dl className="divide-y divide-border rounded-xl border border-success/30 bg-success/5">
              <Row icon={BadgeCheck} label="Titular" value={justUploaded.signature_subject} />
              {justUploaded.signature_identification && (
                <Row icon={Fingerprint} label="Identificación" value={justUploaded.signature_identification} mono />
              )}
              <Row icon={ShieldCheck} label="Emisor" value={justUploaded.signature_issuer} />
              <Row icon={CalendarClock} label="Válido hasta" value={formatDate(justUploaded.signature_expires_at)} />
            </dl>
          )}

          <div className="flex justify-end">
            <Button onClick={submit} disabled={upload.isPending}>
              {upload.isPending && <Loader2 className="size-4 animate-spin" />}
              {configured ? "Renovar firma" : "Guardar firma"}
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

function Row({
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
      <span className="w-32 shrink-0 text-sm text-muted-foreground">{label}</span>
      <span className={`min-w-0 flex-1 truncate text-sm font-medium ${mono ? "font-mono" : ""}`}>
        {value}
      </span>
    </div>
  );
}
