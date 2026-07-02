import Link from "next/link";
import { Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { CategoriesTable } from "./categories-table";

export const metadata = { title: "Categorías" };

export default function CategoriesPage() {
  return (
    <div>
      <PageHeader
        title="Categorías"
        description="Clasifica productos y servicios"
        actions={
          <Button asChild>
            <Link href="/categories/new">
              <Plus className="size-4" />
              Nueva categoría
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <CategoriesTable />
      </div>
    </div>
  );
}
