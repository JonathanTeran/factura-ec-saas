import { Lock } from "lucide-react";
import { cn } from "@/lib/utils";

export function BrowserFrame({ url, children, className }: { url: string; children: React.ReactNode; className?: string }) {
  return (
    <div className={cn("overflow-hidden rounded-2xl border border-white/10 bg-navy-3 shadow-2xl shadow-black/40", className)}>
      <div className="flex items-center gap-3 border-b border-white/10 bg-black/20 px-4 py-2.5">
        <div className="flex gap-1.5" aria-hidden="true">
          <span className="size-2.5 rounded-full bg-white/15" />
          <span className="size-2.5 rounded-full bg-white/15" />
          <span className="size-2.5 rounded-full bg-white/15" />
        </div>
        <div className="mx-auto flex items-center gap-1.5 rounded-md bg-white/5 px-3 py-1 font-mono text-[11px] text-slate-400">
          <Lock className="size-3 text-emerald-400" aria-hidden="true" />
          {url}
        </div>
      </div>
      {children}
    </div>
  );
}
