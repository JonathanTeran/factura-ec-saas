import { PageHeader } from "@/components/panel/page-header";
import { ReportsView } from "./reports-view";

export const metadata = { title: "Reportes" };

export default function ReportsPage() {
  return (
    <div>
      <PageHeader
        title="Reportes"
        description="Ventas, impuestos y rankings por período"
      />
      <div className="p-4 lg:p-6">
        <ReportsView />
      </div>
    </div>
  );
}
