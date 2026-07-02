import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { ProductForm } from "../product-form";

export const metadata = { title: "Nuevo producto" };

export default function NewProductPage() {
  return (
    <div>
      <PageHeader
        title="Nuevo producto"
        actions={
          <Button variant="outline" asChild>
            <Link href="/products">
              <ChevronLeft className="size-4" />
              Volver
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <ProductForm />
      </div>
    </div>
  );
}
