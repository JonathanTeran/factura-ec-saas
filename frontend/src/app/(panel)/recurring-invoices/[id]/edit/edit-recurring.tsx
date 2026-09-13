"use client";

import Link from "next/link";
import { ChevronLeft, Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { useRecurringInvoice } from "@/lib/api/queries/recurring-invoices";
import { RecurringForm } from "../../recurring-form";

export function EditRecurring({ id }: { id: number }) {
  const { data: recurring, isLoading, error } = useRecurringInvoice(id);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (error || !recurring) {
    return (
      <div className="p-6">
        <p className="text-sm text-destructive">No se pudo cargar la recurrente.</p>
        <Button variant="outline" asChild className="mt-4">
          <Link href="/recurring-invoices">
            <ChevronLeft className="size-4" />
            Volver
          </Link>
        </Button>
      </div>
    );
  }

  return (
    <div>
      <PageHeader
        title={`Editar ${recurring.name || recurring.customer?.name || "recurrente"}`}
        description="Los cambios aplican a las próximas emisiones; las facturas ya generadas no cambian."
        actions={
          <Button variant="outline" asChild>
            <Link href={`/recurring-invoices/${recurring.id}`}>
              <ChevronLeft className="size-4" />
              Volver
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <RecurringForm recurring={recurring} />
      </div>
    </div>
  );
}
