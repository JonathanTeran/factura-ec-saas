import { afterEach, describe, expect, it, vi } from "vitest";
import { getLandingData, isLandingData } from "@/lib/landing/data";
import { LANDING_FIXTURE } from "@/lib/landing/fixture";

const okResponse = (data: unknown) =>
  ({ ok: true, json: async () => ({ success: true, message: "Success", data }) }) as unknown as Response;

describe("getLandingData", () => {
  afterEach(() => {
    delete process.env.LANDING_DATA_SOURCE;
  });

  it("devuelve el fixture cuando LANDING_DATA_SOURCE=fixture", async () => {
    process.env.LANDING_DATA_SOURCE = "fixture";
    const fetcher = vi.fn();
    expect(await getLandingData(fetcher as unknown as typeof fetch)).toBe(LANDING_FIXTURE);
    expect(fetcher).not.toHaveBeenCalled();
  });

  it("consulta /api/v1/public/landing con revalidate 300", async () => {
    const fetcher = vi.fn(async () => okResponse(LANDING_FIXTURE));
    const data = await getLandingData(fetcher as unknown as typeof fetch);
    expect(data?.plans).toHaveLength(4);
    const [url, init] = fetcher.mock.calls[0] as unknown as [string, { next?: { revalidate?: number } }];
    expect(url).toMatch(/\/api\/v1\/public\/landing$/);
    expect(init.next?.revalidate).toBe(300);
  });

  it("devuelve null si la respuesta no es ok, no tiene la forma esperada o falla", async () => {
    expect(await getLandingData((async () => ({ ok: false })) as unknown as typeof fetch)).toBeNull();
    expect(await getLandingData((async () => okResponse({ plans: "no" })) as unknown as typeof fetch)).toBeNull();
    expect(
      await getLandingData((async () => {
        throw new Error("red");
      }) as unknown as typeof fetch),
    ).toBeNull();
  });
});

describe("isLandingData", () => {
  it("valida la forma mínima", () => {
    expect(isLandingData(LANDING_FIXTURE)).toBe(true);
    expect(isLandingData({ plans: [], pricing_content: { title: "x" } })).toBe(false);
    expect(isLandingData(null)).toBe(false);
  });
});

describe("withVisibleFeatures", () => {
  it("la API puede incluir punto de venta e inventario, pero la landing no los recibe", async () => {
    const fetcher = vi.fn(async () => okResponse(LANDING_FIXTURE));
    const data = await getLandingData(fetcher as unknown as typeof fetch);
    const negocio = data?.plans.find((p) => p.slug === "negocio");
    expect(negocio).toBeDefined();
    expect(negocio?.features_list).toContain("API REST");
    expect(negocio?.features_list).not.toContain("Punto de venta");
    expect(negocio?.features_list).not.toContain("Inventario");
    expect(negocio?.features_list).not.toContain("Impresora térmica");
  });
});
