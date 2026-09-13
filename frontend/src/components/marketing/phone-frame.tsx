import { cn } from "@/lib/utils";

export function PhoneFrame({ children, className }: { children: React.ReactNode; className?: string }) {
  return (
    <div className={cn("mx-auto w-[260px] rounded-[2.4rem] border-[6px] border-slate-900 bg-slate-900 p-1.5 shadow-2xl shadow-slate-900/30", className)}>
      <div className="overflow-hidden rounded-[1.9rem] bg-white">{children}</div>
    </div>
  );
}
