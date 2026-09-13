import Image from "next/image";
import { Check } from "lucide-react";
import { SHOWCASE } from "@/content/landing";
import { cn } from "@/lib/utils";
import { BrowserFrame } from "./browser-frame";
import { PhoneFrame } from "./phone-frame";
import { Reveal } from "./reveal";

export function Showcase() {
  return (
    <section aria-labelledby="producto-titulo" className="bg-white py-8 sm:py-12">
      <div className="mx-auto max-w-6xl px-5 sm:px-8">
        <h2 id="producto-titulo" className="sr-only">Así se ve Facturón</h2>
        <div className="space-y-24 sm:space-y-32">
          {SHOWCASE.map((row, i) => {
            const reversed = i % 2 === 1;
            return (
              <div key={row.title} className="grid items-center gap-10 lg:grid-cols-12 lg:gap-16">
                <Reveal className={cn("lg:col-span-5", reversed && "lg:order-2")}>
                  <h3 className="font-display text-2xl font-bold tracking-tight text-navy sm:text-3xl">{row.title}</h3>
                  <p className="mt-4 text-base leading-relaxed text-slate-600">{row.text}</p>
                  <ul className="mt-6 space-y-2.5">
                    {row.bullets.map((b) => (
                      <li key={b} className="flex items-start gap-2.5 text-sm text-slate-700">
                        <Check className="mt-0.5 size-4 shrink-0 text-emerald-500" aria-hidden="true" />
                        {b}
                      </li>
                    ))}
                  </ul>
                </Reveal>
                <Reveal className={cn("lg:col-span-7", reversed && "lg:order-1")} delay={0.1}>
                  <div className="rounded-3xl bg-slate-50 p-4 ring-1 ring-slate-200 sm:p-8">
                    {row.image.kind === "browser" ? (
                      <BrowserFrame url="facturon.ec" className="border-slate-200 bg-white shadow-xl shadow-slate-900/10">
                        <Image src={row.image.src} alt={row.image.alt} width={row.image.width} height={row.image.height} sizes="(min-width: 1024px) 56vw, 100vw" className="block h-auto w-full" />
                      </BrowserFrame>
                    ) : (
                      <PhoneFrame>
                        <Image src={row.image.src} alt={row.image.alt} width={row.image.width} height={row.image.height} sizes="260px" className="block h-auto w-full" />
                      </PhoneFrame>
                    )}
                  </div>
                </Reveal>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
