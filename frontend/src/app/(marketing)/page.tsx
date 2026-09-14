import { FEATURE_LIST_FOR_SEO, SITE_DESCRIPTION, faqsForPaymentMethods } from "@/content/landing";
import { APP_URL, CONTACT_EMAIL, WHATSAPP_DIGITS } from "@/lib/landing/config";
import { getLandingData } from "@/lib/landing/data";
import { buildJsonLd } from "@/lib/landing/jsonld";
import { cheapestPlan } from "@/lib/landing/pricing";
import { Arbitros } from "@/components/marketing/arbitros";
import { FinalCta } from "@/components/marketing/cta";
import { Documents } from "@/components/marketing/documents";
import { Faq } from "@/components/marketing/faq";
import { Features } from "@/components/marketing/features";
import { SiteFooter } from "@/components/marketing/footer";
import { Hero } from "@/components/marketing/hero";
import { HowItWorks } from "@/components/marketing/how-it-works";
import { MarketingNav } from "@/components/marketing/nav";
import { Pricing } from "@/components/marketing/pricing";
import { Showcase } from "@/components/marketing/showcase";
import { Why } from "@/components/marketing/why";

// La nav lee la cookie de sesión y los precios vienen de la API (fetch con
// revalidate 300 s): la ruta se renderiza por petición, no en el build.
export const dynamic = "force-dynamic";

export default async function LandingPage() {
  const data = await getLandingData();
  const plans = data?.plans ?? [];
  const faqs = faqsForPaymentMethods(data?.payment_methods?.paypal === true);
  const jsonLd = buildJsonLd({
    baseUrl: APP_URL,
    plans,
    faqs,
    featureList: FEATURE_LIST_FOR_SEO,
    contactEmail: CONTACT_EMAIL,
    whatsappDigits: WHATSAPP_DIGITS,
    description: SITE_DESCRIPTION,
  });

  return (
    <>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }} />
      <MarketingNav />
      <main id="contenido">
        <Hero cheapest={cheapestPlan(plans)} />
        <Documents />
        <Showcase />
        <Features />
        <HowItWorks />
        {data && <Pricing data={data} />}
        <Arbitros />
        <Why />
        <Faq items={faqs} />
        <FinalCta />
      </main>
      <SiteFooter />
    </>
  );
}
