import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { StoreBadges } from "@/components/marketing/store-badges";

describe("StoreBadges", () => {
  it("enlaza a las tiendas cuando hay URL", () => {
    render(<StoreBadges playUrl="https://play.google.com/store/apps/details?id=x" appStoreUrl="https://apps.apple.com/app/id1" />);
    const links = screen.getAllByRole("link");
    expect(links).toHaveLength(2);
    expect(links[0]).toHaveAttribute("href", expect.stringContaining("play.google.com"));
    expect(links[1]).toHaveAttribute("href", expect.stringContaining("apps.apple.com"));
    expect(screen.queryByText("Próximamente")).toBeNull();
  });

  it("muestra Próximamente sin enlaces cuando faltan las URL", () => {
    render(<StoreBadges />);
    expect(screen.queryAllByRole("link")).toHaveLength(0);
    expect(screen.getAllByText("Próximamente")).toHaveLength(2);
    expect(screen.getByText("Google Play")).toBeInTheDocument();
    expect(screen.getByText("App Store")).toBeInTheDocument();
  });
});
