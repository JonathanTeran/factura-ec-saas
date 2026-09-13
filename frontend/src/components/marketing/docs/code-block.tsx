import { CopyButton } from "./copy-button";

/** Bloque de código oscuro con etiqueta y botón de copiar. Sin resaltado externo. */
export function CodeBlock({ code, label, className = "" }: { code: string; label?: string; className?: string }) {
  return (
    <div className={`overflow-hidden rounded-xl border border-white/10 bg-[#0B1220] text-slate-100 shadow-sm ${className}`}>
      <div className="flex items-center justify-between border-b border-white/10 px-3 py-1.5">
        <span className="font-mono text-[11px] uppercase tracking-wider text-slate-400">{label ?? "código"}</span>
        <CopyButton text={code} />
      </div>
      <pre className="overflow-x-auto p-4 text-[12.5px] leading-relaxed">
        <code>{code}</code>
      </pre>
    </div>
  );
}
