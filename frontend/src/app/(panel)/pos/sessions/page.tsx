import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { SessionsList } from "./sessions-list";

export const metadata = { title: "Sesiones POS" };

export default function PosSessionsPage() {
  return (
    <div>
      <PageHeader
        title="Sesiones POS"
        description="Historial de aperturas y cierres de caja"
        actions={
          <Button variant="outline" asChild>
            <Link href="/pos">
              <ChevronLeft className="size-4" />
              Volver al terminal
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <SessionsList />
      </div>
    </div>
  );
}
