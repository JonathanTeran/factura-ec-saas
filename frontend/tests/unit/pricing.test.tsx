import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { Pricing } from "@/components/marketing/pricing";
import { LANDING_FIXTURE } from "@/lib/landing/fixture";

describe("Pricing", () => {
  it("muestra los planes con precio mensual, destaca el featured y enlaza al registro con el plan", () => {
    render(<Pricing data={LANDING_FIXTURE} />);
    expect(screen.getByRole("heading", { level: 2, name: LANDING_FIXTURE.pricing_content.title })).toBeInTheDocument();
    expect(screen.getByText("$7.99")).toBeInTheDocument();
    expect(screen.getByText("Más popular")).toBeInTheDocument();
    const links = screen.getAllByRole("link", { name: "Crear cuenta" });
    expect(links).toHaveLength(3);
    expect(links[1]).toHaveAttribute("href", "/register?plan=negocio");
  });

  it("el plan a medida (Enterprise) no publica precio y ofrece contacto", () => {
    render(<Pricing data={LANDING_FIXTURE} />);
    expect(screen.queryByText("$49.99")).toBeNull();
    expect(screen.getByText("A tu medida")).toBeInTheDocument();
    const contact = screen.getByRole("link", { name: "Contáctanos" });
    expect(contact).toHaveAttribute("href", expect.stringContaining("wa.me/"));
    expect(contact).toHaveAttribute("href", expect.stringContaining("Enterprise"));
    expect(screen.getByRole("link", { name: "info@facturon.ec" })).toHaveAttribute("href", "mailto:info@facturon.ec");
  });

  it("cambia a precios anuales con el switch", () => {
    render(<Pricing data={LANDING_FIXTURE} />);
    fireEvent.click(screen.getByRole("switch", { name: "Facturación anual" }));
    expect(screen.getByText("$79.90")).toBeInTheDocument();
    expect(screen.queryByText("$7.99")).toBeNull();
    expect(screen.getByText("Ahorra hasta 17 %")).toBeInTheDocument();
  });

  it("no lista punto de venta ni inventario en los planes (retirados por ahora)", () => {
    render(<Pricing data={LANDING_FIXTURE} />);
    expect(screen.queryByText("Punto de venta")).toBeNull();
    expect(screen.queryByText("Inventario")).toBeNull();
    expect(screen.queryByText("Impresora térmica")).toBeNull();
    expect(screen.getAllByText("API REST").length).toBeGreaterThan(0);
  });

  it("nunca dice gratis", () => {
    const { container } = render(<Pricing data={LANDING_FIXTURE} />);
    expect(container.textContent).not.toMatch(/gratis/i);
  });
});
