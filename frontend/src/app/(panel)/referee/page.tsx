import { PageHeader } from "@/components/panel/page-header";
import { Flag } from "lucide-react";

export const metadata = { title: "Árbitro · Partidos" };

export default function RefereePage() {
  return (
    <div>
      <PageHeader
        title="Partidos"
        description="Control de partidos pitados y pendientes por facturar"
      />
      <div className="p-4 lg:p-6">
        <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border bg-muted/30 px-6 py-16 text-center">
          <span className="grid size-12 place-items-center rounded-full bg-primary/10 text-primary">
            <Flag className="size-6" />
          </span>
          <h2 className="text-base font-medium text-foreground">
            Módulo de árbitros
          </h2>
          <p className="max-w-md text-sm text-muted-foreground">
            Aquí verás tus partidos pendientes por facturar y podrás emitir una
            factura por partido. En construcción — próximamente.
          </p>
        </div>
      </div>
    </div>
  );
}
