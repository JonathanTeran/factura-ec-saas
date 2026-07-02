import Link from "next/link";
import { Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { DocumentsTable } from "../documents/documents-table";

export const metadata = { title: "Notas de débito" };

export default function DebitNotesPage() {
  return (
    <div>
      <PageHeader
        title="Notas de débito"
        description="Cargos adicionales sobre facturas autorizadas"
        actions={
          <Button asChild>
            <Link href="/documents/new?type=05">
              <Plus className="size-4" />
              Nueva nota de débito
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <DocumentsTable documentType="05" />
      </div>
    </div>
  );
}
