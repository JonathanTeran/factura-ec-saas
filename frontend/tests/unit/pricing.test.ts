import { describe, expect, it } from "vitest";
import {
  cheapestPlan,
  formatPrice,
  maxSavings,
  monthlyEquivalent,
  type LandingPlan,
} from "@/lib/landing/pricing";

const plan = (over: Partial<LandingPlan>): LandingPlan => ({
  id: 1,
  name: "Plan",
  slug: "plan",
  description: "",
  price_monthly: 7.99,
  price_yearly: 79.9,
  currency: "USD",
  is_featured: false,
  yearly_savings_percent: 17,
  features_list: [],
  ...over,
});

describe("pricing", () => {
  it("formatea en dólares con dos decimales", () => {
    expect(formatPrice(2.99)).toBe("$2.99");
    expect(formatPrice(499)).toBe("$499.00");
  });

  it("encuentra el plan más barato", () => {
    const plans = [plan({ slug: "b", price_monthly: 7.99 }), plan({ slug: "a", price_monthly: 2.99 })];
    expect(cheapestPlan(plans)?.slug).toBe("a");
    expect(cheapestPlan([])).toBeNull();
  });

  it("calcula el ahorro máximo y el equivalente mensual", () => {
    expect(maxSavings([plan({ yearly_savings_percent: 10 }), plan({ yearly_savings_percent: 17 })])).toBe(17);
    expect(maxSavings([])).toBe(0);
    expect(monthlyEquivalent(29.9)).toBe(2.49);
  });
});
