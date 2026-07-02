import Link from "next/link";
import { LoginForm } from "./login-form";

export const metadata = { title: "Iniciar sesión" };

const REASON_MESSAGES: Record<string, string> = {
  expired: "Tu sesión expiró. Inicia sesión de nuevo.",
  unreachable: "No pudimos conectar con el servidor. Vuelve a iniciar sesión.",
};

export default async function LoginPage({
  searchParams,
}: {
  searchParams: Promise<{ reason?: string }>;
}) {
  const { reason } = await searchParams;
  const message = reason ? REASON_MESSAGES[reason] : null;

  return (
    <div className="space-y-8">
      <div className="space-y-2">
        <h2 className="text-2xl font-semibold tracking-tight">
          Bienvenido de nuevo
        </h2>
        <p className="text-sm text-muted-foreground">
          Ingresa tus credenciales para acceder a tu panel.
        </p>
      </div>

      {message && (
        <div className="rounded-lg border border-amber-300/60 bg-amber-50 px-3.5 py-2.5 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
          {message}
        </div>
      )}

      <LoginForm />

      <div className="space-y-3 text-sm">
        <div className="text-center text-muted-foreground">
          ¿No tienes cuenta?{" "}
          <Link
            href="/register"
            className="font-medium text-primary underline-offset-4 hover:underline"
          >
            Regístrate gratis
          </Link>
        </div>
      </div>
    </div>
  );
}
