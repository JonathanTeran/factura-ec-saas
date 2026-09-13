import { EditQuote } from "./edit-quote";

export const metadata = { title: "Editar cotización" };

export default async function EditQuotePage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <EditQuote id={Number(id)} />;
}
