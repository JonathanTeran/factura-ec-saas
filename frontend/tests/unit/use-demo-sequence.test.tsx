import { act, renderHook } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { useDemoSequence } from "@/lib/landing/use-demo-sequence";

describe("useDemoSequence", () => {
  beforeEach(() => vi.useFakeTimers());
  afterEach(() => vi.useRealTimers());

  it("avanza según las duraciones cuando está corriendo", () => {
    const { result } = renderHook(() => useDemoSequence(true));
    expect(result.current).toBe("draft");
    act(() => vi.advanceTimersByTime(2500));
    expect(result.current).toBe("signing");
    act(() => vi.advanceTimersByTime(1800));
    expect(result.current).toBe("sending");
  });

  it("no avanza si no está corriendo y respeta el paso inicial", () => {
    const { result } = renderHook(() => useDemoSequence(false, "authorized"));
    act(() => vi.advanceTimersByTime(20_000));
    expect(result.current).toBe("authorized");
  });
});
