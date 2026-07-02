import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { BudgetForm } from "./budget-form";

export const metadata = { title: "Nuevo presupuesto" };

export default function NewBudgetPage() {
  return (
    <div>
      <PageHeader
        title="Nuevo presupuesto"
        description="Planificación financiera por año y cuenta contable"
        actions={
          <Button variant="outline" asChild>
            <Link href="/accounting/budgets">
              <ChevronLeft className="size-4" />
              Volver
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <BudgetForm />
      </div>
    </div>
  );
}
