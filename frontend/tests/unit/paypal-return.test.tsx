import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { ClientApiError } from "@/lib/api/client";

const apiMock = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn(), patch: vi.fn(), delete: vi.fn(), put: vi.fn() }));
const refreshMock = vi.hoisted(() => vi.fn());

vi.mock("@/lib/api/client", async (importOriginal) => {
  const mod = await importOriginal<typeof import("@/lib/api/client")>();
  return { ...mod, api: apiMock };
});
vi.mock("next/navigation", () => ({
  useRouter: () => ({ push: vi.fn(), replace: vi.fn(), refresh: refreshMock }),
}));

import { PayPalReturn } from "@/app/(panel)/settings/subscription/paypal/paypal-return";

function renderReturn(orderId: string | null) {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false }, mutations: { retry: false } } });
  return render(
    <QueryClientProvider client={client}>
      <PayPalReturn orderId={orderId} />
    </QueryClientProvider>,
  );
}

describe("PayPalReturn", () => {
  beforeEach(() => {
    apiMock.post.mockReset();
    refreshMock.mockReset();
  });

  it("confirma el pago una sola vez y muestra el plan activo", async () => {
    apiMock.post.mockResolvedValue({
      success: true,
      message: "¡Pago recibido! Tu plan ya está activo.",
      data: {
        status: "completed",
        payment: {
          id: 1,
          amount: 14.99,
          total_amount: 17.24,
          status: "completed",
          created_at: "2026-09-14T12:00:00Z",
          subscription: { id: 9, status: "active", ends_at: "2026-10-14T12:00:00Z", plan: { id: 7, name: "Negocio" } },
        },
      },
    });

    renderReturn("ORDER-1");

    expect(await screen.findByText("¡Pago recibido!")).toBeInTheDocument();
    expect(screen.getByText(/Tu plan Negocio está activo hasta el/)).toBeInTheDocument();
    expect(apiMock.post).toHaveBeenCalledTimes(1);
    expect(apiMock.post).toHaveBeenCalledWith("subscription/paypal/orders/ORDER-1/capture");
    expect(refreshMock).toHaveBeenCalled();
  });

  it("si PayPal rechaza el cobro permite reintentar o elegir otro método", async () => {
    apiMock.post.mockRejectedValue(
      new ClientApiError(422, { success: false, message: "PayPal rechazó el medio de pago. Vuelve a intentarlo con otra tarjeta o con tu saldo PayPal." }),
    );

    renderReturn("ORDER-2");

    expect(await screen.findByText("No se completó el pago")).toBeInTheDocument();
    expect(screen.getByText(/rechazó el medio de pago/)).toBeInTheDocument();
    expect(screen.getByRole("link", { name: "Elegir otro método" })).toHaveAttribute("href", "/settings/subscription");

    fireEvent.click(screen.getByRole("button", { name: "Volver a intentar" }));
    await waitFor(() => expect(apiMock.post).toHaveBeenCalledTimes(2));
  });

  it("sin token de PayPal no intenta cobrar", () => {
    renderReturn(null);

    expect(screen.getByText("No encontramos el pago")).toBeInTheDocument();
    expect(apiMock.post).not.toHaveBeenCalled();
  });
});
