import Link from "next/link";
import { AuthHeading, FormAlert } from "@/components/auth/fields";
import { LoginForm } from "./login-form";

export const metadata = { title: { absolute: "Iniciar sesión · Facturón" } };

const REASON_MESSAGES: Record<string, string> = {
  expired: "Tu sesión expiró. Inicia sesión de nuevo.",
  unreachable: "No pudimos conectar con el servidor. Vuelve a iniciar sesión.",
};

export default async function LoginPage({ searchParams }: { searchParams: Promise<{ reason?: string }> }) {
  const { reason } = await searchParams;
  const message = reason ? REASON_MESSAGES[reason] : null;

  return (
    <div className="space-y-8">
      <AuthHeading eyebrow="Bienvenido de nuevo" title="Ingresa a tu panel" subtitle="Tus comprobantes, clientes y cobros te esperan." />
      {message && <FormAlert tone="warning">{message}</FormAlert>}
      <LoginForm />
      <p className="text-center text-sm text-slate-500">
        ¿No tienes cuenta?{" "}
        <Link href="/register" className="font-semibold text-brand underline-offset-4 hover:underline">
          Crear cuenta
        </Link>
      </p>
    </div>
  );
}
