import Link from "next/link";
import { ArrowRight } from "lucide-react";
import { FINAL_CTA } from "@/content/landing";
import { whatsappUrl } from "@/lib/landing/config";
import { WhatsAppGlyph } from "./whatsapp-button";

export function FinalCta() {
  return (
    <section aria-labelledby="cta-titulo" className="relative overflow-hidden bg-navy py-24 text-white sm:py-32">
      <div aria-hidden="true" className="absolute left-1/2 top-0 h-[400px] w-[800px] -translate-x-1/2 rounded-full bg-brand/20 blur-3xl" />
      <div className="relative mx-auto max-w-3xl px-5 text-center sm:px-8">
        <h2 id="cta-titulo" className="font-display text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">{FINAL_CTA.title}</h2>
        <p className="mx-auto mt-4 max-w-xl text-base text-slate-300 sm:text-lg">{FINAL_CTA.text}</p>
        <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
          <Link href="/register" className="inline-flex h-12 items-center gap-2 rounded-full bg-brand px-7 text-sm font-semibold text-white shadow-lg shadow-brand/30 transition-colors hover:bg-brand-hover">
            Crear cuenta <ArrowRight className="size-4" aria-hidden="true" />
          </Link>
          <a href={whatsappUrl()} target="_blank" rel="noopener noreferrer" className="inline-flex h-12 items-center gap-2 rounded-full border border-white/15 px-6 text-sm font-medium text-slate-200 transition-colors hover:border-white/30 hover:text-white">
            <WhatsAppGlyph className="size-4" /> Hablar por WhatsApp
          </a>
        </div>
      </div>
    </section>
  );
}
