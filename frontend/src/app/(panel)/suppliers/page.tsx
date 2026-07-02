import Link from "next/link";
import { Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { SuppliersTable } from "./suppliers-table";

export const metadata = { title: "Proveedores" };

export default function SuppliersPage() {
  return (
    <div>
      <PageHeader
        title="Proveedores"
        description="Personas y empresas a las que compras"
        actions={
          <Button asChild>
            <Link href="/suppliers/new">
              <Plus className="size-4" />
              Nuevo proveedor
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <SuppliersTable />
      </div>
    </div>
  );
}
