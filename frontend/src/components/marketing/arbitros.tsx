import { Check, MessageCircle } from "lucide-react";
import { ARBITROS } from "@/content/landing";
import { whatsappUrl } from "@/lib/landing/config";
import { Reveal } from "./reveal";

const MATCHES = [
  { match: "Fecha 12 · Serie A", date: "07/09", status: "Pendiente" },
  { match: "Fecha 11 · Serie B", date: "31/08", status: "Facturado" },
  { match: "Fecha 11 · Serie A", date: "30/08", status: "Facturado" },
] as const;

export function Arbitros() {
  return (
    <section id="arbitros" aria-labelledby="arbitros-titulo" className="relative scroll-mt-16 overflow-hidden bg-navy py-20 text-white sm:py-24">
      <div aria-hidden="true" className="absolute inset-x-0 top-0 h-px bg-flag-gradient opacity-70" />
      <div className="mx-auto grid max-w-6xl gap-10 px-5 sm:px-8 lg:grid-cols-12 lg:items-center">
        <Reveal className="lg:col-span-7">
          <p className="text-xs font-semibold uppercase tracking-widest text-emerald-400">{ARBITROS.eyebrow}</p>
          <h2 id="arbitros-titulo" className="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">{ARBITROS.title}</h2>
          <p className="mt-4 text-base text-slate-300">{ARBITROS.text}</p>
          <ul className="mt-6 space-y-3">
            {ARBITROS.bullets.map((b) => (
              <li key={b} className="flex items-start gap-2.5 text-sm text-slate-200">
                <Check className="mt-0.5 size-4 shrink-0 text-emerald-400" aria-hidden="true" />
                {b}
              </li>
            ))}
          </ul>
          <a
            href={whatsappUrl(ARBITROS.whatsappText)}
            target="_blank"
            rel="noopener noreferrer"
            className="mt-8 inline-flex h-11 items-center gap-2 rounded-full bg-white px-6 text-sm font-semibold text-navy transition-colors hover:bg-slate-100"
          >
            <MessageCircle className="size-4" aria-hidden="true" />
            {ARBITROS.cta}
          </a>
        </Reveal>
        <Reveal className="lg:col-span-5" delay={0.1}>
          <div className="rounded-2xl border border-white/10 bg-navy-2 p-5" aria-hidden="true">
            <p className="text-[11px] font-medium uppercase tracking-wider text-slate-400">Partidos del mes</p>
            <ul className="mt-3 divide-y divide-white/10">
              {MATCHES.map((m) => (
                <li key={m.match} className="flex items-center justify-between gap-3 py-3 text-sm">
                  <div>
                    <p className="font-medium text-white">{m.match}</p>
                    <p className="text-xs text-slate-400">{m.date}</p>
                  </div>
                  <span className={m.status === "Pendiente" ? "rounded-md bg-amber-500/15 px-2 py-0.5 text-xs font-medium text-amber-300" : "rounded-md bg-emerald-500/15 px-2 py-0.5 text-xs font-medium text-emerald-300"}>
                    {m.status}
                  </span>
                </li>
              ))}
            </ul>
            <p className="mt-4 rounded-lg bg-white/5 px-3 py-2 text-xs text-slate-300">Ventana FEF abierta hasta el 20 · 1 partido por facturar</p>
          </div>
        </Reveal>
      </div>
    </section>
  );
}
