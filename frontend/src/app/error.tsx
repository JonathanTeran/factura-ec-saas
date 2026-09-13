"use client";

import { useEffect } from "react";
import Link from "next/link";
import { ErrorScreen, errorButtonPrimary, errorButtonSecondary } from "@/components/error-screen";

/** Error no controlado en cualquier página (dentro del layout raíz). */
export default function ErrorPage({ error, reset }: { error: Error & { digest?: string }; reset: () => void }) {
  useEffect(() => {
    console.error(error);
  }, [error]);

  return (
    <ErrorScreen
      code="Ups"
      title="Algo salió mal de nuestro lado"
      message="Ya quedó registrado y lo vamos a revisar. Tus comprobantes y datos no se han perdido. Inténtalo de nuevo en un momento."
      hint={
        error.digest ? (
          <>
            Si nos escribes, indícanos este código: <code className="font-mono text-[12.5px] text-slate-100">{error.digest}</code>
          </>
        ) : undefined
      }
      actions={
        <>
          <button type="button" onClick={reset} className={errorButtonPrimary}>
            Reintentar
          </button>
          <Link href="/dashboard" className={errorButtonSecondary}>
            Ir a mi panel
          </Link>
        </>
      }
    />
  );
}
