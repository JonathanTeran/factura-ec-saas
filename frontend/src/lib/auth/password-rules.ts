/** Política de contraseñas del backend (bc2237a): 8+, mayúscula, minúscula y carácter especial. */
export const PASSWORD_RULES = [
  { id: "length", label: "8 caracteres o más", test: (p: string) => p.length >= 8 },
  { id: "upper", label: "Una mayúscula", test: (p: string) => /[A-ZÁÉÍÓÚÑ]/.test(p) },
  { id: "lower", label: "Una minúscula", test: (p: string) => /[a-záéíóúñ]/.test(p) },
  { id: "special", label: "Un carácter especial", test: (p: string) => /[^A-Za-z0-9ÁÉÍÓÚÑáéíóúñ]/.test(p) },
] as const;

export type PasswordRuleId = (typeof PASSWORD_RULES)[number]["id"];

export function passwordChecks(password: string): Record<PasswordRuleId, boolean> {
  return Object.fromEntries(PASSWORD_RULES.map((r) => [r.id, r.test(password)])) as Record<PasswordRuleId, boolean>;
}
