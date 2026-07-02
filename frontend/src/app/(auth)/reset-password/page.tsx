import Link from "next/link";
import { ResetForm } from "./reset-form";

export const metadata = { title: "Restablecer contraseña" };

export default async function ResetPasswordPage({
  searchParams,
}: {
  searchParams: Promise<{ token?: string; email?: string }>;
}) {
  const { token, email } = await searchParams;

  if (!token || !email) {
    return (
      <div className="space-y-4">
        <h2 className="text-2xl font-semibold tracking-tight">Enlace inválido</h2>
        <p className="text-sm text-muted-foreground">
          El enlace de recuperación es inválido o expiró. Solicita uno nuevo.
        </p>
        <Link
          href="/forgot-password"
          className="text-sm font-medium text-primary underline-offset-4 hover:underline"
        >
          Solicitar nuevo enlace
        </Link>
      </div>
    );
  }

  return (
    <div className="space-y-8">
      <div className="space-y-2">
        <h2 className="text-2xl font-semibold tracking-tight">
          Nueva contraseña
        </h2>
        <p className="text-sm text-muted-foreground">
          Crea una contraseña nueva para <strong>{email}</strong>.
        </p>
      </div>
      <ResetForm token={token} email={email} />
      <div className="text-center text-sm text-muted-foreground">
        <Link
          href="/login"
          className="font-medium text-primary underline-offset-4 hover:underline"
        >
          Volver a iniciar sesión
        </Link>
      </div>
    </div>
  );
}
