import type { ApiSuccess } from "@/lib/api/types";
import { LANDING_FIXTURE } from "./fixture";
import type { LandingData, LandingPlan, PricingContent } from "./pricing";

export const LANDING_REVALIDATE_SECONDS = 300;

const API_BASE = (process.env.LARAVEL_API_URL ?? "http://localhost:8000").replace(/\/+$/, "");

const CONTENT_KEYS: Array<keyof PricingContent> = ["eyebrow", "title", "subtitle", "badge_enabled", "badge_text", "footer_note"];

function isPlan(value: unknown): value is LandingPlan {
  if (!value || typeof value !== "object") return false;
  const p = value as Record<string, unknown>;
  return (
    typeof p.id === "number" &&
    typeof p.name === "string" &&
    typeof p.slug === "string" &&
    typeof p.price_monthly === "number" &&
    typeof p.price_yearly === "number" &&
    Array.isArray(p.features_list)
  );
}

export function isLandingData(value: unknown): value is LandingData {
  if (!value || typeof value !== "object") return false;
  const v = value as Record<string, unknown>;
  if (!Array.isArray(v.plans) || !v.plans.every(isPlan)) return false;
  const content = v.pricing_content as Record<string, unknown> | undefined;
  if (!content || typeof content !== "object") return false;
  return CONTENT_KEYS.every((key) => key in content);
}

/**
 * Planes + textos del CMS para la landing. Con LANDING_DATA_SOURCE=fixture
 * devuelve datos de ejemplo (tests e2e, desarrollo sin backend). Ante cualquier
 * error devuelve null y la landing se renderiza sin la sección de precios.
 */
export async function getLandingData(fetcher: typeof fetch = fetch): Promise<LandingData | null> {
  if (process.env.LANDING_DATA_SOURCE === "fixture") return LANDING_FIXTURE;

  try {
    const res = await fetcher(`${API_BASE}/api/v1/public/landing`, {
      headers: { Accept: "application/json" },
      next: { revalidate: LANDING_REVALIDATE_SECONDS },
    });
    if (!res.ok) return null;
    const json = (await res.json()) as ApiSuccess<unknown>;
    return isLandingData(json.data) ? json.data : null;
  } catch {
    return null;
  }
}
