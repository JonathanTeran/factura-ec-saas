import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";

vi.mock("next/navigation", () => ({
  usePathname: () => "/dashboard",
  useRouter: () => ({ push: vi.fn(), replace: vi.fn(), refresh: vi.fn() }),
}));

// Las consultas del sidebar (perfil, plan, consumo) quedan pendientes: la marca
// no depende de ellas.
vi.mock("@/lib/api/client", async (importOriginal) => {
  const mod = await importOriginal<typeof import("@/lib/api/client")>();
  return { ...mod, api: { ...mod.api, get: vi.fn(() => new Promise(() => {})) } };
});

import { SidebarContent } from "@/components/panel/sidebar-content";
import { PageHeader } from "@/components/panel/page-header";
import { FacturonGlyph } from "@/components/marketing/logo";

function withQuery(ui: React.ReactNode) {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } });
  return render(<QueryClientProvider client={client}>{ui}</QueryClientProvider>);
}

describe("identidad Facturón en el panel", () => {
  it("el sidebar lleva el glifo oficial y el wordmark enlazados al dashboard", () => {
    withQuery(<SidebarContent />);

    const brand = screen.getByRole("link", { name: "Facturón, ir al panel" });
    expect(brand).toHaveAttribute("href", "/dashboard");
    expect(brand.querySelector("svg linearGradient")?.getAttribute("id")).toMatch(/^facturon-flag-/);
    expect(brand).toHaveTextContent("Facturón");
    expect(brand).toHaveTextContent("Facturación electrónica");
    expect(brand.querySelector(".font-display")).not.toBeNull();
    expect(screen.queryByText(/AmePhia Facturación/)).toBeNull();
  });

  it("cada glifo usa su propio id de degradado (varios logos en la misma página)", () => {
    const { container } = render(
      <>
        <FacturonGlyph className="size-8" />
        <FacturonGlyph className="size-8" />
      </>,
    );

    const ids = Array.from(container.querySelectorAll("linearGradient")).map((g) => g.id);
    expect(ids).toHaveLength(2);
    expect(new Set(ids).size).toBe(2);
    const fills = Array.from(container.querySelectorAll("rect[fill^='url(#']")).map((r) => r.getAttribute("fill"));
    expect(fills).toEqual(ids.map((id) => `url(#${id})`));
  });

  it("los títulos de página usan la fuente display de la marca", () => {
    render(<PageHeader title="Facturas" description="Comprobantes emitidos" />);
    const h1 = screen.getByRole("heading", { level: 1, name: "Facturas" });
    expect(h1.className).toContain("font-display");
  });
});
