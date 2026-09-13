import { act, render, screen } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

// En jsdom no hay IntersectionObserver ni animaciones reales: forzamos "en pantalla"
// y AnimatePresence (mode="wait") sin esperar la salida del elemento anterior.
vi.mock("motion/react", async (importOriginal) => {
  const actual = await importOriginal<typeof import("motion/react")>();
  const Passthrough = ({ children }: { children: React.ReactNode }) => <>{children}</>;
  return { ...actual, useInView: () => true, AnimatePresence: Passthrough };
});

import { LiveDemo } from "@/components/marketing/live-demo";

describe("LiveDemo", () => {
  beforeEach(() => vi.useFakeTimers());
  afterEach(() => vi.useRealTimers());

  it("arranca en borrador y llega a AUTORIZADO con clave de acceso", () => {
    render(<LiveDemo />);
    expect(screen.getByText("001-001-000000123")).toBeInTheDocument();
    expect(screen.getByText("Borrador")).toBeInTheDocument();
    expect(screen.queryByTestId("demo-stamp")).toBeNull();

    // Cada paso programa el siguiente en un efecto: se avanza de uno en uno.
    act(() => vi.advanceTimersByTime(2500)); // signing
    act(() => vi.advanceTimersByTime(1800)); // sending
    act(() => vi.advanceTimersByTime(2200)); // authorized
    expect(screen.getByTestId("demo-stamp")).toHaveTextContent("AUTORIZADO");

    for (let i = 0; i < 49; i++) act(() => vi.advanceTimersByTime(45));
    expect(screen.getByTestId("demo-clave").textContent?.replace(/\D/g, "")).toHaveLength(49);
  });

  it("describe el flujo para lectores de pantalla", () => {
    render(<LiveDemo />);
    expect(screen.getByText(/se firma con tu certificado/i)).toBeInTheDocument();
  });
});
