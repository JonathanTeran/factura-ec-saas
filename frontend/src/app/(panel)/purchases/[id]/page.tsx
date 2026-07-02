import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { PurchaseDetail } from "./purchase-detail";

export const metadata = { title: "Detalle compra" };

export default async function PurchaseDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return (
    <div>
      <PageHeader
        title="Detalle de compra"
        actions={
          <Button variant="outline" asChild>
            <Link href="/purchases">
              <ChevronLeft className="size-4" />
              Volver
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <PurchaseDetail id={Number(id)} />
      </div>
    </div>
  );
}
