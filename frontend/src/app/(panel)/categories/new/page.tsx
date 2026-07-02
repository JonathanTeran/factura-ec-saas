import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { CategoryForm } from "../category-form";

export const metadata = { title: "Nueva categoría" };

export default function NewCategoryPage() {
  return (
    <div>
      <PageHeader
        title="Nueva categoría"
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
        <CategoryForm />
      </div>
    </div>
  );
}
