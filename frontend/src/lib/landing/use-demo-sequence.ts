"use client";

import { useEffect, useState } from "react";
import { DEMO_DURATIONS_MS, nextStep, type DemoStep } from "./demo-machine";

/** Avanza la demo paso a paso mientras `running` sea true; se pausa si no. */
export function useDemoSequence(running: boolean, initial: DemoStep = "draft"): DemoStep {
  const [step, setStep] = useState<DemoStep>(initial);

  useEffect(() => {
    if (!running) return;
    const id = window.setTimeout(() => setStep((s) => nextStep(s)), DEMO_DURATIONS_MS[step]);
    return () => window.clearTimeout(id);
  }, [running, step]);

  return step;
}
