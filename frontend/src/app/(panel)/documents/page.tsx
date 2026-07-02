import Link from "next/link";
import { Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { DocumentsTable } from "./documents-table";

export const metadata = { title: "Facturas" };

export default function DocumentsPage() {
  return (
    <div>
      <PageHeader
        title="Facturas"
        description="Comprobantes de venta emitidos al SRI"
        actions={
          <Button asChild>
            <Link href="/documents/new">
              <Plus className="size-4" />
              Nueva factura
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <DocumentsTable documentType="01" />
      </div>
    </div>
  );
}
