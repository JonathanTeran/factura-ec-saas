import { describe, expect, it } from "vitest";
import { PASSWORD_RULES, passwordChecks } from "@/lib/auth/password-rules";

describe("passwordChecks", () => {
  it("evalúa las 4 reglas de la política (8+, mayúscula, minúscula, especial)", () => {
    expect(PASSWORD_RULES.map((r) => r.id)).toEqual(["length", "upper", "lower", "special"]);
    expect(passwordChecks("")).toEqual({ length: false, upper: false, lower: false, special: false });
    expect(passwordChecks("abcdefgh")).toEqual({ length: true, upper: false, lower: true, special: false });
    expect(passwordChecks("Abcdef1!")).toEqual({ length: true, upper: true, lower: true, special: true });
    expect(passwordChecks("ABC$")).toEqual({ length: false, upper: true, lower: false, special: true });
  });
});
