import { PageHeader } from "@/components/panel/page-header";
import { AccountingReportsView } from "./accounting-reports-view";

export const metadata = { title: "Reportes contables" };

export default function AccountingReportsPage() {
  return (
    <div>
      <PageHeader
        title="Reportes contables"
        description="Balance, estado de resultados, mayor general"
      />
      <div className="p-4 lg:p-6">
        <AccountingReportsView />
      </div>
    </div>
  );
}
