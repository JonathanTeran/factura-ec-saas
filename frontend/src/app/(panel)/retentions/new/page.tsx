import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { RetentionForm } from "./retention-form";

export const metadata = { title: "Nueva retención" };

export default function NewRetentionPage() {
  return (
    <div>
      <PageHeader
        title="Nueva retención"
        description="Comprobante de retención (tipo 07). Crea un borrador y luego fírmalo y envíalo al SRI."
        actions={
          <Button variant="outline" asChild>
            <Link href="/retentions">
              <ChevronLeft className="size-4" />
              Volver
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <RetentionForm />
      </div>
    </div>
  );
}
