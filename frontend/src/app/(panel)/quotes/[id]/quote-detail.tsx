"use client";

import Link from "next/link";
import { ChevronLeft, Loader2, FileSpreadsheet } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { PageHeader } from "@/components/panel/page-header";
import { useQuote } from "@/lib/api/queries/quotes";
import { formatDate, formatMoney } from "@/lib/format";

const STATUS_CLASS: Record<string, string> = {
  accepted: "border-transparent bg-success/10 text-success",
  invoiced: "border-transparent bg-success/10 text-success",
  sent: "border-transparent bg-warning/10 text-warning",
  draft: "border-transparent bg-muted text-muted-foreground",
  rejected: "border-transparent bg-destructive/10 text-destructive",
  expired: "border-transparent bg-destructive/10 text-destructive",
};

export function QuoteDetail({ id }: { id: number }) {
  const { data: quote, isLoading, error } = useQuote(id);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (error || !quote) {
    return (
      <div className="p-6">
        <p className="text-sm text-destructive">
          No se pudo cargar la cotización.
        </p>
        <Button variant="outline" asChild className="mt-4">
          <Link href="/quotes">
            <ChevronLeft className="size-4" />
            Volver
          </Link>
        </Button>
      </div>
    );
  }

  const items = quote.items ?? [];

  return (
    <div className="pb-10">
      <PageHeader
        title={`Cotización ${quote.quote_number}`}
        description={quote.customer?.name ?? "—"}
        actions={
          <Button variant="outline" asChild>
            <Link href="/quotes">
              <ChevronLeft className="size-4" />
              Volver
            </Link>
          </Button>
        }
      />

      <div className="space-y-4 px-4 pt-4 lg:px-6">
        <div className="grid gap-4 sm:grid-cols-3">
          <InfoCard label="Estado">
            <Badge
              variant="outline"
              className={
                STATUS_CLASS[quote.status] ??
                "border-transparent bg-muted text-muted-foreground"
              }
            >
              {quote.status_label ?? quote.status}
            </Badge>
          </InfoCard>
          <InfoCard label="Fecha de emisión">
            {formatDate(quote.issue_date)}
          </InfoCard>
          <InfoCard label="Válida hasta">
            {quote.expiry_date ? formatDate(quote.expiry_date) : "—"}
          </InfoCard>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Ítems</CardTitle>
          </CardHeader>
          <CardContent className="p-0">
            {items.length === 0 ? (
              <div className="flex flex-col items-center gap-2 py-12 text-center text-sm text-muted-foreground">
                <FileSpreadsheet className="size-6" />
                Esta cotización no tiene ítems.
              </div>
            ) : (
              <div className="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow className="hover:bg-transparent">
                      <TableHead>Descripción</TableHead>
                      <TableHead className="text-right">Cant.</TableHead>
                      <TableHead className="text-right">P. unit.</TableHead>
                      <TableHead className="text-right">Subtotal</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {items.map((it) => (
                      <TableRow key={it.id}>
                        <TableCell className="font-medium">
                          {it.description}
                        </TableCell>
                        <TableCell className="text-right tabular-nums">
                          {it.quantity}
                        </TableCell>
                        <TableCell className="text-right tabular-nums">
                          {formatMoney(it.unit_price)}
                        </TableCell>
                        <TableCell className="text-right tabular-nums">
                          {formatMoney(it.subtotal)}
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            )}
          </CardContent>
        </Card>

        <div className="flex justify-end">
          <Card className="w-full max-w-xs">
            <CardContent className="space-y-2 p-5 text-sm">
              <Row label="Subtotal" value={formatMoney(quote.subtotal)} />
              <Row
                label="Descuento"
                value={`- ${formatMoney(quote.total_discount)}`}
              />
              <Row label="IVA" value={formatMoney(quote.total_tax)} />
              <div className="my-1 border-t border-border" />
              <Row
                label="Total"
                value={formatMoney(quote.total)}
                strong
              />
            </CardContent>
          </Card>
        </div>

        {quote.notes && (
          <Card>
            <CardHeader>
              <CardTitle>Notas</CardTitle>
            </CardHeader>
            <CardContent className="text-sm text-muted-foreground">
              {quote.notes}
            </CardContent>
          </Card>
        )}
      </div>
    </div>
  );
}

function InfoCard({
  label,
  children,
}: {
  label: string;
  children: React.ReactNode;
}) {
  return (
    <Card>
      <CardContent className="p-5">
        <p className="text-sm text-muted-foreground">{label}</p>
        <div className="mt-1.5 text-base font-medium">{children}</div>
      </CardContent>
    </Card>
  );
}

function Row({
  label,
  value,
  strong,
}: {
  label: string;
  value: string;
  strong?: boolean;
}) {
  return (
    <div className="flex items-center justify-between">
      <span className={strong ? "font-semibold" : "text-muted-foreground"}>
        {label}
      </span>
      <span className={`tabular-nums ${strong ? "text-base font-semibold" : ""}`}>
        {value}
      </span>
    </div>
  );
}
