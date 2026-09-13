import Link from "next/link";
import { AuthHeading } from "@/components/auth/fields";
import { ForgotForm } from "./forgot-form";

export const metadata = { title: { absolute: "Recuperar contraseña · Facturón" } };

export default function ForgotPasswordPage() {
  return (
    <div className="space-y-8">
      <AuthHeading eyebrow="Recuperar acceso" title="¿Olvidaste tu contraseña?" subtitle="Escribe tu correo y te enviamos un enlace para crear una nueva." />
      <ForgotForm />
      <p className="text-center text-sm text-slate-500">
        <Link href="/login" className="font-semibold text-brand underline-offset-4 hover:underline">
          Volver a iniciar sesión
        </Link>
      </p>
    </div>
  );
}
