"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { Menu, X } from "lucide-react";
import { NAV_LINKS } from "@/content/landing";
import { cn } from "@/lib/utils";
import { FacturonLogo } from "./logo";

const CTA = "inline-flex h-10 items-center rounded-full bg-brand px-5 text-sm font-semibold text-white shadow-lg shadow-brand/30 transition-colors hover:bg-brand-hover";

export function NavClient({ isAuthenticated }: { isAuthenticated: boolean }) {
  const [scrolled, setScrolled] = useState(false);
  const [open, setOpen] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 8);
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  return (
    <header
      className={cn(
        "fixed inset-x-0 top-0 z-50 transition-colors duration-300",
        scrolled || open ? "border-b border-white/10 bg-navy/85 backdrop-blur-md" : "bg-transparent",
      )}
    >
      <nav className="mx-auto flex h-16 max-w-6xl items-center justify-between px-5 sm:px-8" aria-label="Principal">
        <FacturonLogo tone="light" />

        <ul className="hidden items-center gap-1 lg:flex">
          {NAV_LINKS.map((link) => (
            <li key={link.href}>
              <a href={link.href} className="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-300 transition-colors hover:bg-white/5 hover:text-white">
                {link.label}
              </a>
            </li>
          ))}
        </ul>

        <div className="flex items-center gap-2">
          {isAuthenticated ? (
            <Link href="/dashboard" className={CTA}>Ir a mi panel</Link>
          ) : (
            <>
              <Link href="/login" className="hidden h-10 items-center px-4 text-sm font-medium text-slate-300 transition-colors hover:text-white sm:inline-flex">
                Ingresar
              </Link>
              <Link href="/register" className={CTA}>Crear cuenta</Link>
            </>
          )}
          <button
            type="button"
            onClick={() => setOpen((v) => !v)}
            className="ml-1 inline-flex size-10 items-center justify-center rounded-lg text-slate-300 hover:bg-white/5 hover:text-white lg:hidden"
            aria-expanded={open}
            aria-controls="menu-movil"
            aria-label={open ? "Cerrar menú" : "Abrir menú"}
          >
            {open ? <X className="size-5" /> : <Menu className="size-5" />}
          </button>
        </div>
      </nav>

      {open && (
        <div id="menu-movil" className="border-t border-white/10 bg-navy px-5 pb-6 pt-3 lg:hidden">
          <ul className="flex flex-col gap-1">
            {NAV_LINKS.map((link) => (
              <li key={link.href}>
                <a href={link.href} onClick={() => setOpen(false)} className="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-200 hover:bg-white/5">
                  {link.label}
                </a>
              </li>
            ))}
            {!isAuthenticated && (
              <li>
                <Link href="/login" className="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-200 hover:bg-white/5">
                  Ingresar
                </Link>
              </li>
            )}
          </ul>
        </div>
      )}
    </header>
  );
}
