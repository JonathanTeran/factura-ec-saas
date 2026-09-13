import type { ReactNode } from "react";
import Link from "next/link";
import { bricolage } from "@/lib/fonts";
import { CONTACT_EMAIL, formatWhatsapp, whatsappUrl } from "@/lib/landing/config";
import { FacturonGlyph } from "@/components/marketing/logo";

/**
 * Pantalla de error de marca (404, error de la app, etc.). Sin hooks: sirve
 * tanto en componentes de servidor (not-found) como de cliente (error.tsx).
 * Colores explícitos (no depende del tema del panel).
 */
export function ErrorScreen({
  code,
  title,
  message,
  actions,
  hint,
}: {
  code: string;
  title: string;
  message: string;
  actions: ReactNode;
  hint?: ReactNode;
}) {
  return (
    <div
      className={`${bricolage.variable} flex min-h-screen flex-col bg-[#0B1220] text-slate-200 antialiased`}
      style={{ backgroundImage: "radial-gradient(70% 60% at 85% -10%, rgba(43,84,228,.38), transparent 60%)" }}
    >
      <div aria-hidden className="h-1 bg-[linear-gradient(90deg,#FFCE00_0%,#FFCE00_33.3%,#0653C6_33.3%,#0653C6_66.6%,#EF3340_66.6%,#EF3340_100%)]" />
      <main className="flex flex-1 items-center justify-center px-5 py-12">
        <div className="w-full max-w-[560px] text-center">
          <Link href="/" className="inline-flex items-center gap-2.5 text-white" aria-label="Facturón, ir al inicio">
            <FacturonGlyph className="size-10" />
            <span className="font-display text-[22px] font-bold tracking-tight">Facturón</span>
          </Link>

          <p
            aria-hidden
            className="mt-8 bg-gradient-to-b from-white to-white/55 bg-clip-text font-display text-[clamp(64px,16vw,112px)] font-extrabold leading-[0.95] tracking-[-0.04em] text-transparent"
          >
            {code}
          </p>
          <h1 className="mt-1.5 font-display text-[clamp(24px,4.6vw,32px)] font-extrabold leading-tight tracking-tight text-white">{title}</h1>
          <p className="mx-auto mt-3.5 max-w-[460px] text-[16px] leading-relaxed text-[#B7C2D6]">{message}</p>

          {hint && (
            <div className="mx-auto mt-4 max-w-[460px] rounded-xl border border-white/10 bg-white/5 px-3.5 py-3 text-left text-[13.5px] text-[#B7C2D6]">
              {hint}
            </div>
          )}

          <div className="mt-7 flex flex-wrap justify-center gap-2.5">{actions}</div>

          <p className="mt-8 text-[13.5px] leading-relaxed text-[#8B98AF]">
            ¿Necesitas ayuda?{" "}
            <a href={whatsappUrl()} target="_blank" rel="noopener noreferrer" className="text-[#C7D2FE] hover:underline">
              WhatsApp {formatWhatsapp()}
            </a>{" "}
            ·{" "}
            <a href={`mailto:${CONTACT_EMAIL}`} className="text-[#C7D2FE] hover:underline">
              {CONTACT_EMAIL}
            </a>
          </p>
        </div>
      </main>
      <footer className="px-5 pb-6 pt-4 text-center text-[12.5px] text-[#6B7791]">
        © {new Date().getFullYear()} Facturón · Un producto de{" "}
        <a href="https://amephia.com" target="_blank" rel="noopener noreferrer" className="text-[#8B98AF]">
          AmePhia Systems Inc.
        </a>
      </footer>
    </div>
  );
}

export const errorButtonPrimary =
  "inline-flex h-11 items-center justify-center gap-2 rounded-full bg-[#2B54E4] px-5.5 px-6 text-[15px] font-semibold text-white shadow-lg shadow-[#2B54E4]/35 transition-colors hover:bg-[#2446C4]";

export const errorButtonSecondary =
  "inline-flex h-11 items-center justify-center gap-2 rounded-full border border-white/20 px-6 text-[15px] font-semibold text-white transition-colors hover:border-white/40 hover:bg-white/10";
