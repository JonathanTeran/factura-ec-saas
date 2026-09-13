import type { MetadataRoute } from "next";

/** Rutas privadas del panel: no aportan al índice y exigen sesión. */
export const PANEL_PATHS = [
  "/api/",
  "/dashboard",
  "/onboarding",
  "/settings",
  "/documents",
  "/credit-notes",
  "/debit-notes",
  "/guides",
  "/retentions",
  "/liquidations",
  "/quotes",
  "/recurring-invoices",
  "/customers",
  "/products",
  "/categories",
  "/inventory",
  "/purchases",
  "/suppliers",
  "/received-documents",
  "/pos",
  "/reports",
  "/accounting",
  "/personal-expenses",
  "/referee",
  "/support",
];

/** Rastreadores de asistentes de IA a los que se permite explícitamente el sitio público. */
export const AI_BOTS = ["GPTBot", "ChatGPT-User", "ClaudeBot", "Claude-Web", "anthropic-ai", "PerplexityBot", "Google-Extended", "Bingbot", "Applebot"];

export function buildRobots(appUrl: string): MetadataRoute.Robots {
  return {
    rules: [
      { userAgent: "*", allow: "/", disallow: PANEL_PATHS },
      ...AI_BOTS.map((userAgent) => ({ userAgent, allow: "/", disallow: PANEL_PATHS })),
    ],
    sitemap: `${appUrl}/sitemap.xml`,
    host: appUrl,
  };
}

export function buildSitemap(appUrl: string, lastModified: Date): MetadataRoute.Sitemap {
  return [
    { url: `${appUrl}/`, lastModified, changeFrequency: "weekly", priority: 1 },
    { url: `${appUrl}/register`, lastModified, changeFrequency: "monthly", priority: 0.8 },
    { url: `${appUrl}/docs/api`, lastModified, changeFrequency: "monthly", priority: 0.7 },
    { url: `${appUrl}/login`, lastModified, changeFrequency: "yearly", priority: 0.3 },
    { url: `${appUrl}/terms`, lastModified, changeFrequency: "yearly", priority: 0.2 },
    { url: `${appUrl}/privacy`, lastModified, changeFrequency: "yearly", priority: 0.2 },
  ];
}
