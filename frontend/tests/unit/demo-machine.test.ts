import { describe, expect, it } from "vitest";
import {
  DEMO_CYCLE_MS,
  DEMO_DURATIONS_MS,
  DEMO_INVOICE,
  DEMO_STEPS,
  isAtLeast,
  nextStep,
} from "@/lib/landing/demo-machine";

describe("demo-machine", () => {
  it("recorre los 5 pasos y vuelve al inicio", () => {
    expect(DEMO_STEPS).toEqual(["draft", "signing", "sending", "authorized", "delivered"]);
    expect(nextStep("draft")).toBe("signing");
    expect(nextStep("delivered")).toBe("draft");
  });

  it("dura 12,5 s por ciclo", () => {
    const total = Object.values(DEMO_DURATIONS_MS).reduce((a, b) => a + b, 0);
    expect(total).toBe(12_500);
    expect(DEMO_CYCLE_MS).toBe(total);
  });

  it("compara el avance", () => {
    expect(isAtLeast("authorized", "signing")).toBe(true);
    expect(isAtLeast("draft", "signing")).toBe(false);
    expect(isAtLeast("sending", "sending")).toBe(true);
  });

  it("los totales de la factura de ejemplo cuadran", () => {
    const subtotal = DEMO_INVOICE.lines.reduce((s, l) => s + l.total, 0);
    expect(subtotal).toBe(DEMO_INVOICE.subtotal);
    expect(DEMO_INVOICE.iva).toBeCloseTo(subtotal * (DEMO_INVOICE.ivaRate / 100), 2);
    expect(DEMO_INVOICE.total).toBeCloseTo(subtotal + DEMO_INVOICE.iva, 2);
  });
});
