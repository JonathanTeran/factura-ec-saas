import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { Documents } from "@/components/marketing/documents";
import { Features } from "@/components/marketing/features";
import { HowItWorks } from "@/components/marketing/how-it-works";
import { Showcase } from "@/components/marketing/showcase";

describe("secciones", () => {
  it("Documents muestra los 6 comprobantes con código", () => {
    render(<Documents />);
    for (const code of ["01", "03", "04", "05", "06", "07"]) {
      expect(screen.getByText(code)).toBeInTheDocument();
    }
    expect(screen.getByRole("heading", { level: 2, name: "Todo lo que emites" })).toBeInTheDocument();
  });

  it("Features tiene el ancla y 12 tarjetas", () => {
    const { container } = render(<Features />);
    expect(container.querySelector("section#funcionalidades")).not.toBeNull();
    expect(screen.getAllByRole("heading", { level: 3 })).toHaveLength(12);
  });

  it("HowItWorks tiene el ancla y 3 pasos", () => {
    const { container } = render(<HowItWorks />);
    expect(container.querySelector("section#como-funciona")).not.toBeNull();
    expect(screen.getAllByRole("heading", { level: 3 })).toHaveLength(3);
  });

  it("Showcase usa capturas reales con alt descriptivo", () => {
    render(<Showcase />);
    const images = screen.getAllByRole("img");
    expect(images).toHaveLength(3);
    for (const img of images) {
      expect(img.getAttribute("alt")?.length).toBeGreaterThan(20);
    }
  });
});
