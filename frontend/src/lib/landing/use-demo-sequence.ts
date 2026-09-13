"use client";

import { useEffect, useState } from "react";
import { DEMO_DURATIONS_MS, nextStep, type DemoStep } from "./demo-machine";

export type DemoSequence = {
  step: DemoStep;
  /** Número de vuelta completa; sube cada vez que la demo vuelve a `draft`. */
  cycle: number;
};

/** Avanza la demo paso a paso mientras `running` sea true; se pausa si no. */
export function useDemoSequence(running: boolean, initial: DemoStep = "draft"): DemoSequence {
  const [sequence, setSequence] = useState<DemoSequence>({ step: initial, cycle: 0 });

  useEffect(() => {
    if (!running) return;
    const id = window.setTimeout(() => {
      setSequence((s) => {
        const step = nextStep(s.step);
        return { step, cycle: step === "draft" ? s.cycle + 1 : s.cycle };
      });
    }, DEMO_DURATIONS_MS[sequence.step]);
    return () => window.clearTimeout(id);
  }, [running, sequence.step]);

  return sequence;
}
