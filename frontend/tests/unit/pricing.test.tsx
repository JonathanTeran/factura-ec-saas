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
    expect(links).toHaveLength(4);
    expect(links[1]).toHaveAttribute("href", "/register?plan=negocio");
  });

  it("cambia a precios anuales con el switch", () => {
    render(<Pricing data={LANDING_FIXTURE} />);
    fireEvent.click(screen.getByRole("switch", { name: "Facturación anual" }));
    expect(screen.getByText("$79.90")).toBeInTheDocument();
    expect(screen.queryByText("$7.99")).toBeNull();
    expect(screen.getByText("Ahorra hasta 17 %")).toBeInTheDocument();
  });

  it("nunca dice gratis", () => {
    const { container } = render(<Pricing data={LANDING_FIXTURE} />);
    expect(container.textContent).not.toMatch(/gratis/i);
  });
});
