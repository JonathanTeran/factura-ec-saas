import { describe, expect, it } from "vitest";
import { navGroups } from "@/components/panel/sidebar-nav";

describe("sidebar-nav", () => {
  it("apunta el Dashboard a /dashboard porque la raíz es la landing", () => {
    const dashboard = navGroups
      .flatMap((group) => group.items)
      .find((item) => item.label === "Dashboard");
    expect(dashboard?.href).toBe("/dashboard");
  });

  it("no tiene ningún destino en la raíz", () => {
    const roots = navGroups.flatMap((g) => g.items).filter((i) => i.href === "/");
    expect(roots).toHaveLength(0);
  });
});
