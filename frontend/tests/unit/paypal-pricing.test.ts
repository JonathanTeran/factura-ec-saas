import { describe, expect, it } from "vitest";
import { quoteAmounts } from "@/lib/api/queries/subscription";
import { FAQS, faqsForPaymentMethods } from "@/content/landing";

describe("quoteAmounts", () => {
  it("suma el IVA en centavos exactos, igual que el backend", () => {
    expect(quoteAmounts(14.99)).toEqual({ subtotal: 14.99, tax: 2.25, total: 17.24 });
    expect(quoteAmounts(2.99)).toEqual({ subtotal: 2.99, tax: 0.45, total: 3.44 });
    expect(quoteAmounts(29.9)).toEqual({ subtotal: 29.9, tax: 4.49, total: 34.39 });
  });
});

describe("faqsForPaymentMethods", () => {
  it("menciona PayPal solo en la respuesta de pago y solo si está activo", () => {
    expect(faqsForPaymentMethods(false)).toBe(FAQS);

    const withPayPal = faqsForPaymentMethods(true);
    const changed = withPayPal.filter((item, i) => item.a !== FAQS[i].a);
    expect(changed).toHaveLength(1);
    expect(changed[0].a).toMatch(/PayPal/);
    expect(changed[0].a).not.toMatch(/gratis/i);
    expect(FAQS.some((item) => /PayPal/.test(item.a))).toBe(false);
  });
});
