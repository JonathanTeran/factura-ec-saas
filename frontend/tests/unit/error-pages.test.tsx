import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";

vi.mock("@/lib/fonts", () => ({ bricolage: { variable: "font-bricolage" } }));

import ErrorPage from "@/app/error";
import NotFound from "@/app/not-found";

describe("páginas de error de marca", () => {
  it("404: logo, mensaje amigable y acciones", () => {
    render(<NotFound />);

    expect(screen.getByRole("heading", { level: 1 })).toHaveTextContent("No encontramos esta página");
    expect(screen.getByRole("link", { name: "Ir al inicio" })).toHaveAttribute("href", "/");
    expect(screen.getByRole("link", { name: "Ir a mi panel" })).toHaveAttribute("href", "/dashboard");
    expect(screen.getByRole("link", { name: /Facturón, ir al inicio/ })).toBeInTheDocument();
    expect(screen.getByText(/info@amephia.com/)).toBeInTheDocument();
  });

  it("error de la app: muestra el código de referencia y permite reintentar", () => {
    const reset = vi.fn();
    const error = Object.assign(new Error("boom"), { digest: "abc123" });
    vi.spyOn(console, "error").mockImplementation(() => {});

    render(<ErrorPage error={error} reset={reset} />);

    expect(screen.getByRole("heading", { level: 1 })).toHaveTextContent("Algo salió mal de nuestro lado");
    expect(screen.getByText("abc123")).toBeInTheDocument();
    fireEvent.click(screen.getByRole("button", { name: "Reintentar" }));
    expect(reset).toHaveBeenCalledTimes(1);
  });
});
