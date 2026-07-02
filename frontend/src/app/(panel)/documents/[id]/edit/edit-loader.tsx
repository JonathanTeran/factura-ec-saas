"use client";

import { useRouter } from "next/navigation";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";
import { useEffect, useRef } from "react";
import { useDocument } from "@/lib/api/queries/documents";
import { NewInvoiceForm } from "../../new/new-invoice-form";

export function EditDocumentLoader({ id }: { id: number }) {
  const router = useRouter();
  const { data, isLoading, error } = useDocument(id);
  const redirected = useRef(false);

  useEffect(() => {
    if (data?.data?.document && data.data.document.status !== "draft") {
      if (!redirected.current) {
        redirected.current = true;
        toast.error("Solo se pueden editar documentos en borrador.");
        router.replace(`/documents/${id}`);
      }
    }
  }, [data, id, router]);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-6 text-sm text-destructive">
        Error cargando documento: {(error as Error).message}
      </div>
    );
  }

  const doc = data?.data?.document;
  if (!doc || doc.status !== "draft") return null;

  const docType = (doc.document_type ?? "01") as "01" | "04" | "05";

  return (
    <NewInvoiceForm
      key={doc.id}
      documentType={docType}
      existingDocument={doc}
    />
  );
}
