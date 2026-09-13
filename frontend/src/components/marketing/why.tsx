import { REASONS } from "@/content/landing";
import { ICONS } from "./icons";
import { Reveal } from "./reveal";

export function Why() {
  return (
    <section aria-labelledby="porque-titulo" className="bg-white py-24 sm:py-32">
      <div className="mx-auto max-w-6xl px-5 sm:px-8">
        <Reveal className="max-w-2xl">
          <p className="text-xs font-semibold uppercase tracking-widest text-brand">Por qué Facturón</p>
          <h2 id="porque-titulo" className="mt-3 font-display text-3xl font-bold tracking-tight text-navy sm:text-4xl">
            No es un sistema genérico adaptado a Ecuador
          </h2>
          <p className="mt-4 text-base leading-relaxed text-slate-600">
            Lo construimos desde cero sobre la normativa del SRI. Cada detalle está pensado para el contribuyente ecuatoriano.
          </p>
        </Reveal>
        <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {REASONS.map((r, i) => {
            const Icon = ICONS[r.icon];
            return (
              <Reveal key={r.title} delay={i * 0.08}>
                <article className="h-full rounded-2xl border border-slate-200 bg-white p-7">
                  <span className="flex size-11 items-center justify-center rounded-xl bg-navy text-white">
                    <Icon className="size-5" aria-hidden="true" />
                  </span>
                  <h3 className="mt-5 text-lg font-semibold text-navy">{r.title}</h3>
                  <p className="mt-2 text-sm leading-relaxed text-slate-600">{r.text}</p>
                </article>
              </Reveal>
            );
          })}
        </div>
        <Reveal className="mt-8">
          <p className="rounded-2xl bg-slate-50 px-6 py-5 text-center text-base font-medium text-navy ring-1 ring-slate-200">
            Precio honesto: desde $2.99 al mes, sin comisión por documento.
          </p>
        </Reveal>
      </div>
    </section>
  );
}
