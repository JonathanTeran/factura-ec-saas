import Link from "next/link";
import { Receipt, ShieldCheck, Building2, Zap } from "lucide-react";

const FEATURES = [
  {
    icon: ShieldCheck,
    title: "Autorización SRI en segundos",
    desc: "Firma electrónica y envío al SRI automatizados.",
  },
  {
    icon: Building2,
    title: "Multi-empresa y multi-sucursal",
    desc: "Gestiona todos tus RUC desde una sola cuenta.",
  },
  {
    icon: Zap,
    title: "POS, compras y contabilidad",
    desc: "Todo tu negocio integrado en un panel.",
  },
];

export default function AuthLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className="grid min-h-screen lg:grid-cols-[1.05fr_1fr]">
      {/* Brand panel */}
      <div className="relative hidden overflow-hidden bg-sidebar text-sidebar-foreground lg:flex lg:flex-col lg:justify-between lg:p-12">
        {/* Atmosphere */}
        <div
          aria-hidden
          className="pointer-events-none absolute inset-0"
          style={{
            background:
              "radial-gradient(120% 90% at 15% 0%, hsl(172 66% 42% / 0.35), transparent 55%), radial-gradient(100% 80% at 100% 100%, hsl(199 89% 48% / 0.22), transparent 50%)",
          }}
        />
        <div
          aria-hidden
          className="pointer-events-none absolute inset-0 opacity-[0.15]"
          style={{
            backgroundImage:
              "radial-gradient(hsl(210 40% 96% / 0.5) 1px, transparent 1px)",
            backgroundSize: "22px 22px",
            maskImage:
              "linear-gradient(to bottom, black, transparent 85%)",
          }}
        />

        <div className="relative">
          <Link
            href="/"
            className="inline-flex items-center gap-2.5 text-lg font-semibold tracking-tight"
          >
            <span className="grid size-9 place-items-center rounded-xl bg-sidebar-primary text-sidebar-primary-foreground shadow-lg shadow-black/20">
              <Receipt className="size-5" />
            </span>
            AmePhia Facturación
          </Link>
        </div>

        <div className="relative max-w-md space-y-10">
          <div className="space-y-4">
            <h1 className="text-[2.6rem] font-semibold leading-[1.05] tracking-tight">
              Facturación electrónica sin dolores de cabeza.
            </h1>
            <p className="text-sidebar-foreground/70">
              La plataforma de facturación SRI hecha para negocios en Ecuador.
            </p>
          </div>

          <ul className="space-y-5">
            {FEATURES.map(({ icon: Icon, title, desc }) => (
              <li key={title} className="flex items-start gap-3.5">
                <span className="mt-0.5 grid size-9 shrink-0 place-items-center rounded-lg bg-sidebar-accent ring-1 ring-white/5">
                  <Icon className="size-4.5 text-sidebar-primary" />
                </span>
                <div className="space-y-0.5">
                  <p className="text-sm font-medium">{title}</p>
                  <p className="text-sm text-sidebar-foreground/60">{desc}</p>
                </div>
              </li>
            ))}
          </ul>
        </div>

        <div className="relative flex items-center gap-2 text-xs text-sidebar-foreground/50">
          <ShieldCheck className="size-3.5" />
          Autorizado por el Servicio de Rentas Internas
        </div>
      </div>

      {/* Form panel */}
      <div className="flex items-center justify-center bg-background px-6 py-10 lg:px-10">
        <div className="w-full max-w-sm">
          {/* Mobile logo */}
          <Link
            href="/"
            className="mb-8 inline-flex items-center gap-2.5 text-base font-semibold tracking-tight lg:hidden"
          >
            <span className="grid size-8 place-items-center rounded-lg bg-primary text-primary-foreground">
              <Receipt className="size-4.5" />
            </span>
            AmePhia Facturación
          </Link>
          {children}
        </div>
      </div>
    </div>
  );
}
