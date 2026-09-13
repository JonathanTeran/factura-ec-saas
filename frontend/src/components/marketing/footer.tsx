import Link from "next/link";
import { CONTACT_EMAIL, STORE_APPSTORE_URL, STORE_PLAY_URL, formatWhatsapp, whatsappUrl } from "@/lib/landing/config";
import { FacturonLogo } from "./logo";

type FooterLink = { label: string; href: string; disabled?: boolean };

const NEXT_ROUTES = new Set(["/login", "/register", "/dashboard"]);

const COLUMNS: Array<{ title: string; links: FooterLink[] }> = [
  {
    title: "Producto",
    links: [
      { label: "Funcionalidades", href: "#funcionalidades" },
      { label: "Precios", href: "#precios" },
      { label: "Preguntas frecuentes", href: "#faq" },
      { label: "App para Android", href: STORE_PLAY_URL, disabled: STORE_PLAY_URL === "" },
      { label: "App para iOS", href: STORE_APPSTORE_URL, disabled: STORE_APPSTORE_URL === "" },
    ],
  },
  {
    title: "Cuenta",
    links: [
      { label: "Ingresar", href: "/login" },
      { label: "Crear cuenta", href: "/register" },
    ],
  },
  {
    title: "Legal",
    links: [
      { label: "Términos y condiciones", href: "/terms" },
      { label: "Política de privacidad", href: "/privacy" },
      { label: "Eliminación de cuenta", href: "/delete-account" },
    ],
  },
  {
    title: "Contacto",
    links: [
      { label: `WhatsApp ${formatWhatsapp()}`, href: whatsappUrl() },
      { label: CONTACT_EMAIL, href: `mailto:${CONTACT_EMAIL}` },
      { label: "AmePhia Systems", href: "https://amephia.com" },
    ],
  },
];

function FooterAnchor({ link }: { link: FooterLink }) {
  const cls = "text-sm text-slate-500 transition-colors hover:text-navy";
  if (link.disabled) {
    return (
      <span className="text-sm text-slate-500">
        {link.label} <span className="text-xs">(próximamente)</span>
      </span>
    );
  }
  if (NEXT_ROUTES.has(link.href)) {
    return <Link href={link.href} className={cls}>{link.label}</Link>;
  }
  const external = link.href.startsWith("http") || link.href.startsWith("mailto:");
  return (
    <a href={link.href} className={cls} {...(external ? { target: "_blank", rel: "noopener noreferrer" } : {})}>
      {link.label}
    </a>
  );
}

export function SiteFooter() {
  return (
    <footer className="border-t border-slate-200 bg-white">
      <div className="mx-auto max-w-6xl px-5 py-14 sm:px-8">
        <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-12">
          <div className="lg:col-span-4">
            <FacturonLogo />
            <p className="mt-3 max-w-xs text-sm leading-relaxed text-slate-500">
              Facturación electrónica del Ecuador. Comprobantes autorizados por el SRI, firmados con tu certificado.
            </p>
          </div>
          {COLUMNS.map((col) => (
            <div key={col.title} className="lg:col-span-2">
              <h2 className="text-xs font-semibold uppercase tracking-wider text-navy">{col.title}</h2>
              <ul className="mt-4 space-y-2.5">
                {col.links.map((link) => (
                  <li key={link.label}>
                    <FooterAnchor link={link} />
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>

        <div className="mt-12 flex flex-col gap-4 border-t border-slate-200 pt-8 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
          <p>© {new Date().getFullYear()} AmePhia Systems Inc. · Hecho en Ecuador 🇪🇨</p>
          <p className="max-w-xl sm:text-right">
            Facturón es un producto de AmePhia Systems Inc. No está afiliado al SRI; la autorización de cada comprobante la emite el SRI.
          </p>
        </div>
      </div>
    </footer>
  );
}
