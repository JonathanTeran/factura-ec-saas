import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { formatMoney } from "@/lib/format";
import type { Plan } from "@/lib/api/types";

const apiMock = vi.hoisted(() => ({
  get: vi.fn(),
  post: vi.fn(),
  patch: vi.fn(),
  delete: vi.fn(),
  put: vi.fn(),
}));
const redirectMock = vi.hoisted(() => vi.fn());

vi.mock("@/lib/api/client", async (importOriginal) => {
  const mod = await importOriginal<typeof import("@/lib/api/client")>();
  return { ...mod, api: apiMock };
});
vi.mock("@/lib/navigation", () => ({ redirectTo: redirectMock }));
vi.mock("sonner", () => ({ toast: { success: vi.fn(), error: vi.fn(), info: vi.fn() } }));

import { SubscribeDialog } from "@/app/(panel)/settings/subscription/subscribe-dialog";

const PLAN: Plan = {
  id: 7,
  name: "Negocio",
  price: 14.99,
  priceMonthly: 14.99,
  priceYearly: 149.99,
  currency: "USD",
  interval: "mes",
  features: [],
};

function mockApi(paypalEnabled: boolean) {
  apiMock.get.mockImplementation((path: string) => {
    if (path === "subscription/checkout-options") {
      return Promise.resolve({
        success: true,
        data: { bank_transfer: { enabled: true }, paypal: { enabled: paypalEnabled, sandbox: true }, tax_rate: 15 },
      });
    }
    if (path === "profile") {
      return Promise.resolve({
        success: true,
        data: { user: { id: 1, name: "Jonathan", email: "dueno@example.com", role: "tenant_owner", is_active: true, tenant_id: 3, tenant: { id: 3, name: "Mi Negocio S.A.", slug: "mi-negocio", email: null, status: "active", trial_ends_at: null } } },
      });
    }
    if (path === "subscription/bank-accounts") {
      return Promise.resolve({ success: true, data: { bank_accounts: [] } });
    }
    return Promise.reject(new Error(`GET inesperado: ${path}`));
  });
}

function renderDialog() {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } });
  return render(
    <QueryClientProvider client={client}>
      <SubscribeDialog plan={PLAN} onOpenChange={() => {}} />
    </QueryClientProvider>,
  );
}

describe("SubscribeDialog", () => {
  beforeEach(() => {
    apiMock.get.mockReset();
    apiMock.post.mockReset();
    redirectMock.mockReset();
  });

  it("con PayPal activo lo preselecciona, muestra el total con IVA y redirige a PayPal", async () => {
    mockApi(true);
    apiMock.post.mockResolvedValue({
      success: true,
      data: {
        payment_id: 55,
        order_id: "ORDER-1",
        approve_url: "https://www.sandbox.paypal.com/checkoutnow?token=ORDER-1",
        amounts: { subtotal: 14.99, discount: 0, tax: 2.25, total: 17.24, currency: "USD" },
      },
    });

    renderDialog();

    const paypalOption = await screen.findByRole("radio", { name: /PayPal/ });
    expect(paypalOption).toHaveAttribute("aria-checked", "true");
    expect(screen.getByText("Modo pruebas")).toBeInTheDocument();

    const summary = screen.getByLabelText("Resumen del pago");
    expect(summary).toHaveTextContent(formatMoney(2.25));
    expect(summary).toHaveTextContent(formatMoney(17.24));

    // Datos de facturación precargados desde el perfil.
    await waitFor(() => expect(screen.getByLabelText(/Nombre de facturación/)).toHaveValue("Mi Negocio S.A."));
    expect(screen.getByLabelText(/Correo de facturación/)).toHaveValue("dueno@example.com");

    fireEvent.click(screen.getByRole("button", { name: `Pagar ${formatMoney(17.24)} con PayPal` }));

    await waitFor(() => expect(redirectMock).toHaveBeenCalledWith("https://www.sandbox.paypal.com/checkoutnow?token=ORDER-1"));
    expect(apiMock.post).toHaveBeenCalledWith("subscription/paypal/orders", {
      plan_id: 7,
      billing_cycle: "monthly",
      billing_name: "Mi Negocio S.A.",
      billing_email: "dueno@example.com",
      billing_identification: undefined,
      coupon_code: undefined,
    });
    expect(screen.queryByText("Enviar comprobante")).toBeNull();
  });

  it("sin PayPal no ofrece el selector y mantiene el flujo por transferencia", async () => {
    mockApi(false);

    renderDialog();

    await waitFor(() => expect(apiMock.get).toHaveBeenCalledWith("subscription/checkout-options"));
    expect(await screen.findByRole("button", { name: /Enviar comprobante/ })).toBeInTheDocument();
    expect(screen.queryByRole("radiogroup", { name: "Método de pago" })).toBeNull();
    expect(screen.getByText("Cuenta para transferir")).toBeInTheDocument();
    expect(apiMock.post).not.toHaveBeenCalled();
  });
});
