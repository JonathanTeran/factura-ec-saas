import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { TemplatesForm } from "./templates-form";

export const metadata = { title: "Plantillas de documento" };

export default function TemplatesPage() {
  return (
    <div className="pb-10">
      <PageHeader
        title="Plantillas de documento"
        description="Correo automático al cliente y pie del RIDE"
        actions={
          <Button variant="outline" asChild>
            <Link href="/settings">
              <ChevronLeft className="size-4" />
              Volver
            </Link>
          </Button>
        }
      />
      <div className="px-4 pt-4 lg:px-6">
        <TemplatesForm />
      </div>
    </div>
  );
}
