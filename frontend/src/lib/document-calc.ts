export type DraftItem = {
  product_id: number | null;
  main_code: string;
  description: string;
  quantity: number;
  unit_price: number;
  discount: number;
  tax_rate: number;
  // Código SRI (codigoPorcentaje). Es la fuente autoritativa: distingue casos
  // que comparten tarifa 0 (0% código '0', No objeto '6', Exento '7'). Si no
  // viene, se deriva de tax_rate.
  tax_percentage_code?: string;
};

export type CalculatedItem = DraftItem & {
  subtotal: number;
  tax_base: number;
  tax_value: number;
};

export type DocumentTotals = {
  subtotal_no_tax: number;
  subtotal_0: number;
  subtotal_5: number;
  subtotal_8: number;
  subtotal_12: number;
  subtotal_13: number;
  subtotal_15: number;
  total_tax: number;
  total_discount: number;
  total: number;
};

/**
 * Tarifas de IVA que el usuario puede elegir, con su codigoPorcentaje del SRI.
 * `rate` es el porcentaje numérico que grava; los casos sin IVA (0/no objeto/
 * exento) tienen rate 0 pero código distinto.
 */
export type TaxOption = {
  code: string;
  rate: number;
  label: string;
};

export const TAX_OPTIONS: TaxOption[] = [
  { code: "4", rate: 15, label: "15%" },
  { code: "10", rate: 13, label: "13%" },
  { code: "2", rate: 12, label: "12%" },
  { code: "8", rate: 8, label: "8%" },
  { code: "5", rate: 5, label: "5%" },
  { code: "0", rate: 0, label: "0%" },
  { code: "6", rate: 0, label: "No objeto de impuesto" },
  { code: "7", rate: 0, label: "Exento de IVA" },
];

const OPTION_BY_CODE: Record<string, TaxOption> = Object.fromEntries(
  TAX_OPTIONS.map((o) => [o.code, o]),
);

// Fallback para ítems que solo traen tarifa numérica (ej. productos viejos):
// se elige el código "gravado" natural de esa tarifa.
const CODE_BY_RATE: Record<number, string> = {
  0: "0",
  5: "5",
  8: "8",
  12: "2",
  13: "10",
  14: "3",
  15: "4",
};

/** Devuelve el código SRI efectivo de un ítem (explícito o derivado). */
export function effectiveTaxCode(item: {
  tax_percentage_code?: string;
  tax_rate: number;
}): string {
  if (item.tax_percentage_code && OPTION_BY_CODE[item.tax_percentage_code]) {
    return item.tax_percentage_code;
  }
  return CODE_BY_RATE[item.tax_rate] ?? "2";
}

export function getTaxCodes(rate: number) {
  return { tax_code: "2", tax_percentage_code: CODE_BY_RATE[rate] ?? "2" };
}

export function calcItem(item: DraftItem): CalculatedItem {
  const gross = round(item.quantity * item.unit_price);
  const discount = round(item.discount);
  const subtotal = Math.max(0, round(gross - discount));
  const tax_value = round(subtotal * (item.tax_rate / 100));
  return { ...item, subtotal, tax_base: subtotal, tax_value };
}

export function calcTotals(items: CalculatedItem[]): DocumentTotals {
  const totals: DocumentTotals = {
    subtotal_no_tax: 0,
    subtotal_0: 0,
    subtotal_5: 0,
    subtotal_8: 0,
    subtotal_12: 0,
    subtotal_13: 0,
    subtotal_15: 0,
    total_tax: 0,
    total_discount: 0,
    total: 0,
  };

  for (const it of items) {
    totals.total_discount += it.discount;
    totals.total_tax += it.tax_value;
    // Se agrupa por código SRI, no por tarifa: así "No objeto" y "Exento"
    // (tarifa 0) caen en subtotal_no_tax y no se confunden con el 0%.
    switch (effectiveTaxCode(it)) {
      case "0":
        totals.subtotal_0 += it.subtotal;
        break;
      case "5":
        totals.subtotal_5 += it.subtotal;
        break;
      case "8":
        totals.subtotal_8 += it.subtotal;
        break;
      case "2":
        totals.subtotal_12 += it.subtotal;
        break;
      case "10":
        totals.subtotal_13 += it.subtotal;
        break;
      case "4":
        totals.subtotal_15 += it.subtotal;
        break;
      case "6":
      case "7":
      default:
        totals.subtotal_no_tax += it.subtotal;
        break;
    }
  }

  const subtotalsSum =
    totals.subtotal_0 +
    totals.subtotal_5 +
    totals.subtotal_8 +
    totals.subtotal_12 +
    totals.subtotal_13 +
    totals.subtotal_15 +
    totals.subtotal_no_tax;

  totals.total = round(subtotalsSum + totals.total_tax);

  for (const k of Object.keys(totals) as Array<keyof DocumentTotals>) {
    totals[k] = round(totals[k]);
  }

  return totals;
}

function round(n: number): number {
  return Math.round(n * 100) / 100;
}

export type ItemPayload = {
  product_id: number | null;
  main_code: string;
  description: string;
  quantity: number;
  unit_price: number;
  discount: number;
  subtotal: number;
  tax_rate: number;
  tax_base: number;
  tax_value: number;
  tax_code: string;
  tax_percentage_code: string;
};

export function buildItemPayload(it: CalculatedItem, index = 0): ItemPayload {
  return {
    product_id: it.product_id,
    // Ítems manuales (sin producto del catálogo) no traen código; el SRI
    // exige codigoPrincipal, así que se genera uno.
    main_code: it.main_code || `ITEM-${index + 1}`,
    description: it.description,
    quantity: it.quantity,
    unit_price: it.unit_price,
    discount: it.discount,
    subtotal: it.subtotal,
    tax_rate: it.tax_rate,
    tax_base: it.tax_base,
    tax_value: it.tax_value,
    tax_code: "2",
    tax_percentage_code: effectiveTaxCode(it),
  };
}
