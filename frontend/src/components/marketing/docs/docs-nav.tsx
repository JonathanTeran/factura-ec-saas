"use client";

import { useEffect, useState } from "react";
import { DOC_NAV } from "@/content/api-docs";

/** Índice lateral con resaltado de la sección visible. */
export function DocsNav() {
  const [active, setActive] = useState<string>(DOC_NAV[0].id);

  useEffect(() => {
    if (typeof IntersectionObserver === "undefined") return;
    const sections = DOC_NAV.map((n) => document.getElementById(n.id)).filter((el): el is HTMLElement => el !== null);
    const observer = new IntersectionObserver(
      (entries) => {
        const visible = entries.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
        if (visible[0]) setActive(visible[0].target.id);
      },
      { rootMargin: "-96px 0px -70% 0px", threshold: 0 },
    );
    sections.forEach((s) => observer.observe(s));
    return () => observer.disconnect();
  }, []);

  return (
    <nav aria-label="Secciones de la documentación" className="text-sm">
      <p className="mb-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Contenido</p>
      <ul className="space-y-0.5">
        {DOC_NAV.map((item) => {
          const isActive = active === item.id;
          return (
            <li key={item.id}>
              <a
                href={`#${item.id}`}
                aria-current={isActive ? "location" : undefined}
                className={`block rounded-md border-l-2 py-1.5 pl-3 pr-2 transition-colors ${
                  isActive
                    ? "border-[#2B54E4] bg-[#EEF2FF] font-semibold text-[#2446C4]"
                    : "border-transparent text-slate-600 hover:border-slate-300 hover:text-navy"
                }`}
              >
                {item.label}
              </a>
            </li>
          );
        })}
      </ul>
    </nav>
  );
}
