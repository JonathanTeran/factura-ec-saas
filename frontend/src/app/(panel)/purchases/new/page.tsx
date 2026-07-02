import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { PurchaseForm } from "../purchase-form";

export const metadata = { title: "Nueva compra" };

export default function NewPurchasePage() {
  return (
    <div>
      <PageHeader
        title="Nueva compra"
        description="Registra un comprobante recibido de un proveedor"
        actions={
          <Button variant="outline" asChild>
            <Link href="/purchases">
              <ChevronLeft className="size-4" />
              Volver
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <PurchaseForm />
      </div>
    </div>
  );
}
