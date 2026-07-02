import { PageHeader } from "@/components/panel/page-header";
import { PosTerminal } from "./pos-terminal";

export const metadata = { title: "POS" };

export default function PosPage() {
  return (
    <div className="flex flex-col h-[calc(100vh-4rem)]">
      <PageHeader title="Punto de venta" description="Terminal de cobro rápido" />
      <div className="flex-1 overflow-hidden">
        <PosTerminal />
      </div>
    </div>
  );
}
