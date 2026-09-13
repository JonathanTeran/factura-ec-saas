import type { FaqItem } from "@/content/landing";
import { pricedPlans, type LandingPlan } from "./pricing";

export type JsonLdInput = {
  baseUrl: string;
  plans: LandingPlan[];
  faqs: FaqItem[];
  featureList: string[];
  contactEmail: string;
  whatsappDigits: string;
  description: string;
};

/** Grafo schema.org: Organization + SoftwareApplication (ofertas reales) + FAQPage. */
export function buildJsonLd(input: JsonLdInput) {
  const orgId = `${input.baseUrl}/#organization`;

  const organization = {
    "@type": "Organization",
    "@id": orgId,
    name: "AmePhia Systems Inc.",
    url: "https://amephia.com",
    logo: `${input.baseUrl}/marketing/icon-facturon-512.png`,
    contactPoint: [
      {
        "@type": "ContactPoint",
        contactType: "sales",
        email: input.contactEmail,
        telephone: `+${input.whatsappDigits}`,
        areaServed: "EC",
        availableLanguage: ["es"],
      },
    ],
  };

  const priced = pricedPlans(input.plans);
  const prices = priced.map((p) => p.price_monthly);
  const offers =
    priced.length === 0
      ? {}
      : {
          offers: [
            {
              "@type": "AggregateOffer",
              priceCurrency: "USD",
              lowPrice: Math.min(...prices).toFixed(2),
              highPrice: Math.max(...prices).toFixed(2),
              offerCount: priced.length,
              offers: priced.map((p) => ({
                "@type": "Offer",
                name: p.name,
                price: p.price_monthly.toFixed(2),
                priceCurrency: p.currency,
                url: `${input.baseUrl}/register?plan=${p.slug}`,
                availability: "https://schema.org/InStock",
                category: "subscription",
              })),
            },
          ],
        };

  const software = {
    "@type": "SoftwareApplication",
    "@id": `${input.baseUrl}/#software`,
    name: "Facturón",
    alternateName: "Facturón EC",
    url: `${input.baseUrl}/`,
    description: input.description,
    applicationCategory: "BusinessApplication",
    operatingSystem: "Web, Android, iOS",
    inLanguage: "es-EC",
    countriesSupported: "EC",
    featureList: input.featureList,
    publisher: { "@id": orgId },
    ...offers,
  };

  const faq = {
    "@type": "FAQPage",
    "@id": `${input.baseUrl}/#faq`,
    mainEntity: input.faqs.map((f) => ({
      "@type": "Question",
      name: f.q,
      acceptedAnswer: { "@type": "Answer", text: f.a },
    })),
  };

  return { "@context": "https://schema.org", "@graph": [organization, software, faq] };
}
