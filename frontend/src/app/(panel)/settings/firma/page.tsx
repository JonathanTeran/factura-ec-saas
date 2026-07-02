import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { FirmaManager } from "./firma-manager";

export const metadata = { title: "Firma electrónica" };

export default function FirmaPage() {
  return (
    <div className="pb-10">
      <PageHeader
        title="Firma electrónica"
        description="Tu certificado .p12 para firmar comprobantes ante el SRI"
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
        <FirmaManager />
      </div>
    </div>
  );
}
