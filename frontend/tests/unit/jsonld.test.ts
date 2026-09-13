import { describe, expect, it } from "vitest";
import { FAQS, FEATURE_LIST_FOR_SEO, SITE_DESCRIPTION } from "@/content/landing";
import { LANDING_FIXTURE } from "@/lib/landing/fixture";
import { buildJsonLd } from "@/lib/landing/jsonld";

const input = {
  baseUrl: "https://facturon.ec",
  plans: LANDING_FIXTURE.plans,
  faqs: FAQS,
  featureList: FEATURE_LIST_FOR_SEO,
  contactEmail: "info@amephia.com",
  whatsappDigits: "13347324056",
  description: SITE_DESCRIPTION,
};

type Node = Record<string, unknown> & { "@type": string };

describe("buildJsonLd", () => {
  const graph = buildJsonLd(input)["@graph"] as Node[];
  const byType = (t: string) => graph.find((n) => n["@type"] === t) as Node;

  it("tiene Organization, SoftwareApplication y FAQPage", () => {
    expect(graph.map((n) => n["@type"])).toEqual(["Organization", "SoftwareApplication", "FAQPage"]);
  });

  it("la Organization lleva contacto y el software la referencia", () => {
    const org = byType("Organization");
    expect(org.name).toBe("AmePhia Systems Inc.");
    const contact = (org.contactPoint as Array<Record<string, unknown>>)[0];
    expect(contact.email).toBe("info@amephia.com");
    expect(contact.telephone).toBe("+13347324056");
    expect((byType("SoftwareApplication").publisher as { "@id": string })["@id"]).toBe("https://facturon.ec/#organization");
  });

  it("las ofertas son los planes reales en USD, sin aggregateRating", () => {
    const app = byType("SoftwareApplication");
    const aggregate = (app.offers as Array<Record<string, unknown>>)[0];
    expect(aggregate["@type"]).toBe("AggregateOffer");
    expect(aggregate.lowPrice).toBe("2.99");
    expect(aggregate.highPrice).toBe("49.99");
    expect((aggregate.offers as unknown[]).length).toBe(4);
    expect(app.aggregateRating).toBeUndefined();
    expect(app.url).toBe("https://facturon.ec/");
  });

  it("sin planes no incluye offers", () => {
    const app = (buildJsonLd({ ...input, plans: [] })["@graph"] as Node[])[1];
    expect(app.offers).toBeUndefined();
  });

  it("la FAQPage tiene las 13 preguntas", () => {
    expect((byType("FAQPage").mainEntity as unknown[]).length).toBe(14);
  });
});
