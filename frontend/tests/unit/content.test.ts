import { describe, expect, it } from "vitest";
import {
  ARBITROS,
  DOCUMENT_TYPES,
  FAQS,
  FEATURES,
  HERO,
  REASONS,
  STEPS,
  TRUST_CHIPS,
} from "@/content/landing";

function allStrings(value: unknown): string[] {
  if (typeof value === "string") return [value];
  if (Array.isArray(value)) return value.flatMap(allStrings);
  if (value && typeof value === "object") return Object.values(value).flatMap(allStrings);
  return [];
}

describe("contenido de la landing", () => {
  it("tiene 13 preguntas frecuentes completas", () => {
    expect(FAQS).toHaveLength(13);
    for (const f of FAQS) {
      expect(f.q.length).toBeGreaterThan(10);
      expect(f.a.length).toBeGreaterThan(40);
    }
  });

  it("lista los 6 comprobantes del SRI con su código", () => {
    expect(DOCUMENT_TYPES.map((d) => d.code)).toEqual(["01", "03", "04", "05", "06", "07"]);
  });

  it("tiene 12 funcionalidades, 3 pasos y 3 razones", () => {
    expect(FEATURES).toHaveLength(12);
    expect(STEPS).toHaveLength(3);
    expect(REASONS).toHaveLength(3);
    expect(TRUST_CHIPS.length).toBeGreaterThanOrEqual(5);
    expect(ARBITROS.bullets).toHaveLength(4);
  });

  it("no promete pruebas gratis ni afirma que el software esté autorizado por el SRI", () => {
    const text = allStrings({ HERO, FAQS, FEATURES, REASONS, STEPS, ARBITROS, TRUST_CHIPS }).join("\n");
    expect(text).not.toMatch(/prueba gratis|gratis/i);
    expect(text).not.toMatch(/(software|sistema|plataforma) autorizad[oa] por el SRI/i);
  });
});
