import Link from "next/link";
import { Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { DocumentsTable } from "../documents/documents-table";

export const metadata = { title: "Notas de crédito" };

export default function CreditNotesPage() {
  return (
    <div>
      <PageHeader
        title="Notas de crédito"
        description="Devoluciones y descuentos sobre facturas autorizadas"
        actions={
          <Button asChild>
            <Link href="/documents/new?type=04">
              <Plus className="size-4" />
              Nueva nota de crédito
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <DocumentsTable documentType="04" />
      </div>
    </div>
  );
}
