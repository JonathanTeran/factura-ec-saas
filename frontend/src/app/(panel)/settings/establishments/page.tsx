import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { EstablishmentsManager } from "./establishments-manager";

export const metadata = { title: "Establecimientos" };

export default function EstablishmentsPage() {
  return (
    <div>
      <PageHeader
        title="Establecimientos y puntos de emisión"
        description="Configura sucursales y series de facturación SRI"
        actions={
          <Button variant="outline" asChild>
            <Link href="/settings">
              <ChevronLeft className="size-4" />
              Configuración
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <EstablishmentsManager />
      </div>
    </div>
  );
}
