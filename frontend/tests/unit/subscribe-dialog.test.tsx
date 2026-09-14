import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
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

import { toast } from "sonner";
import { SubscribeDialog } from "@/app/(panel)/settings/subscription/subscribe-dialog";

const ONE_BANK = [
  { id: 1, bank_name: "Banco Pichincha", account_type: "Ahorros", account_number: "2200123456", holder_name: "AmePhia Systems", holder_identification: "1790012345001" },
];
const TWO_BANKS = [
  ...ONE_BANK,
  { id: 2, bank_name: "Banco Guayaquil", account_type: "Corriente", account_number: "3300654321", holder_name: "AmePhia Systems" },
];

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

// Dos RUCs: el escenario real de una cuenta con varias empresas. El id 9
// coincide con current_company_id del perfil abajo, así que debe preferirse
// sobre el 8 aunque venga después en la lista.
const COMPANIES = [
  { id: 8, ruc: "0999999999001", legal_name: "Otra Empresa S.A.", sri_environment: "2" },
  { id: 9, ruc: "1790012345001", legal_name: "Mi Negocio S.A.", sri_environment: "2" },
];

function mockApi(
  paypalEnabled: boolean,
  companies: typeof COMPANIES | [] = COMPANIES,
  bankAccounts: typeof TWO_BANKS | [] = [],
) {
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
        data: {
          user: {
            id: 1,
            name: "Jonathan",
            email: "dueno@example.com",
            role: "tenant_owner",
            is_active: true,
            tenant_id: 3,
            current_company_id: 9,
            tenant: { id: 3, name: "Mi Negocio S.A.", slug: "mi-negocio", email: null, status: "active", trial_ends_at: null },
          },
        },
      });
    }
    if (path === "companies") {
      return Promise.resolve({ success: true, data: { companies } });
    }
    if (path === "subscription/bank-accounts") {
      return Promise.resolve({ success: true, data: { bank_accounts: bankAccounts } });
    }
    return Promise.reject(new Error(`GET inesperado: ${path}`));
  });
}

function pdfFile() {
  return new File(["contenido"], "comprobante.pdf", { type: "application/pdf" });
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
  // La transferencia bancaria manda el comprobante con fetch crudo (FormData),
  // no con el cliente `api` mockeado arriba: subscription.ts lo hace así a
  // propósito para no serializar el archivo como JSON.
  const fetchMock = vi.fn();

  beforeEach(() => {
    apiMock.get.mockReset();
    apiMock.post.mockReset();
    redirectMock.mockReset();
    fetchMock.mockReset();
    vi.stubGlobal("fetch", fetchMock);
  });

  afterEach(() => vi.unstubAllGlobals());

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

    // Datos de facturación precargados desde el perfil y la empresa activa
    // (id 9, no la 8 aunque venga primero en la lista).
    await waitFor(() => expect(screen.getByLabelText(/Nombre de facturación/)).toHaveValue("Mi Negocio S.A."));
    expect(screen.getByLabelText(/Correo de facturación/)).toHaveValue("dueno@example.com");
    expect(screen.getByLabelText(/RUC \/ Cédula de facturación/)).toHaveValue("1790012345001");
    expect(screen.getByText(/Tomado del RUC\/cédula ya registrado/)).toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: `Pagar ${formatMoney(17.24)} con PayPal` }));

    await waitFor(() => expect(redirectMock).toHaveBeenCalledWith("https://www.sandbox.paypal.com/checkoutnow?token=ORDER-1"));
    expect(apiMock.post).toHaveBeenCalledWith("subscription/paypal/orders", {
      plan_id: 7,
      billing_cycle: "monthly",
      billing_name: "Mi Negocio S.A.",
      billing_email: "dueno@example.com",
      billing_identification: "1790012345001",
      coupon_code: undefined,
    });
    expect(screen.queryByText("Enviar comprobante")).toBeNull();
  });

  it("si el cliente edita el RUC/cédula, ese valor manda sobre el registrado", async () => {
    mockApi(true);
    apiMock.post.mockResolvedValue({
      success: true,
      data: {
        payment_id: 56,
        order_id: "ORDER-2",
        approve_url: "https://www.sandbox.paypal.com/checkoutnow?token=ORDER-2",
        amounts: { subtotal: 14.99, discount: 0, tax: 2.25, total: 17.24, currency: "USD" },
      },
    });

    renderDialog();

    const identification = await screen.findByLabelText(/RUC \/ Cédula de facturación/);
    await waitFor(() => expect(identification).toHaveValue("1790012345001"));
    fireEvent.change(identification, { target: { value: "0102030405" } });

    fireEvent.click(screen.getByRole("button", { name: `Pagar ${formatMoney(17.24)} con PayPal` }));

    await waitFor(() =>
      expect(apiMock.post).toHaveBeenCalledWith(
        "subscription/paypal/orders",
        expect.objectContaining({ billing_identification: "0102030405" }),
      ),
    );
  });

  it("sin empresas registradas deja el RUC/cédula en blanco, sin la pista", async () => {
    mockApi(true, []);

    renderDialog();

    await waitFor(() => expect(screen.getByLabelText(/Nombre de facturación/)).toHaveValue("Mi Negocio S.A."));
    expect(screen.getByLabelText(/RUC \/ Cédula de facturación/)).toHaveValue("");
    expect(screen.queryByText(/Tomado del RUC\/cédula ya registrado/)).toBeNull();
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

  it("el comprobante acepta imagen o PDF y, con una sola cuenta, la selecciona sola", async () => {
    mockApi(false, COMPANIES, ONE_BANK);
    fetchMock.mockResolvedValue({
      ok: true,
      json: async () => ({ success: true, data: {} }),
    });

    renderDialog();

    // El diálogo se porta fuera del contenedor de render, a document.body.
    // Antes de este fix el input filtraba solo imágenes: un comprobante en
    // PDF (lo usual en bancos ecuatorianos) no se podía ni seleccionar.
    const fileInput = await waitFor(() => {
      const input = document.querySelector<HTMLInputElement>('input[type="file"]');
      if (!input) throw new Error("input de archivo no encontrado todavía");
      return input;
    });
    expect(fileInput).toHaveAttribute("accept", "image/*,application/pdf");

    // Con una sola cuenta configurada no hace falta hacer clic en ella.
    await waitFor(() => expect(screen.getByText("Banco Pichincha · Ahorros").closest("button")).toHaveClass("border-primary"));

    fireEvent.change(screen.getByLabelText(/Número de referencia/), { target: { value: "REF-001" } });
    fireEvent.change(fileInput, { target: { files: [pdfFile()] } });
    expect(await screen.findByText("comprobante.pdf")).toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: "Enviar comprobante" }));

    await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(1));
    expect(toast.error).not.toHaveBeenCalled();

    const [url, init] = fetchMock.mock.calls[0];
    expect(url).toBe("/api/proxy/subscription/subscribe-bank-transfer");
    const body = init.body as FormData;
    expect(body.get("transfer_reference")).toBe("REF-001");
    expect((body.get("transfer_receipt") as File).name).toBe("comprobante.pdf");
    expect((body.get("transfer_receipt") as File).type).toBe("application/pdf");
  });

  it("avisa puntualmente qué falta en vez de un mensaje genérico", async () => {
    mockApi(false, COMPANIES, TWO_BANKS);

    renderDialog();
    await screen.findByText("Banco Pichincha · Ahorros");

    // Con dos cuentas ninguna se preselecciona: hay que elegir una.
    fireEvent.click(screen.getByRole("button", { name: "Enviar comprobante" }));
    expect(toast.error).toHaveBeenLastCalledWith("Elige la cuenta a la que hiciste la transferencia.");
    expect(fetchMock).not.toHaveBeenCalled();

    fireEvent.click(screen.getByText("Banco Pichincha · Ahorros"));
    fireEvent.click(screen.getByRole("button", { name: "Enviar comprobante" }));
    expect(toast.error).toHaveBeenLastCalledWith("Escribe el número de referencia de la transferencia.");

    fireEvent.change(screen.getByLabelText(/Número de referencia/), { target: { value: "REF-002" } });
    fireEvent.click(screen.getByRole("button", { name: "Enviar comprobante" }));
    expect(toast.error).toHaveBeenLastCalledWith("Adjunta el comprobante de la transferencia.");
    expect(fetchMock).not.toHaveBeenCalled();
  });
});
