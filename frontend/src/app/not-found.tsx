import type { Metadata } from "next";
import Link from "next/link";
import { ErrorScreen, errorButtonPrimary, errorButtonSecondary } from "@/components/error-screen";

export const metadata: Metadata = {
  title: { absolute: "Página no encontrada · Facturón" },
  robots: { index: false, follow: false },
};

export default function NotFound() {
  return (
    <ErrorScreen
      code="404"
      title="No encontramos esta página"
      message="La dirección puede estar mal escrita o la página ya no existe. Revisa el enlace o vuelve al inicio."
      actions={
        <>
          <Link href="/" className={errorButtonPrimary}>
            Ir al inicio
          </Link>
          <Link href="/dashboard" className={errorButtonSecondary}>
            Ir a mi panel
          </Link>
        </>
      }
    />
  );
}
