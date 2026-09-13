import Link from "next/link";
import { Check } from "lucide-react";
import { LiveDemo } from "@/components/marketing/live-demo";
import { FacturonLogo } from "@/components/marketing/logo";
import { bricolage } from "@/lib/fonts";

const TRUST = ["Ficha técnica del SRI", "Firma XAdES-BES", "Certificados BCE · Security Data · ANF"];

/**
 * Shell de las páginas de acceso: panel de marca (navy, con la demo de emisión)
 * y panel claro con el formulario. Colores explícitos: no sigue el tema del sistema.
 */
export default function AuthLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className={`${bricolage.variable} min-h-screen bg-white text-slate-900 antialiased lg:grid lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]`}>
      {/* Panel de marca (escritorio) */}
      <aside className="relative hidden overflow-hidden bg-navy text-white lg:flex lg:flex-col lg:justify-between lg:p-12 xl:p-16">
        <div aria-hidden="true" className="pointer-events-none absolute inset-0">
          <div className="absolute -top-40 left-1/4 size-[620px] rounded-full bg-brand/25 blur-3xl" />
          <div className="absolute -bottom-48 -left-24 size-[480px] rounded-full bg-[#0653C6]/20 blur-3xl" />
          <div
            className="absolute inset-0 opacity-[0.06]"
            style={{
              backgroundImage:
                "linear-gradient(rgba(255,255,255,.6) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.6) 1px, transparent 1px)",
              backgroundSize: "44px 44px",
              maskImage: "radial-gradient(ellipse at 35% 25%, black, transparent 72%)",
            }}
          />
          <div className="absolute inset-x-0 top-0 h-[3px] bg-flag-gradient" />
        </div>

        <div className="relative">
          <FacturonLogo tone="light" />
        </div>

        <div className="relative max-w-xl">
          <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Facturación electrónica · Ecuador</p>
          <p className="mt-4 font-display text-[2.6rem] font-extrabold leading-[1.05] tracking-tight xl:text-5xl">
            Facturación electrónica que el SRI autoriza en segundos.
          </p>
          <div className="mt-10 max-w-[560px]">
            <LiveDemo />
          </div>
        </div>

        <ul className="relative flex flex-wrap gap-x-6 gap-y-2 text-xs text-slate-400" aria-label="Cumplimiento técnico">
          {TRUST.map((item) => (
            <li key={item} className="inline-flex items-center gap-1.5">
              <Check className="size-3.5 text-emerald-400" aria-hidden="true" />
              {item}
            </li>
          ))}
        </ul>
      </aside>

      {/* Panel del formulario */}
      <main className="flex min-h-screen flex-col">
        <div className="relative overflow-hidden bg-navy px-6 pb-7 pt-5 text-white lg:hidden">
          <div aria-hidden="true" className="absolute inset-x-0 top-0 h-[3px] bg-flag-gradient" />
          <div aria-hidden="true" className="pointer-events-none absolute -right-16 -top-16 size-56 rounded-full bg-brand/30 blur-3xl" />
          <div className="relative flex items-center justify-between">
            <FacturonLogo tone="light" />
            <Link href="/" className="text-xs font-medium text-slate-300 hover:text-white">
              ← Sitio
            </Link>
          </div>
        </div>

        <div className="flex flex-1 items-center justify-center px-6 py-10 sm:px-10 lg:px-16 xl:px-24">
          <div className="w-full max-w-[420px]">{children}</div>
        </div>

        <p className="px-6 pb-6 text-center text-xs text-slate-400 sm:px-10 lg:px-16 lg:text-left xl:px-24">
          Comprobantes autorizados por el SRI ·{" "}
          <Link href="/" className="font-medium text-slate-500 hover:text-navy">
            facturon.ec
          </Link>
        </p>
      </main>
    </div>
  );
}
