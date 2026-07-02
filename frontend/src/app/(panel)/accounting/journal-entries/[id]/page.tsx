import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { JournalEntryDetail } from "./journal-entry-detail";

export const metadata = { title: "Asiento" };

export default async function JournalEntryPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return (
    <div>
      <PageHeader
        title="Asiento contable"
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
        <JournalEntryDetail id={Number(id)} />
      </div>
    </div>
  );
}
