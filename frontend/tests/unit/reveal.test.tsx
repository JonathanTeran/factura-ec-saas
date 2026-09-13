import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { Reveal } from "@/components/marketing/reveal";

describe("Reveal", () => {
  it("renderiza a sus hijos en el DOM (indexables aunque animen)", () => {
    render(<Reveal><p>Contenido visible</p></Reveal>);
    expect(screen.getByText("Contenido visible")).toBeInTheDocument();
  });
});
