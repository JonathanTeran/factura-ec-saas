export type LandingPlan = {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  price_monthly: number;
  price_yearly: number;
  currency: string;
  is_featured: boolean;
  yearly_savings_percent: number;
  features_list: string[];
};

export type PricingContent = {
  eyebrow: string;
  title: string;
  subtitle: string;
  badge_enabled: boolean;
  badge_text: string;
  footer_note: string;
};

export type LandingData = {
  plans: LandingPlan[];
  pricing_content: PricingContent;
};

export function formatPrice(amount: number, currency = "USD"): string {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount);
}

export function cheapestPlan(plans: LandingPlan[]): LandingPlan | null {
  return plans.reduce<LandingPlan | null>(
    (min, plan) => (min === null || plan.price_monthly < min.price_monthly ? plan : min),
    null,
  );
}

export function maxSavings(plans: LandingPlan[]): number {
  return plans.reduce((max, plan) => Math.max(max, plan.yearly_savings_percent), 0);
}

export function monthlyEquivalent(priceYearly: number): number {
  return Math.round((priceYearly / 12) * 100) / 100;
}
