import type { MetadataRoute } from "next";
import { APP_URL } from "@/lib/landing/config";
import { buildSitemap } from "@/lib/landing/seo";

export default function sitemap(): MetadataRoute.Sitemap {
  return buildSitemap(APP_URL, new Date());
}
