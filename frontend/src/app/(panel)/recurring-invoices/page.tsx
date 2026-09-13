import Link from "next/link";
import { Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { RecurringTable } from "./recurring-table";

export const metadata = { title: "Facturas recurrentes" };

export default function RecurringInvoicesPage() {
  return (
    <div>
      <PageHeader
        title="Facturas recurrentes"
        description="Cobros periódicos: la factura se genera y se envía al SRI sola en cada fecha."
        actions={
          <Button asChild>
            <Link href="/recurring-invoices/new">
              <Plus className="size-4" />
              Nueva recurrente
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <RecurringTable />
      </div>
    </div>
  );
}
