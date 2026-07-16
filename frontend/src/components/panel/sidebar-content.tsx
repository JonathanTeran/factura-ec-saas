"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { Plus, Receipt } from "lucide-react";
import { cn } from "@/lib/utils";
import { buildNavGroups } from "./sidebar-nav";
import { useProfile } from "@/lib/api/queries/profile";

function isActive(currentPath: string, href: string) {
  if (href === "/") return currentPath === "/";
  return currentPath === href || currentPath.startsWith(`${href}/`);
}

export function SidebarContent({ onNavigate }: { onNavigate?: () => void }) {
  const pathname = usePathname();
  const { data: user } = useProfile();
  const navGroups = buildNavGroups(user?.tenant?.business_type);

  // Resalta solo el destino MÁS específico que coincide (evita que un padre
  // como /referee quede activo a la vez que /referee/reports).
  const activeHref = navGroups
    .flatMap((g) => g.items.map((i) => i.href))
    .filter((href) => isActive(pathname, href))
    .sort((a, b) => b.length - a.length)[0];

  return (
    <div className="flex h-full flex-col bg-sidebar">
      {/* Brand */}
      <div className="flex h-16 items-center gap-2.5 px-5">
        <span className="grid size-8 place-items-center rounded-lg bg-primary text-primary-foreground shadow-sm shadow-primary/30">
          <Receipt className="size-4.5" />
        </span>
        <span className="text-[15px] font-semibold tracking-tight text-foreground">
          AmePhia
        </span>
      </div>

      {/* Primary action */}
      <div className="px-3 pb-2">
        <Link
          href="/documents/new"
          onClick={onNavigate}
          className="flex h-9 items-center justify-center gap-2 rounded-lg bg-primary text-sm font-medium text-primary-foreground shadow-sm shadow-primary/25 transition hover:brightness-105 active:scale-[0.99]"
        >
          <Plus className="size-4" />
          Nueva factura
        </Link>
      </div>

      {/* Nav */}
      <nav className="flex-1 space-y-5 overflow-y-auto px-3 py-3">
        {navGroups.map((group) => (
          <div key={group.label}>
            <h3 className="px-3 pb-1.5 text-[10.5px] font-semibold uppercase tracking-[0.08em] text-muted-foreground/70">
              {group.label}
            </h3>
            <ul className="space-y-0.5">
              {group.items.map((item) => {
                const active = item.href === activeHref;
                const Icon = item.icon;
                return (
                  <li key={item.href}>
                    <Link
                      href={item.href}
                      onClick={onNavigate}
                      aria-current={active ? "page" : undefined}
                      className={cn(
                        "group relative flex items-center gap-3 rounded-lg px-3 py-2 text-[13.5px] transition-colors",
                        active
                          ? "bg-sidebar-accent font-medium text-sidebar-accent-foreground"
                          : "text-sidebar-foreground hover:bg-sidebar-accent/60 hover:text-sidebar-accent-foreground",
                      )}
                    >
                      {active && (
                        <span className="absolute left-0 top-1/2 h-5 w-0.5 -translate-y-1/2 rounded-full bg-primary" />
                      )}
                      <Icon
                        className={cn(
                          "size-4 shrink-0 transition-colors",
                          active
                            ? "text-primary"
                            : "text-muted-foreground group-hover:text-sidebar-accent-foreground",
                        )}
                      />
                      <span className="truncate">{item.label}</span>
                    </Link>
                  </li>
                );
              })}
            </ul>
          </div>
        ))}
      </nav>

      <div className="shrink-0 border-t border-sidebar-border bg-sidebar px-5 py-3 text-[11px] text-muted-foreground/60">
        AmePhia Facturación · SRI
      </div>
    </div>
  );
}
