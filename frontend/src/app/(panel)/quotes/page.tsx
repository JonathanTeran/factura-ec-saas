import Link from "next/link";
import { Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { QuotesTable } from "./quotes-table";

export const metadata = { title: "Cotizaciones" };

export default function QuotesPage() {
  return (
    <div>
      <PageHeader
        title="Cotizaciones"
        description="Propuestas comerciales antes de facturar"
        actions={
          <Button asChild>
            <Link href="/quotes/new">
              <Plus className="size-4" />
              Nueva cotización
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <QuotesTable />
      </div>
    </div>
  );
}
