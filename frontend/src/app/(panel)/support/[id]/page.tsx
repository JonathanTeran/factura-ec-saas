import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { TicketDetail } from "./ticket-detail";

export const metadata = { title: "Ticket de soporte" };

export default async function TicketPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return (
    <div>
      <PageHeader
        title="Ticket de soporte"
        actions={
          <Button variant="outline" asChild>
            <Link href="/support">
              <ChevronLeft className="size-4" />
              Volver
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <TicketDetail id={Number(id)} />
      </div>
    </div>
  );
}
