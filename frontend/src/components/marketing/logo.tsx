import { useId } from "react";
import Link from "next/link";
import { cn } from "@/lib/utils";

/** Glifo del icono de la app: cuadrado con borde bandera y "F" en blanco. */
export function FacturonGlyph({ className }: { className?: string }) {
  // Id único por instancia: si varios glifos comparten el id del degradado, el
  // primero del DOM "captura" la referencia aunque esté oculto (sidebar de
  // escritorio en móvil) y los demás se pintan sin el borde bandera.
  const gradientId = `facturon-flag-${useId().replace(/[^a-zA-Z0-9]/g, "-")}`;
  return (
    <svg viewBox="0 0 1024 1024" className={className} aria-hidden="true" focusable="false">
      <defs>
        <linearGradient id={gradientId} x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stopColor="#FFCE00" />
          <stop offset="48%" stopColor="#0653C6" />
          <stop offset="100%" stopColor="#EF3340" />
        </linearGradient>
      </defs>
      <rect width="1024" height="1024" rx="230" fill={`url(#${gradientId})`} />
      <rect x="110" y="110" width="804" height="804" rx="190" fill="#0B1424" />
      <rect x="400" y="300" width="100" height="424" rx="16" fill="#FFFFFF" />
      <rect x="400" y="300" width="260" height="100" rx="16" fill="#FFFFFF" />
      <rect x="400" y="440" width="200" height="96" rx="16" fill="#FFFFFF" />
    </svg>
  );
}

export function FacturonLogo({ tone = "dark", className }: { tone?: "dark" | "light"; className?: string }) {
  return (
    <Link href="/" className={cn("inline-flex items-center gap-2.5", className)} aria-label="Facturón, ir al inicio">
      <FacturonGlyph className="size-8 shrink-0" />
      <span className={cn("font-display text-lg font-bold tracking-tight", tone === "light" ? "text-white" : "text-navy")}>
        Facturón
      </span>
    </Link>
  );
}
