import { PageHeader } from "@/components/panel/page-header";
import { RefereeView } from "./referee-view";

export const metadata = { title: "Árbitro · Partidos" };

export default function RefereePage() {
  return (
    <div>
      <PageHeader
        title="Partidos"
        description="Partidos pitados, pendientes por facturar y facturación a la FEF"
      />
      <div className="p-4 lg:p-6">
        <RefereeView />
      </div>
    </div>
  );
}
