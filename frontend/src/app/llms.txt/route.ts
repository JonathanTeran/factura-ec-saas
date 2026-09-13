import { APP_URL, CONTACT_EMAIL, formatWhatsapp } from "@/lib/landing/config";
import { getLandingData } from "@/lib/landing/data";
import { buildLlmsTxt } from "@/lib/landing/llms";

// Dinámico: los planes vienen de la API (fetch cacheado 300 s), no del build.
export const dynamic = "force-dynamic";

export async function GET() {
  const data = await getLandingData();
  const body = buildLlmsTxt({ appUrl: APP_URL, plans: data?.plans ?? [], contactEmail: CONTACT_EMAIL, whatsapp: formatWhatsapp() });
  return new Response(body, {
    headers: { "Content-Type": "text/plain; charset=utf-8", "Cache-Control": "public, max-age=3600" },
  });
}
