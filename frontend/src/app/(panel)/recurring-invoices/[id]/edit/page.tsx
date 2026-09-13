import { EditRecurring } from "./edit-recurring";

export const metadata = { title: "Editar factura recurrente" };

export default async function EditRecurringInvoicePage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <EditRecurring id={Number(id)} />;
}
