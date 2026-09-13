/**
 * Captura el panel web con el tenant demo local para la landing.
 * Uso: DEMO_PASSWORD=… node scripts/capture-screenshots.ts
 * Requiere backend en :8001 y `pnpm dev` en :3000 (o CAPTURE_BASE_URL).
 */
import { chromium } from "@playwright/test";

const BASE = process.env.CAPTURE_BASE_URL ?? "http://localhost:3000";
const EMAIL = process.env.DEMO_EMAIL ?? "demo@amephia.com";
const PASSWORD = process.env.DEMO_PASSWORD ?? "";

const SHOTS = [
  { path: "/dashboard", file: "panel-dashboard.png", ready: "main" },
  { path: "/documents/new", file: "panel-invoice.png", ready: "form" },
  { path: "/documents", file: "panel-documents.png", ready: "table" },
];

async function main() {
  if (!PASSWORD) throw new Error("Falta DEMO_PASSWORD");
  const browser = await chromium.launch();
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
    deviceScaleFactor: 2,
    locale: "es-EC",
    colorScheme: "light",
  });
  const page = await context.newPage();

  await page.goto(`${BASE}/login`);
  await page.fill("#email", EMAIL);
  await page.fill("#password", PASSWORD);
  await page.click("button[type=submit]");
  await page.waitForURL(/\/dashboard/, { timeout: 60_000 });

  for (const shot of SHOTS) {
    await page.goto(`${BASE}${shot.path}`);
    await page.waitForSelector(shot.ready, { timeout: 60_000 });
    await page.waitForTimeout(1500);
    await page.screenshot({ path: `public/marketing/${shot.file}` });
    console.log(`✓ ${shot.file}`);
  }

  await browser.close();
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
