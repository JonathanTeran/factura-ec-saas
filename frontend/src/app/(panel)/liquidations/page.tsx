import Link from "next/link";
import { Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { DocumentsTable } from "../documents/documents-table";

export const metadata = { title: "Liquidaciones de compra" };

export default function LiquidationsPage() {
  return (
    <div>
      <PageHeader
        title="Liquidaciones de compra"
        description="Compras a proveedores que no pueden facturar"
        actions={
          <Button asChild>
            <Link href="/documents/new?type=03">
              <Plus className="size-4" />
              Nueva liquidación
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <DocumentsTable documentType="03" />
      </div>
    </div>
  );
}
