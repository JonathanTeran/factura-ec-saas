import Link from "next/link";
import { Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { JournalEntriesTable } from "./journal-entries-table";

export const metadata = { title: "Asientos contables" };

export default function JournalEntriesPage() {
  return (
    <div>
      <PageHeader
        title="Asientos contables"
        description="Movimientos contables de la empresa"
        actions={
          <Button asChild>
            <Link href="/accounting/journal-entries/new">
              <Plus className="size-4" />
              Nuevo asiento
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <JournalEntriesTable />
      </div>
    </div>
  );
}
