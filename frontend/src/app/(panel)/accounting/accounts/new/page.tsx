import Link from "next/link";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/panel/page-header";
import { AccountForm } from "../account-form";

export const metadata = { title: "Nueva cuenta" };

export default function NewAccountPage() {
  return (
    <div>
      <PageHeader
        title="Nueva cuenta"
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
        <AccountForm />
      </div>
    </div>
  );
}
