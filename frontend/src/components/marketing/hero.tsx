import Link from "next/link";
import { ArrowRight, ChevronDown } from "lucide-react";
import { HERO } from "@/content/landing";
import { STORE_APPSTORE_URL, STORE_PLAY_URL } from "@/lib/landing/config";
import { formatPrice, type LandingPlan } from "@/lib/landing/pricing";
import { LiveDemo } from "./live-demo";
import { StoreBadges } from "./store-badges";
import { TrustStrip } from "./trust-strip";

export function Hero({ cheapest }: { cheapest: LandingPlan | null }) {
  const from = cheapest ? formatPrice(cheapest.price_monthly) : "$2.99";

  return (
    <section className="relative overflow-hidden bg-navy pt-16 text-white">
      <div aria-hidden="true" className="pointer-events-none absolute inset-0">
        <div className="absolute -top-40 right-[-10%] size-[640px] rounded-full bg-brand/20 blur-3xl" />
        <div className="absolute bottom-0 left-[-10%] size-[480px] rounded-full bg-[#0653C6]/15 blur-3xl" />
        <div className="absolute inset-x-0 top-16 h-px bg-flag-gradient opacity-70" />
      </div>

      <div className="relative mx-auto max-w-6xl px-5 pb-16 pt-14 sm:px-8 sm:pt-20 lg:pb-24 lg:pt-24">
        <div className="grid items-center gap-12 lg:grid-cols-12">
          <div className="lg:col-span-6">
            <p className="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3.5 py-1.5 text-xs font-semibold uppercase tracking-wider text-slate-200">
              {HERO.eyebrow}
            </p>
            <h1 className="mt-6 font-display text-[2.6rem] font-extrabold leading-[1.04] tracking-tight sm:text-5xl lg:text-[3.6rem]">
              {HERO.title}
            </h1>
            <p className="mt-5 max-w-lg text-base leading-relaxed text-slate-300 sm:text-lg">
              <span className="font-semibold text-white">Desde {from} al mes</span>, sin comisión por documento. {HERO.subtitle}
            </p>
            <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
              <Link href="/register" className="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-brand px-7 text-sm font-semibold text-white shadow-lg shadow-brand/30 transition-colors hover:bg-brand-hover">
                Crear cuenta <ArrowRight className="size-4" aria-hidden="true" />
              </Link>
              <a href="#como-funciona" className="inline-flex h-12 items-center justify-center gap-2 px-5 text-sm font-medium text-slate-200 transition-colors hover:text-white">
                Ver cómo funciona <ChevronDown className="size-4" aria-hidden="true" />
              </a>
            </div>
            <StoreBadges className="mt-8" playUrl={STORE_PLAY_URL} appStoreUrl={STORE_APPSTORE_URL} />
          </div>
          <div className="lg:col-span-6">
            <LiveDemo />
          </div>
        </div>
        <TrustStrip />
      </div>
    </section>
  );
}
