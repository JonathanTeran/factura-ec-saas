import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { QuoteForm } from "./quote-form";

export const metadata = { title: "Nueva cotización" };

export default function NewQuotePage() {
  return (
    <div>
      <PageHeader
        title="Nueva cotización"
        description="Genera una propuesta comercial. Luego puedes convertirla a factura."
        actions={
          <Button variant="outline" asChild>
            <Link href="/quotes">
              <ChevronLeft className="size-4" />
              Volver
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <QuoteForm />
      </div>
    </div>
  );
}
