import { QuoteDetail } from "./quote-detail";

export const metadata = { title: "Cotización" };

export default async function QuotePage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <QuoteDetail id={Number(id)} />;
}
