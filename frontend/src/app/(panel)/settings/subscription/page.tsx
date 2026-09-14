import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { SubscriptionView } from "./subscription-view";

export const metadata = { title: "Suscripción" };

export default async function SubscriptionPage({
  searchParams,
}: {
  searchParams: Promise<{ paypal?: string | string[]; token?: string | string[] }>;
}) {
  const { paypal, token } = await searchParams;
  // Regreso por el botón "Cancelar" de PayPal: ?paypal=cancelled&token=ORDER_ID
  const paypalCancelledOrder =
    paypal === "cancelled" ? (typeof token === "string" ? token : "") : null;

  return (
    <div>
      <PageHeader
        title="Suscripción"
        description="Plan, pagos y cuentas bancarias"
        actions={
          <Button variant="outline" asChild>
            <Link href="/settings">
              <ChevronLeft className="size-4" />
              Volver
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <SubscriptionView paypalCancelledOrder={paypalCancelledOrder} />
      </div>
    </div>
  );
}
