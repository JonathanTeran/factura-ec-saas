import Link from "next/link";
import { Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { CustomersTable } from "./customers-table";
import { ImportCustomersDialog } from "./import-customers-dialog";

export const metadata = { title: "Clientes" };

export default function CustomersPage() {
  return (
    <div>
      <PageHeader
        title="Clientes"
        description="Personas naturales y jurídicas a las que facturas"
        actions={
          <>
            <ImportCustomersDialog />
            <Button asChild>
              <Link href="/customers/new">
                <Plus className="size-4" />
                Nuevo cliente
              </Link>
            </Button>
          </>
        }
      />
      <div className="p-4 lg:p-6">
        <CustomersTable />
      </div>
    </div>
  );
}
