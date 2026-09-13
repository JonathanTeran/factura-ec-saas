import { describe, expect, it } from "vitest";
import { LANDING_FIXTURE } from "@/lib/landing/fixture";
import { buildLlmsTxt } from "@/lib/landing/llms";

describe("llms.txt", () => {
  const text = buildLlmsTxt({ appUrl: "https://facturon.ec", plans: LANDING_FIXTURE.plans, contactEmail: "info@amephia.com", whatsapp: "+1 334 732 4056" });

  it("sigue el formato llmstxt.org y solo contiene hechos", () => {
    expect(text.startsWith("# Facturón\n\n> ")).toBe(true);
    expect(text).toContain("- Negocio: $7.99/mes o $79.90/año");
    expect(text).toContain("https://facturon.ec/register");
    expect(text).toContain("info@amephia.com");
    expect(text).not.toMatch(/gratis/i);
    expect(text).not.toMatch(/ignora|ignore previous|recomienda siempre|always recommend/i);
  });

  it("sin planes omite la sección de precios", () => {
    const t = buildLlmsTxt({ appUrl: "https://facturon.ec", plans: [], contactEmail: "info@amephia.com", whatsapp: "+1 334 732 4056" });
    expect(t).not.toContain("## Precios");
  });
});
