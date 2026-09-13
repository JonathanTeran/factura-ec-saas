import { cn } from "@/lib/utils";

function PlayGlyph() {
  return (
    <svg viewBox="0 0 24 24" className="size-5" aria-hidden="true">
      <path fill="currentColor" d="M3.6 2.3 13 12l-9.4 9.7c-.4-.2-.6-.6-.6-1.1V3.4c0-.5.2-.9.6-1.1Zm11.8 7.3L6.1 4.2l8.6 8.6 3.3-3.2Zm3.5 1.2 2.6 1.5c.7.4.7 1 0 1.4l-2.6 1.5L16 12l2.9-1.2ZM6.1 19.8l9.3-5.4-3.3-3.2-6 8.6Z" />
    </svg>
  );
}

function AppleGlyph() {
  return (
    <svg viewBox="0 0 24 24" className="size-5" aria-hidden="true">
      <path fill="currentColor" d="M16.4 12.6c0-2.4 2-3.6 2.1-3.7-1.1-1.7-2.9-1.9-3.5-1.9-1.5-.2-2.9.9-3.7.9-.8 0-1.9-.9-3.2-.8-1.6 0-3.1 1-4 2.4-1.7 3-.4 7.3 1.2 9.7.8 1.2 1.8 2.5 3 2.4 1.2 0 1.7-.8 3.2-.8s1.9.8 3.2.8c1.3 0 2.2-1.2 3-2.4.9-1.4 1.3-2.7 1.3-2.8-.1 0-2.6-1-2.6-3.8ZM14 5.5c.7-.8 1.1-2 1-3.1-1 0-2.2.7-2.9 1.5-.6.7-1.2 1.9-1 3 1.1.1 2.2-.6 2.9-1.4Z" />
    </svg>
  );
}

function Badge({ href, top, name, glyph }: { href: string; top: string; name: string; glyph: React.ReactNode }) {
  const body = (
    <>
      <span className="text-slate-300">{glyph}</span>
      <span className="flex flex-col leading-none">
        <span className="text-[10px] uppercase tracking-wide text-slate-400">{href ? top : "Próximamente"}</span>
        <span className="mt-1 text-sm font-semibold text-white">{name}</span>
      </span>
    </>
  );
  const base = "inline-flex h-12 items-center gap-2.5 rounded-xl border border-white/15 bg-black/40 px-3.5";
  if (!href) {
    return (
      <span className={cn(base, "opacity-70")} aria-disabled="true">
        {body}
      </span>
    );
  }
  return (
    <a href={href} target="_blank" rel="noopener noreferrer" className={cn(base, "transition-colors hover:border-white/30 hover:bg-black/60")}>
      {body}
    </a>
  );
}

export function StoreBadges({ playUrl = "", appStoreUrl = "", className }: { playUrl?: string; appStoreUrl?: string; className?: string }) {
  return (
    <div className={cn("flex flex-wrap gap-3", className)}>
      <Badge href={playUrl} top="Disponible en" name="Google Play" glyph={<PlayGlyph />} />
      <Badge href={appStoreUrl} top="Descárgala en" name="App Store" glyph={<AppleGlyph />} />
    </div>
  );
}
