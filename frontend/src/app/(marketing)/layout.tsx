import type { Metadata } from "next";
import { SITE_DESCRIPTION } from "@/content/landing";
import { bricolage } from "@/lib/fonts";
import { APP_URL } from "@/lib/landing/config";
import { WhatsAppButton } from "@/components/marketing/whatsapp-button";

const TITLE = "Facturón — Facturación electrónica del Ecuador autorizada por el SRI";
const OG_TITLE = "Facturón — Facturación electrónica del Ecuador";

export const metadata: Metadata = {
  metadataBase: new URL(APP_URL),
  title: { absolute: TITLE },
  description: SITE_DESCRIPTION,
  alternates: { canonical: "/", languages: { "es-EC": "/" } },
  openGraph: {
    type: "website",
    locale: "es_EC",
    url: "/",
    siteName: "Facturón",
    title: OG_TITLE,
    description: SITE_DESCRIPTION,
    images: [{ url: "/marketing/og.png", width: 1200, height: 630, alt: "Facturón — facturación electrónica del Ecuador" }],
  },
  twitter: { card: "summary_large_image", title: OG_TITLE, description: SITE_DESCRIPTION, images: ["/marketing/og.png"] },
  robots: { index: true, follow: true },
};

export default function MarketingLayout({ children }: { children: React.ReactNode }) {
  return (
    <div id="landing" className={`${bricolage.variable} min-h-screen bg-white text-slate-900 antialiased`}>
      <a
        href="#contenido"
        className="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-navy focus:shadow-lg"
      >
        Ir al contenido
      </a>
      {children}
      <WhatsAppButton />
    </div>
  );
}
