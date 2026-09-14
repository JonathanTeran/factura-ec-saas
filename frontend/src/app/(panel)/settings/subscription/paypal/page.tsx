import { PageHeader } from "@/components/panel/page-header";
import { PayPalReturn } from "./paypal-return";

export const metadata = { title: "Pago con PayPal" };

/**
 * Regreso desde PayPal: `?token=ORDER_ID&PayerID=…`. La confirmación del cobro
 * y la activación del plan ocurren en el cliente (PayPalReturn).
 */
export default async function PayPalReturnPage({
  searchParams,
}: {
  searchParams: Promise<{ token?: string | string[] }>;
}) {
  const { token } = await searchParams;
  const orderId = typeof token === "string" && token.trim() !== "" ? token.trim() : null;

  return (
    <div>
      <PageHeader title="Pago con PayPal" description="Confirmamos el cobro y activamos tu plan" />
      <div className="p-4 lg:p-6">
        <PayPalReturn orderId={orderId} />
      </div>
    </div>
  );
}
