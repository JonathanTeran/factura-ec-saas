import { PageHeader } from "@/components/panel/page-header";
import { CompanySettingsForm } from "./company-settings-form";

export const metadata = { title: "Datos del emisor" };

export default function CompanySettingsPage() {
  return (
    <div className="pb-10">
      <PageHeader
        title="Datos del emisor"
        description="La información que el SRI usa para identificar tus comprobantes"
      />
      <div className="p-4 lg:p-6">
        <CompanySettingsForm />
      </div>
    </div>
  );
}
