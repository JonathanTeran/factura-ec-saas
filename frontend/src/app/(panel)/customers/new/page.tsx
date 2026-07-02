import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { CustomerForm } from "../customer-form";

export const metadata = { title: "Nuevo cliente" };

export default function NewCustomerPage() {
  return (
    <div>
      <PageHeader
        title="Nuevo cliente"
        actions={
          <Button variant="outline" asChild>
            <Link href="/customers">
              <ChevronLeft className="size-4" />
              Volver
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <CustomerForm />
      </div>
    </div>
  );
}
