import { Check } from "lucide-react";
import { TRUST_CHIPS } from "@/content/landing";

export function TrustStrip() {
  return (
    <ul className="mt-14 flex flex-wrap items-center gap-x-6 gap-y-3 border-t border-white/10 pt-6 text-xs text-slate-400" aria-label="Cumplimiento técnico">
      {TRUST_CHIPS.map((chip) => (
        <li key={chip} className="inline-flex items-center gap-1.5">
          <Check className="size-3.5 shrink-0 text-emerald-400" aria-hidden="true" />
          {chip}
        </li>
      ))}
    </ul>
  );
}
