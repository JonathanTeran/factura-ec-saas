import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { RecurringForm } from "../recurring-form";

export const metadata = { title: "Nueva factura recurrente" };

export default function NewRecurringInvoicePage() {
  return (
    <div>
      <PageHeader
        title="Nueva factura recurrente"
        description="Programa cobros periódicos: la factura se genera y se envía al SRI sola en cada fecha."
        actions={
          <Button variant="outline" asChild>
            <Link href="/recurring-invoices">
              <ChevronLeft className="size-4" />
              Volver
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <RecurringForm />
      </div>
    </div>
  );
}
