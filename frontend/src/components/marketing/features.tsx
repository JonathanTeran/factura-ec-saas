import { FEATURES } from "@/content/landing";
import { cn } from "@/lib/utils";
import { ICONS } from "./icons";
import { Reveal } from "./reveal";

export function Features() {
  return (
    <section id="funcionalidades" aria-labelledby="funcionalidades-titulo" className="scroll-mt-16 bg-slate-50 py-24 sm:py-32">
      <div className="mx-auto max-w-6xl px-5 sm:px-8">
        <Reveal className="max-w-2xl">
          <p className="text-xs font-semibold uppercase tracking-widest text-brand">Funcionalidades</p>
          <h2 id="funcionalidades-titulo" className="mt-3 font-display text-3xl font-bold tracking-tight text-navy sm:text-4xl">
            Mucho más que facturar
          </h2>
          <p className="mt-4 text-base leading-relaxed text-slate-600">
            Emisión al SRI, punto de venta, inventario, contabilidad y portal para tus clientes: todo tu negocio en una sola plataforma hecha para la normativa ecuatoriana.
          </p>
        </Reveal>
        <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {FEATURES.map((f, i) => {
            const Icon = ICONS[f.icon];
            return (
              <Reveal key={f.title} delay={(i % 4) * 0.05} className={cn(f.span === 2 && "sm:col-span-2")}>
                <article className="flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-6 transition-shadow hover:shadow-lg hover:shadow-slate-200/60">
                  <span className="flex size-11 items-center justify-center rounded-xl bg-brand/10 text-brand">
                    <Icon className="size-5" aria-hidden="true" />
                  </span>
                  <h3 className="mt-5 text-base font-semibold text-navy">{f.title}</h3>
                  <p className="mt-2 text-sm leading-relaxed text-slate-600">{f.text}</p>
                </article>
              </Reveal>
            );
          })}
        </div>
      </div>
    </section>
  );
}
