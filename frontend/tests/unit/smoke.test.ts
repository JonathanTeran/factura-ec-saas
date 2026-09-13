import { describe, expect, it } from "vitest";

describe("tooling", () => {
  it("ejecuta pruebas con jsdom", () => {
    document.body.innerHTML = "<p>hola</p>";
    expect(document.querySelector("p")?.textContent).toBe("hola");
  });
});
