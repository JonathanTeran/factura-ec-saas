// @vitest-environment node
import { NextRequest } from "next/server";
import { describe, expect, it } from "vitest";
import { proxy } from "@/proxy";

const req = (path: string, cookie?: string) =>
  new NextRequest(`http://localhost:3000${path}`, cookie ? { headers: { cookie: `factura_session=${cookie}` } } : undefined);

describe("proxy (rutas públicas y privadas)", () => {
  it("deja pasar la landing en / con y sin sesión", () => {
    expect(proxy(req("/")).headers.get("location")).toBeNull();
    expect(proxy(req("/", "abc")).headers.get("location")).toBeNull();
  });

  it("la documentación de la API (/docs/*) es pública", () => {
    expect(proxy(req("/docs/api")).headers.get("location")).toBeNull();
    expect(proxy(req("/docs/api", "abc")).headers.get("location")).toBeNull();
    expect(proxy(req("/docs")).headers.get("location")).toBeNull();
  });

  it("manda al login las rutas del panel sin sesión, conservando el destino", () => {
    const res = proxy(req("/dashboard"));
    expect(res.headers.get("location")).toBe("http://localhost:3000/login?next=%2Fdashboard");
  });

  it("con sesión, las páginas de auth van a /dashboard", () => {
    expect(proxy(req("/login", "abc")).headers.get("location")).toBe("http://localhost:3000/dashboard");
  });

  it("no toca archivos ni rutas de API (robots, sitemap, llms, assets)", () => {
    for (const path of ["/robots.txt", "/sitemap.xml", "/llms.txt", "/marketing/og.png", "/api/proxy/x"]) {
      expect(proxy(req(path)).headers.get("location")).toBeNull();
    }
  });
});
