import { ChevronDown } from "lucide-react";
import { FAQS } from "@/content/landing";
import { CONTACT_EMAIL, whatsappUrl } from "@/lib/landing/config";
import { Reveal } from "./reveal";

export function Faq() {
  return (
    <section id="faq" aria-labelledby="faq-titulo" className="scroll-mt-16 bg-white py-24 sm:py-32">
      <div className="mx-auto max-w-3xl px-5 sm:px-8">
        <Reveal className="text-center">
          <p className="text-xs font-semibold uppercase tracking-widest text-brand">Preguntas frecuentes</p>
          <h2 id="faq-titulo" className="mt-3 font-display text-3xl font-bold tracking-tight text-navy sm:text-4xl">Resolvemos tus dudas</h2>
        </Reveal>
        <div className="mt-12 space-y-3">
          {FAQS.map((item, i) => (
            <details
              key={item.q}
              name="faq"
              open={i === 0}
              className="group rounded-xl border border-slate-200 bg-white transition-colors open:border-brand/40 open:bg-brand/[0.03]"
            >
              <summary className="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-5 text-sm font-semibold text-navy [&::-webkit-details-marker]:hidden">
                {item.q}
                <ChevronDown className="size-4 shrink-0 text-slate-400 transition-transform group-open:rotate-180" aria-hidden="true" />
              </summary>
              <p className="px-6 pb-5 text-sm leading-relaxed text-slate-600">{item.a}</p>
            </details>
          ))}
        </div>
        <p className="mt-10 text-center text-sm text-slate-500">
          ¿Otra pregunta?{" "}
          <a href={whatsappUrl()} target="_blank" rel="noopener noreferrer" className="font-medium text-brand hover:underline">Escríbenos por WhatsApp</a>
          {" "}o a{" "}
          <a href={`mailto:${CONTACT_EMAIL}`} className="font-medium text-brand hover:underline">{CONTACT_EMAIL}</a>.
        </p>
      </div>
    </section>
  );
}
