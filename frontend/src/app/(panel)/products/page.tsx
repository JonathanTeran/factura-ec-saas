import Link from "next/link";
import { Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { ProductsTable } from "./products-table";

export const metadata = { title: "Productos" };

export default function ProductsPage() {
  return (
    <div>
      <PageHeader
        title="Productos"
        description="Catálogo de productos y servicios"
        actions={
          <Button asChild>
            <Link href="/products/new">
              <Plus className="size-4" />
              Nuevo producto
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <ProductsTable />
      </div>
    </div>
  );
}
