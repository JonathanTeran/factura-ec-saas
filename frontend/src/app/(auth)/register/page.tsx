import Link from "next/link";
import { AuthHeading } from "@/components/auth/fields";
import { RegisterForm } from "./register-form";

export const metadata = { title: { absolute: "Crear cuenta · Facturón" } };

export default async function RegisterPage({
  searchParams,
}: {
  searchParams: Promise<{ type?: string; plan?: string }>;
}) {
  const { type, plan } = await searchParams;
  const defaultType = type === "referee" ? "referee" : "generic";
  const planSlug = typeof plan === "string" && /^[a-z0-9-]{1,50}$/.test(plan) ? plan : "";

  return (
    <div className="space-y-8">
      <AuthHeading
        eyebrow="Empieza hoy"
        title="Crea tu cuenta"
        subtitle={
          <>
            Desde <span className="font-semibold text-navy">$2.99 al mes</span>, sin comisión por documento. En 5 minutos estás facturando.
          </>
        }
      />
      <RegisterForm defaultType={defaultType} plan={planSlug} />
      <p className="text-center text-sm text-slate-500">
        ¿Ya tienes cuenta?{" "}
        <Link href="/login" className="font-semibold text-brand underline-offset-4 hover:underline">
          Ingresar
        </Link>
      </p>
    </div>
  );
}
