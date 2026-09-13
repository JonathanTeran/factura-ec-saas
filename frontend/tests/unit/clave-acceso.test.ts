import { describe, expect, it } from "vitest";
import { claveAcceso, modulo11 } from "@/lib/landing/clave-acceso";

describe("modulo11", () => {
  it("aplica pesos 2..7 desde la derecha y 11 - (suma mod 11)", () => {
    expect(modulo11("1")).toBe(9); // 1*2=2 → 11-2
    expect(modulo11("10")).toBe(8); // 0*2+1*3=3 → 11-3
  });

  it("mapea 11 → 0 y 10 → 1", () => {
    expect(modulo11("0")).toBe(0); // suma 0 → 11 → 0
    expect(modulo11("6")).toBe(1); // 6*2=12 → 12 mod 11 = 1 → 10 → 1
  });

  it("rechaza no dígitos", () => {
    expect(() => modulo11("12a")).toThrow();
  });
});

describe("claveAcceso", () => {
  const clave = claveAcceso({ date: new Date(2026, 8, 12), ruc: "1790012345001", sequential: "123" });

  it("tiene 49 dígitos con la estructura del SRI", () => {
    expect(clave).toMatch(/^\d{49}$/);
    expect(clave.slice(0, 8)).toBe("12092026"); // ddmmaaaa
    expect(clave.slice(8, 10)).toBe("01"); // factura
    expect(clave.slice(10, 23)).toBe("1790012345001"); // RUC
    expect(clave.slice(23, 24)).toBe("2"); // producción
    expect(clave.slice(24, 30)).toBe("001001"); // serie
    expect(clave.slice(30, 39)).toBe("000000123"); // secuencial
    expect(clave.slice(47, 48)).toBe("1"); // emisión normal
  });

  it("termina con el dígito verificador de los primeros 48", () => {
    expect(Number(clave[48])).toBe(modulo11(clave.slice(0, 48)));
  });

  it("falla si el cuerpo no mide 48 dígitos", () => {
    expect(() => claveAcceso({ date: new Date(2026, 8, 12), ruc: "123", sequential: "1" })).toThrow(/48/);
  });
});
