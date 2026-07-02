export type DraftItem = {
  product_id: number | null;
  main_code: string;
  description: string;
  quantity: number;
  unit_price: number;
  discount: number;
  tax_rate: number;
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
  subtotal_12: number;
  subtotal_15: number;
  total_tax: number;
  total_discount: number;
  total: number;
};

const TAX_CODE_BY_RATE: Record<number, { tax_code: string; tax_percentage_code: string }> = {
  0: { tax_code: "2", tax_percentage_code: "0" },
  5: { tax_code: "2", tax_percentage_code: "5" },
  12: { tax_code: "2", tax_percentage_code: "2" },
  13: { tax_code: "2", tax_percentage_code: "10" },
  14: { tax_code: "2", tax_percentage_code: "3" },
  15: { tax_code: "2", tax_percentage_code: "4" },
};

export function getTaxCodes(rate: number) {
  return TAX_CODE_BY_RATE[rate] ?? TAX_CODE_BY_RATE[12];
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
    subtotal_12: 0,
    subtotal_15: 0,
    total_tax: 0,
    total_discount: 0,
    total: 0,
  };

  for (const it of items) {
    totals.total_discount += it.discount;
    totals.total_tax += it.tax_value;
    if (it.tax_rate === 0) totals.subtotal_0 += it.subtotal;
    else if (it.tax_rate === 5) totals.subtotal_5 += it.subtotal;
    else if (it.tax_rate === 12) totals.subtotal_12 += it.subtotal;
    else if (it.tax_rate === 15) totals.subtotal_15 += it.subtotal;
    else totals.subtotal_no_tax += it.subtotal;
  }

  const subtotalsSum =
    totals.subtotal_0 +
    totals.subtotal_5 +
    totals.subtotal_12 +
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

export function buildItemPayload(it: CalculatedItem): ItemPayload {
  const codes = getTaxCodes(it.tax_rate);
  return {
    product_id: it.product_id,
    main_code: it.main_code,
    description: it.description,
    quantity: it.quantity,
    unit_price: it.unit_price,
    discount: it.discount,
    subtotal: it.subtotal,
    tax_rate: it.tax_rate,
    tax_base: it.tax_base,
    tax_value: it.tax_value,
    tax_code: codes.tax_code,
    tax_percentage_code: codes.tax_percentage_code,
  };
}
