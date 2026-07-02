import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { SupplierForm } from "../supplier-form";

export const metadata = { title: "Nuevo proveedor" };

export default function NewSupplierPage() {
  return (
    <div>
      <PageHeader
        title="Nuevo proveedor"
        actions={
          <Button variant="outline" asChild>
            <Link href="/suppliers">
              <ChevronLeft className="size-4" />
              Volver
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <SupplierForm />
      </div>
    </div>
  );
}
