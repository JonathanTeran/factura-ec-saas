import { DOCUMENT_TYPES } from "@/content/landing";
import { ICONS } from "./icons";
import { Reveal } from "./reveal";

export function Documents() {
  return (
    <section aria-labelledby="comprobantes-titulo" className="bg-white py-20 sm:py-28">
      <div className="mx-auto max-w-6xl px-5 sm:px-8">
        <Reveal className="max-w-2xl">
          <p className="text-xs font-semibold uppercase tracking-widest text-brand">Comprobantes</p>
          <h2 id="comprobantes-titulo" className="mt-3 font-display text-3xl font-bold tracking-tight text-navy sm:text-4xl">
            Todo lo que emites
          </h2>
          <p className="mt-4 text-base leading-relaxed text-slate-600">
            Los seis comprobantes electrónicos del SRI, con el código que exige la ficha técnica.
          </p>
        </Reveal>
        <ul className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {DOCUMENT_TYPES.map((doc, i) => {
            const Icon = ICONS[doc.icon];
            return (
              <Reveal key={doc.code} delay={i * 0.05}>
                <li className="flex h-full gap-4 rounded-2xl border border-slate-200 bg-white p-6 transition-shadow hover:shadow-lg hover:shadow-slate-200/60">
                  <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-navy text-white">
                    <Icon className="size-5" aria-hidden="true" />
                  </span>
                  <div>
                    <p className="font-mono text-xs font-semibold text-brand">{doc.code}</p>
                    <h3 className="mt-0.5 text-base font-semibold text-navy">{doc.name}</h3>
                    <p className="mt-1.5 text-sm leading-relaxed text-slate-600">{doc.use}</p>
                  </div>
                </li>
              </Reveal>
            );
          })}
        </ul>
      </div>
    </section>
  );
}
