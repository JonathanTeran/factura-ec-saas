import { PageHeader } from "@/components/panel/page-header";
import { InventoryDashboard } from "./inventory-dashboard";

export const metadata = { title: "Inventario" };

export default function InventoryPage() {
  return (
    <div>
      <PageHeader
        title="Inventario"
        description="Stock, alertas y movimientos"
      />
      <div className="p-4 lg:p-6">
        <InventoryDashboard />
      </div>
    </div>
  );
}
