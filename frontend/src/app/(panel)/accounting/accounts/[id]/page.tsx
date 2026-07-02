import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { AccountForm } from "../account-form";

export const metadata = { title: "Editar cuenta" };

export default async function EditAccountPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return (
    <div>
      <PageHeader
        title="Editar cuenta"
        actions={
          <Button variant="outline" asChild>
            <Link href="/accounting/accounts">
              <ChevronLeft className="size-4" />
              Volver
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <AccountForm id={Number(id)} />
      </div>
    </div>
  );
}
