import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { ClientApiError } from "@/lib/api/client";

const apiMock = vi.hoisted(() => ({
  get: vi.fn(),
  post: vi.fn(),
  patch: vi.fn(),
  delete: vi.fn(),
  put: vi.fn(),
}));

vi.mock("@/lib/api/client", async (importOriginal) => {
  const mod = await importOriginal<typeof import("@/lib/api/client")>();
  return { ...mod, api: apiMock };
});

vi.mock("sonner", () => ({ toast: { success: vi.fn(), error: vi.fn() } }));

import { ApiKeysManager } from "@/app/(panel)/settings/api/api-keys-manager";

function renderManager() {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } });
  return render(
    <QueryClientProvider client={client}>
      <ApiKeysManager />
    </QueryClientProvider>,
  );
}

const LIST = {
  success: true,
  data: {
    api_keys: [
      {
        id: 1,
        name: "Tienda en línea",
        key_prefix: "fec_a1B2c3D4",
        scopes: ["documents:write", "customers:write"],
        rate_limit_per_minute: 60,
        effective_rate_limit: 60,
        last_used_at: null,
        last_used_ip: null,
        expires_at: null,
        is_active: true,
        is_expired: false,
        created_at: "2026-09-13T00:00:00.000Z",
      },
    ],
    scopes: { "documents:read": "Consultar documentos", "documents:write": "Emitir documentos" },
    limits: { max_keys: 10, plan_rate_limit: 60, plan_slug: "negocio" },
    base_url: "https://facturon.ec/api/v1/ext",
    docs_url: "https://facturon.ec/docs/api",
  },
};

describe("ApiKeysManager", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("muestra el upsell cuando el plan no incluye API (403 feature_not_available)", async () => {
    apiMock.get.mockRejectedValue(new ClientApiError(403, { success: false, error: "feature_not_available", message: "Acceso API no esta disponible" }));

    renderManager();

    expect(await screen.findByText(/disponible desde el plan Negocio/i)).toBeInTheDocument();
    expect(screen.getByRole("link", { name: "Ver planes" })).toHaveAttribute("href", "/settings/subscription");
    expect(screen.getByRole("link", { name: /Ver documentación/ })).toHaveAttribute("href", "/docs/api");
  });

  it("lista las llaves con prefijo, alcances y estado", async () => {
    apiMock.get.mockResolvedValue(LIST);

    renderManager();

    expect(await screen.findByText("Tienda en línea")).toBeInTheDocument();
    expect(screen.getByText("fec_a1B2c3D4…")).toBeInTheDocument();
    expect(screen.getByText("documents:write")).toBeInTheDocument();
    expect(screen.getByText("Activa")).toBeInTheDocument();
    expect(screen.getByText(/1 de 10 llaves activas/)).toBeInTheDocument();
  });

  it("al crear una llave muestra el valor en claro una sola vez", async () => {
    apiMock.get.mockResolvedValue(LIST);
    apiMock.post.mockResolvedValue({
      success: true,
      data: { api_key: { ...LIST.data.api_keys[0], id: 2, name: "ERP" }, plain_key: "fec_" + "x".repeat(40) },
    });

    renderManager();
    await screen.findByText("Tienda en línea");

    fireEvent.click(screen.getByRole("button", { name: /Nueva llave/ }));
    const nameInput = await screen.findByLabelText(/Nombre/);
    fireEvent.change(nameInput, { target: { value: "ERP" } });
    fireEvent.click(screen.getByRole("button", { name: "Crear llave" }));

    await waitFor(() => expect(apiMock.post).toHaveBeenCalledWith("api-keys", expect.objectContaining({ name: "ERP" })));
    expect(await screen.findByTestId("plain-key")).toHaveTextContent("fec_" + "x".repeat(40));
    expect(screen.getByText(/No volverá a mostrarse/)).toBeInTheDocument();
  });
});
