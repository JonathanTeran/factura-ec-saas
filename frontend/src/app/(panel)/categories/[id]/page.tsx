import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { CategoryForm } from "../category-form";

export const metadata = { title: "Editar categoría" };

export default async function EditCategoryPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return (
    <div>
      <PageHeader
        title="Editar categoría"
        actions={
          <Button variant="outline" asChild>
            <Link href="/categories">
              <ChevronLeft className="size-4" />
              Volver
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <CategoryForm id={Number(id)} />
      </div>
    </div>
  );
}
