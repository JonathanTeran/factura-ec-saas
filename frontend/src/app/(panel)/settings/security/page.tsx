import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { SecurityForm } from "./security-form";

export const metadata = { title: "Seguridad" };

export default function SecurityPage() {
  return (
    <div className="pb-10">
      <PageHeader
        title="Seguridad"
        description="Contraseña y acceso a tu cuenta"
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
        <SecurityForm />
      </div>
    </div>
  );
}
