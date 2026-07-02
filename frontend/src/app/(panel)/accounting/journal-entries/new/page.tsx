import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { JournalEntryForm } from "./journal-entry-form";

export const metadata = { title: "Nuevo asiento" };

export default function NewJournalEntryPage() {
  return (
    <div>
      <PageHeader
        title="Nuevo asiento contable"
        description="El total de DEBE debe ser igual al total de HABER"
        actions={
          <Button variant="outline" asChild>
            <Link href="/accounting/journal-entries">
              <ChevronLeft className="size-4" />
              Volver
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <JournalEntryForm />
      </div>
    </div>
  );
}
