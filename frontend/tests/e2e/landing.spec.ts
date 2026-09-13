import { expect, test } from "@playwright/test";

test.describe("landing", () => {
  test("hero, un solo h1, CTA visible y sin errores de consola", async ({ page }) => {
    const errors: string[] = [];
    page.on("console", (msg) => {
      if (msg.type() === "error") errors.push(msg.text());
    });
    await page.goto("/");
    await expect(page.locator("h1")).toHaveCount(1);
    await expect(page.getByRole("heading", { level: 1 })).toHaveText("Facturación electrónica que el SRI autoriza en segundos");
    await expect(page.getByRole("link", { name: "Crear cuenta" }).first()).toBeInViewport();
    expect(errors).toEqual([]);
  });

  test("precios del fixture con toggle anual y enlace al registro por plan", async ({ page }) => {
    await page.goto("/");
    const pricing = page.locator("#precios");
    await pricing.scrollIntoViewIfNeeded();
    await expect(pricing.getByText("Negocio", { exact: true })).toBeVisible();
    await expect(pricing.getByText("$7.99", { exact: true })).toBeVisible();
    await pricing.getByRole("switch", { name: "Facturación anual" }).click();
    await expect(pricing.getByText("$79.90", { exact: true })).toBeVisible();
    await expect(pricing.getByRole("link", { name: "Crear cuenta" }).nth(1)).toHaveAttribute("href", "/register?plan=negocio");
  });

  test("JSON-LD con Organization, SoftwareApplication y FAQPage", async ({ page }) => {
    await page.goto("/");
    const raw = await page.locator('script[type="application/ld+json"]').first().textContent();
    const json = JSON.parse(raw ?? "{}") as { "@graph": Array<{ "@type": string }> };
    expect(json["@graph"].map((n) => n["@type"])).toEqual(["Organization", "SoftwareApplication", "FAQPage"]);
  });

  test("robots, sitemap y llms.txt", async ({ request }) => {
    const robots = await request.get("/robots.txt");
    expect(robots.ok()).toBe(true);
    const robotsText = await robots.text();
    expect(robotsText).toContain("User-Agent: GPTBot");
    expect(robotsText).toContain("Disallow: /dashboard");

    const sitemap = await request.get("/sitemap.xml");
    expect(sitemap.ok()).toBe(true);
    expect(await sitemap.text()).toContain("/register");

    const llms = await request.get("/llms.txt");
    expect(llms.ok()).toBe(true);
    expect((await llms.text()).startsWith("# Facturón")).toBe(true);
  });

  test("el cuerpo sigue claro aunque el sistema esté en dark", async ({ browser }) => {
    const context = await browser.newContext({ colorScheme: "dark" });
    const page = await context.newPage();
    await page.goto("/");
    const bg = await page.locator("#landing").evaluate((el) => getComputedStyle(el).backgroundColor);
    expect(bg).toBe("rgb(255, 255, 255)");
    await context.close();
  });

  test("con reduced motion la demo muestra AUTORIZADO de inmediato", async ({ browser }) => {
    const context = await browser.newContext({ reducedMotion: "reduce" });
    const page = await context.newPage();
    await page.goto("/");
    await expect(page.getByTestId("demo-stamp")).toHaveText("AUTORIZADO");
    await context.close();
  });

  test("sin scroll horizontal", async ({ page }) => {
    await page.goto("/");
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 1);
    expect(overflow).toBe(false);
  });

  test("/dashboard sin sesión termina en /login", async ({ page }) => {
    await page.goto("/dashboard");
    await expect(page).toHaveURL(/\/login/);
  });
});

test.describe("documentación de la API", () => {
  test("/docs/api renderiza la guía con índice, referencia y OpenAPI", async ({ page }) => {
    const errors: string[] = [];
    page.on("console", (msg) => {
      if (msg.type() === "error") errors.push(msg.text());
    });
    await page.goto("/docs/api");
    await expect(page.locator("h1")).toHaveCount(1);
    await expect(page.getByRole("heading", { level: 1 })).toHaveText("Emite comprobantes del SRI desde tu propio sistema");
    // El índice lateral solo se muestra en escritorio (lg:); en móvil sigue en el DOM.
    await expect(page.locator('nav[aria-label="Secciones de la documentación"] a[href="#idempotencia"]')).toHaveCount(1);
    await expect(page.locator("#idempotencia")).toBeAttached();
    await expect(page.locator("#post-documents")).toContainText("/documents");
    await expect(page.getByRole("link", { name: /OpenAPI 3.1/ })).toHaveAttribute("href", "/docs/openapi.yaml");
    expect(errors).toEqual([]);
  });

  test("openapi.yaml se sirve y el sitemap incluye /docs/api", async ({ request }) => {
    const spec = await request.get("/docs/openapi.yaml");
    expect(spec.ok()).toBe(true);
    expect(await spec.text()).toContain("openapi: 3.1.0");
    const sitemap = await request.get("/sitemap.xml");
    expect(await sitemap.text()).toContain("/docs/api");
  });

  test("la página de docs es pública también sin sesión y no redirige", async ({ page }) => {
    const response = await page.goto("/docs/api");
    expect(response?.status()).toBe(200);
    await expect(page).toHaveURL(/\/docs\/api$/);
  });
});

test.describe("páginas de error", () => {
  test("una ruta inexistente responde 404 con la página de marca", async ({ page }) => {
    const response = await page.goto("/esta-ruta-no-existe");
    expect(response?.status()).toBe(404);
    await expect(page.getByRole("heading", { level: 1 })).toHaveText("No encontramos esta página");
    await expect(page.getByRole("link", { name: "Ir al inicio", exact: true })).toBeVisible();
  });
});
