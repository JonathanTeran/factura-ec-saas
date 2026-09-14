export type LandingPlan = {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  price_monthly: number;
  price_yearly: number;
  currency: string;
  is_featured: boolean;
  /** Plan a medida (Enterprise): sin precio publicado, el interesado nos contacta. */
  is_contact_sales?: boolean;
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
  /** Métodos con los que se puede pagar un plan (PayPal solo si está activo). */
  payment_methods?: { bank_transfer: boolean; paypal: boolean };
};

export function formatPrice(amount: number, currency = "USD"): string {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount);
}

export function isContactSales(plan: LandingPlan): boolean {
  return plan.is_contact_sales === true;
}

/** Planes con precio publicado (excluye los "a medida"). */
export function pricedPlans(plans: LandingPlan[]): LandingPlan[] {
  return plans.filter((plan) => !isContactSales(plan));
}

export function cheapestPlan(plans: LandingPlan[]): LandingPlan | null {
  return pricedPlans(plans).reduce<LandingPlan | null>(
    (min, plan) => (min === null || plan.price_monthly < min.price_monthly ? plan : min),
    null,
  );
}

export function maxSavings(plans: LandingPlan[]): number {
  return pricedPlans(plans).reduce((max, plan) => Math.max(max, plan.yearly_savings_percent), 0);
}

export function monthlyEquivalent(priceYearly: number): number {
  return Math.round((priceYearly / 12) * 100) / 100;
}

/**
 * Funcionalidades que, por ahora, no se promocionan en la landing aunque el
 * plan las incluya (el panel sí las muestra). Comparación sin acentos ni
 * mayúsculas para tolerar cambios de etiqueta en el backend.
 */
export const HIDDEN_PLAN_FEATURES = ["punto de venta", "inventario", "impresora termica"];

const normalize = (s: string) =>
  s
    .toLowerCase()
    .normalize("NFD")
    .replace(/[̀-ͯ]/g, "");

export function visiblePlanFeatures(features: string[]): string[] {
  return features.filter((feature) => !HIDDEN_PLAN_FEATURES.some((hidden) => normalize(feature).includes(hidden)));
}
