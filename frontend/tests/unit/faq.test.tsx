import { render } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { Faq } from "@/components/marketing/faq";
import { FAQS } from "@/content/landing";

describe("Faq", () => {
  it("renderiza 13 <details> nativos con la primera abierta", () => {
    const { container } = render(<Faq />);
    const details = container.querySelectorAll("details");
    expect(details).toHaveLength(14);
    expect(details[0].hasAttribute("open")).toBe(true);
    expect(details[1].hasAttribute("open")).toBe(false);
    expect(details[0].querySelector("summary")?.textContent).toContain(FAQS[0].q);
    expect(container.querySelector("section#faq")).not.toBeNull();
  });
});
