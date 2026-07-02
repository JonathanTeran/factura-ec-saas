import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { GuideForm } from "./guide-form";

export const metadata = { title: "Nueva guía de remisión" };

export default function NewGuidePage() {
  return (
    <div>
      <PageHeader
        title="Nueva guía de remisión"
        description="Documenta el traslado de mercadería (comprobante SRI tipo 06)."
        actions={
          <Button variant="outline" asChild>
            <Link href="/guides">
              <ChevronLeft className="size-4" />
              Volver
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <GuideForm />
      </div>
    </div>
  );
}
