import Link from "next/link";
import { Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { PurchasesTable } from "./purchases-table";

export const metadata = { title: "Compras" };

export default function PurchasesPage() {
  return (
    <div>
      <PageHeader
        title="Compras"
        description="Facturas y comprobantes de proveedores"
        actions={
          <Button asChild>
            <Link href="/purchases/new">
              <Plus className="size-4" />
              Nueva compra
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <PurchasesTable />
      </div>
    </div>
  );
}
