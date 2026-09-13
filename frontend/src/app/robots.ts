import type { MetadataRoute } from "next";
import { APP_URL } from "@/lib/landing/config";
import { buildRobots } from "@/lib/landing/seo";

export default function robots(): MetadataRoute.Robots {
  return buildRobots(APP_URL);
}
