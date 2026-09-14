"use client";

import Link from "next/link";
import { useState } from "react";
import { Check } from "lucide-react";
import { formatPrice, isContactSales, maxSavings, monthlyEquivalent, visiblePlanFeatures, type LandingData, type LandingPlan } from "@/lib/landing/pricing";
import { CONTACT_EMAIL, whatsappUrl } from "@/lib/landing/config";
import { cn } from "@/lib/utils";
import { Reveal } from "./reveal";

function PlanCard({ plan, yearly }: { plan: LandingPlan; yearly: boolean }) {
  const featured = plan.is_featured;
  const contactSales = isContactSales(plan);
  const price = yearly ? plan.price_yearly : plan.price_monthly;
  return (
    <article
      className={cn(
        "relative flex h-full w-[85vw] shrink-0 snap-center flex-col rounded-2xl p-7 sm:w-auto sm:shrink",
        featured ? "bg-navy text-white shadow-xl shadow-navy/20 ring-1 ring-navy" : "bg-white text-navy ring-1 ring-slate-200",
      )}
    >
      {featured && (
        <span className="absolute -top-3 left-6 rounded-full bg-brand px-3.5 py-1 text-[11px] font-semibold text-white shadow-sm">Más popular</span>
      )}
      <h3 className="font-display text-lg font-semibold">{plan.name}</h3>
      {plan.description && <p className={cn("mt-1.5 text-sm", featured ? "text-slate-300" : "text-slate-600")}>{plan.description}</p>}
      {contactSales ? (
        <>
          <div className="mt-6 flex items-baseline gap-1">
            <span className="font-display text-3xl font-bold tracking-tight">A tu medida</span>
          </div>
          <p className={cn("mt-1 h-5 text-xs", featured ? "text-slate-400" : "text-slate-500")}>Precio según volumen y requerimientos</p>
          <a
            href={whatsappUrl(`Hola, quiero información del plan ${plan.name} de Facturón`)}
            target="_blank"
            rel="noopener noreferrer"
            className={cn(
              "mt-5 inline-flex h-11 items-center justify-center rounded-xl text-sm font-semibold transition-colors",
              featured ? "bg-brand text-white hover:bg-brand-hover" : "bg-navy text-white hover:bg-navy/90",
            )}
          >
            Contáctanos
          </a>
          <p className={cn("mt-2 text-center text-xs", featured ? "text-slate-400" : "text-slate-500")}>
            o escríbenos a{" "}
            <a href={`mailto:${CONTACT_EMAIL}`} className="font-medium underline-offset-2 hover:underline">
              {CONTACT_EMAIL}
            </a>
          </p>
        </>
      ) : (
        <>
          <div className="mt-6 flex items-baseline gap-1">
            <span className="font-display text-4xl font-bold tracking-tight">{formatPrice(price, plan.currency)}</span>
            <span className={cn("text-sm", featured ? "text-slate-400" : "text-slate-500")}>{yearly ? "/año" : "/mes"}</span>
          </div>
          <p className={cn("mt-1 h-5 text-xs", featured ? "text-slate-400" : "text-slate-500")}>
            {yearly ? `Equivale a ${formatPrice(monthlyEquivalent(plan.price_yearly), plan.currency)} al mes` : ""}
          </p>
          <Link
            href={`/register?plan=${plan.slug}`}
            className={cn(
              "mt-5 inline-flex h-11 items-center justify-center rounded-xl text-sm font-semibold transition-colors",
              featured ? "bg-brand text-white hover:bg-brand-hover" : "bg-slate-100 text-navy hover:bg-slate-200",
            )}
          >
            Crear cuenta
          </Link>
        </>
      )}
      <ul className="mt-7 space-y-2.5">
        {visiblePlanFeatures(plan.features_list).map((feature) => (
          <li key={feature} className={cn("flex items-start gap-2.5 text-sm", featured ? "text-slate-200" : "text-slate-700")}>
            <Check className={cn("mt-0.5 size-4 shrink-0", featured ? "text-emerald-400" : "text-emerald-500")} aria-hidden="true" />
            {feature}
          </li>
        ))}
      </ul>
    </article>
  );
}

export function Pricing({ data }: { data: LandingData }) {
  const [yearly, setYearly] = useState(false);
  const { plans, pricing_content: content } = data;
  const savings = maxSavings(plans);

  return (
    <section id="precios" aria-labelledby="precios-titulo" className="scroll-mt-16 bg-slate-50 py-24 sm:py-32">
      <div className="mx-auto max-w-6xl px-5 sm:px-8">
        <Reveal className="mx-auto max-w-2xl text-center">
          <p className="text-xs font-semibold uppercase tracking-widest text-brand">{content.eyebrow}</p>
          <h2 id="precios-titulo" className="mt-3 font-display text-3xl font-bold tracking-tight text-navy sm:text-4xl">{content.title}</h2>
          <p className="mt-4 text-base text-slate-600">{content.subtitle}</p>
          {content.badge_enabled && content.badge_text && (
            <span className="mt-5 inline-flex items-center rounded-full bg-brand/10 px-4 py-1.5 text-sm font-medium text-brand ring-1 ring-brand/20">{content.badge_text}</span>
          )}
        </Reveal>

        <div className="mt-10 flex items-center justify-center gap-3">
          <span className={cn("text-sm font-medium", yearly ? "text-slate-600" : "text-navy")}>Mensual</span>
          <button
            type="button"
            role="switch"
            aria-checked={yearly}
            aria-label="Facturación anual"
            onClick={() => setYearly((v) => !v)}
            className={cn("relative h-6 w-11 rounded-full transition-colors", yearly ? "bg-brand" : "bg-slate-300")}
          >
            <span className={cn("absolute left-0.5 top-0.5 size-5 rounded-full bg-white shadow-sm transition-transform", yearly && "translate-x-5")} />
          </button>
          <span className={cn("text-sm font-medium", yearly ? "text-navy" : "text-slate-600")}>
            Anual
            {savings > 0 && (
              <span className="ml-1.5 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-200">Ahorra hasta {savings} %</span>
            )}
          </span>
        </div>
        <p className="mt-4 text-center text-sm text-slate-500">
          {data.payment_methods?.paypal
            ? "Sin comisión por documento. Paga con PayPal y tu cuenta se activa al instante, o por transferencia bancaria."
            : "Sin comisión por documento. Pago por transferencia bancaria; tu cuenta se activa al confirmarlo."}
        </p>

        <div className="-mx-5 mt-12 flex snap-x snap-mandatory gap-4 overflow-x-auto px-5 pb-4 sm:mx-0 sm:grid sm:grid-cols-2 sm:overflow-visible sm:px-0 sm:pb-0 lg:grid-cols-4">
          {plans.map((plan) => (
            <PlanCard key={plan.id} plan={plan} yearly={yearly} />
          ))}
        </div>

        {content.footer_note && <p className="mt-8 text-center text-sm text-slate-500">{content.footer_note}</p>}
      </div>
    </section>
  );
}
