import Link from "next/link";
import { Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { AccountsTable } from "./accounts-table";

export const metadata = { title: "Plan de cuentas" };

export default function AccountsPage() {
  return (
    <div>
      <PageHeader
        title="Plan de cuentas"
        description="Estructura contable jerárquica"
        actions={
          <Button asChild>
            <Link href="/accounting/accounts/new">
              <Plus className="size-4" />
              Nueva cuenta
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <AccountsTable />
      </div>
    </div>
  );
}
