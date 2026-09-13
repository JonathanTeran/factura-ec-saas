/**
 * Clave de acceso del SRI (49 dígitos) con dígito verificador módulo 11.
 * Solo para la demo de la landing: el formato es real, los datos son ficticios.
 */
export type ClaveAccesoInput = {
  date: Date;
  ruc: string;
  sequential: string;
  docType?: string;
  environment?: "1" | "2";
  series?: string;
  numericCode?: string;
  emissionType?: "1";
};

/** Pesos 2..7 cíclicos desde la derecha; 11 → 0, 10 → 1. */
export function modulo11(digits: string): number {
  if (!/^\d+$/.test(digits)) throw new Error("modulo11: solo acepta dígitos");
  let weight = 2;
  let sum = 0;
  for (let i = digits.length - 1; i >= 0; i--) {
    sum += Number(digits[i]) * weight;
    weight = weight === 7 ? 2 : weight + 1;
  }
  const result = 11 - (sum % 11);
  if (result === 11) return 0;
  if (result === 10) return 1;
  return result;
}

export function claveAcceso(input: ClaveAccesoInput): string {
  const d = input.date;
  const dd = String(d.getDate()).padStart(2, "0");
  const mm = String(d.getMonth() + 1).padStart(2, "0");
  const yyyy = String(d.getFullYear());
  const body =
    `${dd}${mm}${yyyy}` +
    (input.docType ?? "01") +
    input.ruc +
    (input.environment ?? "2") +
    (input.series ?? "001001") +
    input.sequential.padStart(9, "0") +
    (input.numericCode ?? "12345678") +
    (input.emissionType ?? "1");
  if (body.length !== 48) {
    throw new Error(`claveAcceso: el cuerpo tiene ${body.length} dígitos, se esperaban 48`);
  }
  return body + String(modulo11(body));
}
