import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { EditDocumentLoader } from "./edit-loader";

export const metadata = { title: "Editar borrador" };

export default async function EditDocumentPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return (
    <div>
      <PageHeader
        title="Editar borrador"
        description="Solo se pueden editar documentos antes de enviarlos al SRI."
        actions={
          <Button variant="outline" asChild>
            <Link href={`/documents/${id}`}>
              <ChevronLeft className="size-4" />
              Volver al detalle
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <EditDocumentLoader id={Number(id)} />
      </div>
    </div>
  );
}
