import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { DocumentDetail } from "./document-detail";

export const metadata = { title: "Documento" };

export default async function DocumentPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const numericId = Number(id);

  return (
    <div>
      <PageHeader
        title="Detalle de documento"
        actions={
          <Button variant="outline" asChild>
            <Link href="/documents">
              <ChevronLeft className="size-4" />
              Volver
            </Link>
          </Button>
        }
      />
      <DocumentDetail id={numericId} />
    </div>
  );
}
