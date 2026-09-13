/** Máquina de estados de la demo "Emisión en vivo" (sin React, testeable). */
export const DEMO_STEPS = ["draft", "signing", "sending", "authorized", "delivered"] as const;
export type DemoStep = (typeof DEMO_STEPS)[number];

export const DEMO_DURATIONS_MS: Record<DemoStep, number> = {
  draft: 2500,
  signing: 1800,
  sending: 2200,
  authorized: 4000,
  delivered: 2000,
};

export const DEMO_CYCLE_MS = Object.values(DEMO_DURATIONS_MS).reduce((a, b) => a + b, 0);

export function stepIndex(step: DemoStep): number {
  return DEMO_STEPS.indexOf(step);
}

export function nextStep(step: DemoStep): DemoStep {
  return DEMO_STEPS[(stepIndex(step) + 1) % DEMO_STEPS.length];
}

export function isAtLeast(step: DemoStep, target: DemoStep): boolean {
  return stepIndex(step) >= stepIndex(target);
}

export const DEMO_INVOICE = {
  number: "001-001-000000123",
  emitterRuc: "1791234567001",
  customer: {
    name: "Comercial Andina S.A.",
    ruc: "1790012345001",
    email: "facturacion@comercialandina.ec",
  },
  lines: [
    { description: "Servicio de consultoría", qty: 1, unit: 120, total: 120 },
    { description: "Licencia mensual", qty: 1, unit: 30, total: 30 },
  ],
  subtotal: 150,
  ivaRate: 15,
  iva: 22.5,
  total: 172.5,
} as const;
