import { RecurringDetail } from "./recurring-detail";

export const metadata = { title: "Factura recurrente" };

export default async function RecurringInvoicePage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <RecurringDetail id={Number(id)} />;
}
