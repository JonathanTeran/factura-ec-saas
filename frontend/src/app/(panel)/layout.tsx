import { redirect } from "next/navigation";
import { requireUser } from "@/lib/server/auth";
import { apiFetch } from "@/lib/server/api";
import { Sidebar } from "@/components/panel/sidebar";
import { Topbar } from "@/components/panel/topbar";
import { SignatureBanner } from "@/components/panel/signature-banner";
import type { ApiSuccess } from "@/lib/api/client";
import type {
  OnboardingStatus,
  SignatureStatus,
} from "@/lib/api/queries/onboarding";

export default async function PanelLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const user = await requireUser();

  // Gate de onboarding: al wizard solo si aún no existe la empresa (sin ella
  // el panel no tiene nada que mostrar). Con empresa creada se puede entrar
  // aunque falten pasos — el semáforo del dashboard guía lo pendiente.
  // Ante cualquier error del endpoint, dejamos pasar (no bloquear el panel).
  let needsOnboarding = false;
  try {
    const status = await apiFetch<ApiSuccess<OnboardingStatus>>(
      "/api/v1/onboarding/status",
    );
    needsOnboarding =
      status.data.completed === false && status.data.has_company === false;
  } catch {
    needsOnboarding = false;
  }
  if (needsOnboarding) redirect("/onboarding");

  // Aviso de firma electrónica (vencida / por vencer / faltante).
  let signature: SignatureStatus | null = null;
  try {
    const res = await apiFetch<ApiSuccess<SignatureStatus>>(
      "/api/v1/signature-status",
    );
    signature = res.data;
  } catch {
    signature = null;
  }

  return (
    <div className="flex min-h-screen bg-background">
      <Sidebar />
      <div className="flex min-w-0 flex-1 flex-col">
        <Topbar user={user} />
        <main className="flex-1">
          {signature && <SignatureBanner data={signature} />}
          {children}
        </main>
      </div>
    </div>
  );
}
