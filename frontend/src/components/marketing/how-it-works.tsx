import { STEPS } from "@/content/landing";
import { Reveal } from "./reveal";

export function HowItWorks() {
  return (
    <section id="como-funciona" aria-labelledby="como-funciona-titulo" className="scroll-mt-16 bg-white py-24 sm:py-32">
      <div className="mx-auto max-w-6xl px-5 sm:px-8">
        <Reveal className="mx-auto max-w-2xl text-center">
          <p className="text-xs font-semibold uppercase tracking-widest text-brand">Cómo funciona</p>
          <h2 id="como-funciona-titulo" className="mt-3 font-display text-3xl font-bold tracking-tight text-navy sm:text-4xl">
            En 5 minutos estás facturando
          </h2>
        </Reveal>
        <ol className="mt-14 grid gap-8 sm:grid-cols-3">
          {STEPS.map((step, i) => (
            <Reveal key={step.title} delay={i * 0.1}>
              <li className="relative rounded-2xl border border-slate-200 bg-white p-7">
                <span className="flex size-12 items-center justify-center rounded-2xl bg-navy font-display text-xl font-bold text-white">{i + 1}</span>
                <h3 className="mt-5 text-base font-semibold text-navy">{step.title}</h3>
                <p className="mt-2 text-sm leading-relaxed text-slate-600">{step.text}</p>
              </li>
            </Reveal>
          ))}
        </ol>
      </div>
    </section>
  );
}
