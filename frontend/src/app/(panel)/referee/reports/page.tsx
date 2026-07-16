import { PageHeader } from "@/components/panel/page-header";
import { RefereeReportView } from "./referee-report-view";

export const metadata = { title: "Árbitro · Reportes" };

export default function RefereeReportsPage() {
  return (
    <div>
      <PageHeader
        title="Reportes"
        description="Cuánto facturaste, cuánto te falta cobrar, por campeonato y por mes"
      />
      <div className="p-4 lg:p-6">
        <RefereeReportView />
      </div>
    </div>
  );
}
