import Link from "next/link";
import { AuthHeading } from "@/components/auth/fields";
import { ResetForm } from "./reset-form";

export const metadata = { title: { absolute: "Restablecer contraseña · Facturón" } };

export default async function ResetPasswordPage({
  searchParams,
}: {
  searchParams: Promise<{ token?: string; email?: string }>;
}) {
  const { token, email } = await searchParams;

  if (!token || !email) {
    return (
      <div className="space-y-6">
        <AuthHeading eyebrow="Recuperar acceso" title="Enlace inválido" subtitle="El enlace de recuperación es inválido o expiró. Solicita uno nuevo." />
        <Link href="/forgot-password" className="text-sm font-semibold text-brand underline-offset-4 hover:underline">
          Solicitar nuevo enlace
        </Link>
      </div>
    );
  }

  return (
    <div className="space-y-8">
      <AuthHeading eyebrow="Recuperar acceso" title="Nueva contraseña" subtitle={<>Crea una contraseña nueva para <strong className="text-navy">{email}</strong>.</>} />
      <ResetForm token={token} email={email} />
      <div className="text-center text-sm text-slate-500">
        <Link
          href="/login"
          className="font-semibold text-brand underline-offset-4 hover:underline"
        >
          Volver a iniciar sesión
        </Link>
      </div>
    </div>
  );
}
