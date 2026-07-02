"use client";

import { useState, useEffect, useSyncExternalStore } from "react";
import { createPortal } from "react-dom";
import { usePathname } from "next/navigation";
import { Menu, X } from "lucide-react";
import { SidebarContent } from "./sidebar-content";

// Devuelve `true` solo tras la hidratación en el cliente. Usar
// useSyncExternalStore evita el patrón setState-en-effect para el guard de montaje.
const emptySubscribe = () => () => {};
function useMounted() {
  return useSyncExternalStore(
    emptySubscribe,
    () => true,
    () => false,
  );
}

export function MobileNav() {
  const [open, setOpen] = useState(false);
  const mounted = useMounted();
  const pathname = usePathname();

  // Cerrar al navegar. El cierre se hace en el propio efecto de navegación
  // usando el pathname como clave.
  const [lastPath, setLastPath] = useState(pathname);
  if (pathname !== lastPath) {
    setLastPath(pathname);
    if (open) setOpen(false);
  }

  // Bloquear scroll del body mientras está abierto
  useEffect(() => {
    document.body.style.overflow = open ? "hidden" : "";
    return () => {
      document.body.style.overflow = "";
    };
  }, [open]);

  // El drawer se monta en <body> (fuera del header) para no quedar atrapado
  // por el backdrop-filter del Topbar, que crea un containing block para fixed.
  const drawer = (
    <div
      className={`fixed inset-0 z-50 lg:hidden ${open ? "" : "pointer-events-none"}`}
      aria-hidden={!open}
    >
      <div
        onClick={() => setOpen(false)}
        className={`absolute inset-0 bg-foreground/40 backdrop-blur-sm transition-opacity duration-200 ${
          open ? "opacity-100" : "opacity-0"
        }`}
      />
      <div
        role="dialog"
        aria-modal="true"
        className={`absolute inset-y-0 left-0 flex w-72 max-w-[82%] flex-col border-r border-sidebar-border bg-sidebar shadow-2xl transition-transform duration-200 ease-out ${
          open ? "translate-x-0" : "-translate-x-full"
        }`}
      >
        <button
          type="button"
          onClick={() => setOpen(false)}
          aria-label="Cerrar menú"
          className="absolute right-3 top-4 z-10 grid size-8 place-items-center rounded-lg text-muted-foreground transition hover:bg-muted hover:text-foreground"
        >
          <X className="size-4.5" />
        </button>
        <SidebarContent onNavigate={() => setOpen(false)} />
      </div>
    </div>
  );

  return (
    <>
      <button
        type="button"
        onClick={() => setOpen(true)}
        aria-label="Abrir menú"
        className="grid size-9 place-items-center rounded-lg text-muted-foreground transition hover:bg-muted hover:text-foreground lg:hidden"
      >
        <Menu className="size-5" />
      </button>

      {mounted && createPortal(drawer, document.body)}
    </>
  );
}
