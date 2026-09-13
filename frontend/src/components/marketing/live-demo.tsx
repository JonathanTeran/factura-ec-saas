"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { AnimatePresence, motion, useInView, useReducedMotion } from "motion/react";
import { CheckCircle2, FileSignature, Mail, Send, ShieldCheck, type LucideIcon } from "lucide-react";
import { claveAcceso } from "@/lib/landing/clave-acceso";
import { DEMO_INVOICE, isAtLeast, type DemoStep } from "@/lib/landing/demo-machine";
import { formatPrice } from "@/lib/landing/pricing";
import { useDemoSequence } from "@/lib/landing/use-demo-sequence";
import { cn } from "@/lib/utils";
import { BrowserFrame } from "./browser-frame";

const DEMO_HOST = (process.env.NEXT_PUBLIC_APP_URL ?? "https://facturon.ec").replace(/^https?:\/\//, "");

/**
 * Clave de acceso con efecto máquina de escribir. Se monta con `key` por ciclo,
 * así el contador arranca en cero en cada vuelta sin resetear estado en efectos.
 */
function TypedClave({ text, active, msPerChar = 45 }: { text: string; active: boolean; msPerChar?: number }) {
  const [count, setCount] = useState(0);
  useEffect(() => {
    if (!active || count >= text.length) return;
    const id = window.setTimeout(() => setCount((c) => c + 1), msPerChar);
    return () => window.clearTimeout(id);
  }, [active, count, text.length, msPerChar]);
  const shown = active ? text.slice(0, count) : "";
  const typing = active && count < text.length;
  return (
    <p data-testid="demo-clave" className="break-all font-mono text-[11px] leading-snug text-emerald-300">
      {shown}
      <span className={cn("inline-block w-[1ch]", typing ? "animate-pulse" : "opacity-0")}>▍</span>
    </p>
  );
}

const STATUS: Array<{ step: DemoStep; label: string; icon: LucideIcon; process: boolean }> = [
  { step: "signing", label: "Firmada con tu certificado · XAdES-BES", icon: FileSignature, process: true },
  { step: "sending", label: "Recibida por el SRI", icon: Send, process: true },
  { step: "authorized", label: "Autorizada por el SRI", icon: ShieldCheck, process: false },
  { step: "delivered", label: "RIDE (PDF) + XML enviados al cliente", icon: Mail, process: false },
];

export function LiveDemo() {
  const ref = useRef<HTMLDivElement>(null);
  const inView = useInView(ref, { amount: 0.3 });
  const reduce = useReducedMotion() ?? false;
  const { step, cycle } = useDemoSequence(inView && !reduce, reduce ? "authorized" : "draft");
  const clave = useMemo(
    () => claveAcceso({ date: new Date(), ruc: DEMO_INVOICE.emitterRuc, sequential: "123" }),
    [],
  );
  const authorized = isAtLeast(step, "authorized");
  const authorizedAt = useMemo(
    () => new Date().toLocaleDateString("es-EC", { day: "2-digit", month: "2-digit", year: "numeric" }),
    [],
  );

  return (
    <div ref={ref} className="relative" aria-hidden="true">
      <p className="sr-only">
        Demostración: una factura se crea, se firma con tu certificado, se envía al SRI, queda autorizada con su clave de acceso y se envía al cliente en PDF y XML.
      </p>
      <div aria-hidden="true" className="absolute -inset-6 rounded-[2rem] bg-brand/20 blur-3xl" />
      <BrowserFrame url={`${DEMO_HOST}/documents/new`} className="relative">
        <div className="grid gap-4 p-4 sm:grid-cols-5 sm:p-5">
          {/* Factura */}
          <div className="rounded-xl border border-white/10 bg-navy-2 p-4 sm:col-span-3">
            <div className="flex items-start justify-between gap-3">
              <div>
                <p className="text-[11px] font-medium uppercase tracking-wider text-slate-400">Factura</p>
                <p className="font-mono text-sm font-semibold text-white">{DEMO_INVOICE.number}</p>
              </div>
              <AnimatePresence mode="wait" initial={false}>
                {authorized ? (
                  <motion.span
                    key="stamp"
                    data-testid="demo-stamp"
                    initial={reduce ? false : { scale: 0.6, opacity: 0, rotate: -12 }}
                    animate={{ scale: 1, opacity: 1, rotate: -6 }}
                    exit={{ opacity: 0 }}
                    className="rounded-md border-2 border-emerald-400 px-2 py-0.5 font-display text-xs font-extrabold tracking-widest text-emerald-400"
                  >
                    AUTORIZADO
                  </motion.span>
                ) : (
                  <motion.span
                    key="pending"
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    className="rounded-md bg-white/5 px-2 py-0.5 text-[11px] font-medium text-slate-400"
                  >
                    {step === "draft" ? "Borrador" : "En proceso"}
                  </motion.span>
                )}
              </AnimatePresence>
            </div>

            <div className="mt-4 text-xs">
              <p className="font-medium text-white">{DEMO_INVOICE.customer.name}</p>
              <p className="font-mono text-slate-400">RUC {DEMO_INVOICE.customer.ruc}</p>
            </div>

            <ul className="mt-4 space-y-2 border-t border-white/10 pt-3 text-xs">
              {DEMO_INVOICE.lines.map((line, i) => (
                <motion.li
                  key={`${cycle}-${i}`}
                  initial={reduce ? false : { opacity: 0, y: 6 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ delay: 0.3 + i * 0.6, duration: 0.35 }}
                  className="flex justify-between gap-3 text-slate-300"
                >
                  <span>{line.qty} × {line.description}</span>
                  <span className="font-mono text-white">{formatPrice(line.total)}</span>
                </motion.li>
              ))}
            </ul>

            <dl className="mt-3 space-y-1 border-t border-white/10 pt-3 text-xs">
              <div className="flex justify-between text-slate-400"><dt>Subtotal</dt><dd className="font-mono">{formatPrice(DEMO_INVOICE.subtotal)}</dd></div>
              <div className="flex justify-between text-slate-400"><dt>IVA {DEMO_INVOICE.ivaRate} %</dt><dd className="font-mono">{formatPrice(DEMO_INVOICE.iva)}</dd></div>
              <div className="flex justify-between text-sm font-semibold text-white"><dt>Total</dt><dd className="font-mono">{formatPrice(DEMO_INVOICE.total)}</dd></div>
            </dl>

            <div className="mt-4 min-h-14 rounded-lg bg-black/30 p-2.5">
              <p className="text-[10px] uppercase tracking-wider text-slate-400">Clave de acceso</p>
              {reduce ? (
                <p data-testid="demo-clave" className="break-all font-mono text-[11px] leading-snug text-emerald-300">{clave}</p>
              ) : (
                <TypedClave key={cycle} text={clave} active={authorized} />
              )}
            </div>
          </div>

          {/* Estado */}
          <ol className="space-y-2 sm:col-span-2">
            {STATUS.map(({ step: s, label, icon: Icon, process }) => {
              const reached = isAtLeast(step, s);
              const inProgress = step === s && process;
              const done = reached && !inProgress;
              return (
                <li
                  key={s}
                  className={cn(
                    "flex items-start gap-2.5 rounded-lg border px-3 py-2.5 text-xs transition-colors",
                    done ? "border-emerald-500/30 bg-emerald-500/10 text-emerald-100" : inProgress ? "border-brand/40 bg-brand/10 text-white" : "border-white/10 bg-white/[0.03] text-slate-400",
                  )}
                >
                  <span className="mt-px shrink-0">
                    {done ? <CheckCircle2 className="size-4 text-emerald-400" /> : <Icon className={cn("size-4", inProgress ? "animate-pulse text-brand" : "text-slate-500")} />}
                  </span>
                  <span className="min-w-0 flex-1">
                    {label}
                    {inProgress && (
                      <motion.span
                        key={`${cycle}-${s}`}
                        initial={{ width: 0 }}
                        animate={{ width: "100%" }}
                        transition={{ duration: s === "signing" ? 1.6 : 2.0, ease: "easeInOut" }}
                        className="mt-1.5 block h-1 rounded-full bg-brand"
                      />
                    )}
                  </span>
                </li>
              );
            })}
            <li className={cn("rounded-lg px-3 py-2 text-[11px] transition-opacity", authorized ? "opacity-100" : "opacity-0")}>
              <span className="text-slate-400">Ambiente: </span>
              <span className="font-medium text-white">Producción</span>
              <span className="text-slate-500"> · {authorizedAt}</span>
            </li>
          </ol>
        </div>
      </BrowserFrame>
    </div>
  );
}
