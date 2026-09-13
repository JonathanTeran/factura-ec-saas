import { describe, expect, it } from "vitest";
import { AI_BOTS, PANEL_PATHS, buildRobots, buildSitemap } from "@/lib/landing/seo";

describe("robots", () => {
  const robots = buildRobots("https://facturon.ec");
  const rules = Array.isArray(robots.rules) ? robots.rules : [robots.rules];

  it("permite todo a todos los bots, incluidos los de IA, y bloquea el panel", () => {
    const agents = rules.map((r) => r.userAgent);
    expect(agents).toContain("*");
    for (const bot of AI_BOTS) expect(agents).toContain(bot);
    for (const rule of rules) {
      expect(rule.allow).toBe("/");
      expect(rule.disallow).toEqual(PANEL_PATHS);
    }
    expect(PANEL_PATHS).toContain("/dashboard");
    expect(PANEL_PATHS).toContain("/api/");
    expect(robots.sitemap).toBe("https://facturon.ec/sitemap.xml");
  });
});

describe("sitemap", () => {
  it("lista la landing, registro, docs de la API, login y páginas legales", () => {
    const urls = buildSitemap("https://facturon.ec", new Date("2026-09-12")).map((e) => e.url);
    expect(urls).toEqual([
      "https://facturon.ec/",
      "https://facturon.ec/register",
      "https://facturon.ec/docs/api",
      "https://facturon.ec/login",
      "https://facturon.ec/terms",
      "https://facturon.ec/privacy",
    ]);
  });
});
