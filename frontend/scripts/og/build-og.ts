/** Genera public/marketing/og.png (1200×630) a partir de og.html. Uso: node scripts/og/build-og.ts */
import path from "node:path";
import { fileURLToPath } from "node:url";
import { chromium } from "@playwright/test";

const here = path.dirname(fileURLToPath(import.meta.url));

async function main() {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1200, height: 630 }, deviceScaleFactor: 1 });
  await page.goto(`file://${path.join(here, "og.html")}`);
  await page.waitForTimeout(300);
  await page.screenshot({ path: path.join(here, "../../public/marketing/og.png"), type: "png" });
  await browser.close();
  console.log("✓ public/marketing/og.png");
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
