import Link from "next/link";
import { BookOpen, ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { ApiKeysManager } from "./api-keys-manager";

export const metadata = { title: "API e integraciones" };

export default function ApiSettingsPage() {
  return (
    <div className="pb-10">
      <PageHeader
        title="API e integraciones"
        description="Llaves para conectar tu ERP, tienda en línea o sistema con Facturón"
        actions={
          <>
            <Button variant="outline" asChild>
              <Link href="/docs/api" target="_blank" rel="noopener noreferrer">
                <BookOpen className="size-4" />
                Documentación
              </Link>
            </Button>
            <Button variant="outline" asChild>
              <Link href="/settings">
                <ChevronLeft className="size-4" />
                Volver
              </Link>
            </Button>
          </>
        }
      />
      <div className="px-4 pt-4 lg:px-6">
        <ApiKeysManager />
      </div>
    </div>
  );
}
