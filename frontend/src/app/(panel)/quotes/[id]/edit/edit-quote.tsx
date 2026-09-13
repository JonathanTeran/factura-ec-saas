"use client";

import Link from "next/link";
import { ChevronLeft, Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { useQuote } from "@/lib/api/queries/quotes";
import { QuoteForm } from "../../quote-form";

export function EditQuote({ id }: { id: number }) {
  const { data: quote, isLoading, error } = useQuote(id);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (error || !quote) {
    return (
      <div className="p-6">
        <p className="text-sm text-destructive">No se pudo cargar la cotización.</p>
        <Button variant="outline" asChild className="mt-4">
          <Link href="/quotes">
            <ChevronLeft className="size-4" />
            Volver
          </Link>
        </Button>
      </div>
    );
  }

  if (!quote.can_edit) {
    return (
      <div className="p-6">
        <p className="text-sm text-muted-foreground">
          Esta cotización ya fue {quote.status_label?.toLowerCase() ?? "procesada"} y no se puede editar.
        </p>
        <Button variant="outline" asChild className="mt-4">
          <Link href={`/quotes/${quote.id}`}>
            <ChevronLeft className="size-4" />
            Ver cotización
          </Link>
        </Button>
      </div>
    );
  }

  return (
    <div>
      <PageHeader
        title={`Editar ${quote.quote_number}`}
        description="Ajusta los ítems o las condiciones y vuelve a enviarla."
        actions={
          <Button variant="outline" asChild>
            <Link href={`/quotes/${quote.id}`}>
              <ChevronLeft className="size-4" />
              Volver
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <QuoteForm quote={quote} />
      </div>
    </div>
  );
}
