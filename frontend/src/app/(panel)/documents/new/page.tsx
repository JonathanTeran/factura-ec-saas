import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { NewInvoiceForm } from "./new-invoice-form";

export const metadata = { title: "Nuevo documento" };

const TITLES: Record<string, { title: string; description: string }> = {
  "01": {
    title: "Nueva factura",
    description: "Crea un borrador. Luego podrás firmarlo y enviarlo al SRI.",
  },
  "03": {
    title: "Nueva liquidación de compra",
    description: "Emite una liquidación a un proveedor que no puede facturar.",
  },
  "04": {
    title: "Nueva nota de crédito",
    description: "Modifica una factura autorizada (devolución, descuento).",
  },
  "05": {
    title: "Nueva nota de débito",
    description: "Cobra cargos adicionales sobre una factura autorizada.",
  },
};

export default async function NewInvoicePage({
  searchParams,
}: {
  searchParams: Promise<{ type?: string }>;
}) {
  const { type } = await searchParams;
  const docType: "01" | "03" | "04" | "05" =
    type === "03" || type === "04" || type === "05" ? type : "01";
  const meta = TITLES[docType] ?? TITLES["01"];

  return (
    <div>
      <PageHeader
        title={meta.title}
        description={meta.description}
        actions={
          <Button variant="outline" asChild>
            <Link href="/documents">
              <ChevronLeft className="size-4" />
              Volver
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <NewInvoiceForm documentType={docType} />
      </div>
    </div>
  );
}
