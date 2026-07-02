"use client";

import { Loader2 } from "lucide-react";
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
import { usePurchase } from "@/lib/api/queries/purchases";
import { formatDate, formatMoney } from "@/lib/format";
import { documentTypeLabel } from "@/lib/status";

const PURCHASE_STATUS_LABELS: Record<string, string> = {
  registered: "Registrada",
  withholding_issued: "Retención emitida",
  paid: "Pagada",
  voided: "Anulada",
};

function purchaseStatusLabel(status: string | null | undefined): string {
  if (!status) return "—";
  return PURCHASE_STATUS_LABELS[status] ?? status;
}

export function PurchaseDetail({ id }: { id: number }) {
  const { data, isLoading, error } = usePurchase(id);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="text-sm text-destructive">
        Error: {(error as Error).message}
      </div>
    );
  }

  const p = data;
  if (!p) return null;

  return (
    <div className="space-y-6">
      <Card>
        <CardHeader className="flex flex-row items-start justify-between">
          <div>
            <CardTitle className="text-xl">
              Compra · <span className="font-mono">{p.supplier_document_number}</span>
            </CardTitle>
            <p className="text-sm text-muted-foreground mt-1">
              {formatDate(p.issue_date)} · {documentTypeLabel(p.document_type)}
            </p>
          </div>
          <Badge variant="secondary">{purchaseStatusLabel(p.status)}</Badge>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2">
          <div>
            <h3 className="text-xs font-semibold uppercase text-muted-foreground mb-1">
              Proveedor
            </h3>
            <p className="font-medium">
              {p.supplier?.business_name ?? `Proveedor #${p.supplier_id}`}
            </p>
            {p.supplier?.identification && (
              <p className="text-sm text-muted-foreground">
                {p.supplier.identification}
              </p>
            )}
          </div>
          <div>
            <h3 className="text-xs font-semibold uppercase text-muted-foreground mb-1">
              SRI
            </h3>
            {p.supplier_authorization ? (
              <p className="font-mono text-xs break-all">{p.supplier_authorization}</p>
            ) : (
              <p className="text-sm text-muted-foreground">Sin autorización</p>
            )}
            {p.authorization_date && (
              <p className="text-xs text-muted-foreground mt-1">
                {formatDate(p.authorization_date)}
              </p>
            )}
          </div>
          {p.notes && (
            <div className="sm:col-span-2">
              <h3 className="text-xs font-semibold uppercase text-muted-foreground mb-1">
                Notas
              </h3>
              <p className="text-sm">{p.notes}</p>
            </div>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Detalle</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Código</TableHead>
                <TableHead>Descripción</TableHead>
                <TableHead className="text-right">Cant.</TableHead>
                <TableHead className="text-right">P. unit.</TableHead>
                <TableHead className="text-right">Desc.</TableHead>
                <TableHead className="text-right">Subtotal</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {(p.items ?? []).map((it) => (
                <TableRow key={it.id}>
                  <TableCell className="font-mono text-xs">
                    {it.main_code ?? "—"}
                  </TableCell>
                  <TableCell>{it.description}</TableCell>
                  <TableCell className="text-right">{it.quantity}</TableCell>
                  <TableCell className="text-right">
                    {formatMoney(it.unit_price)}
                  </TableCell>
                  <TableCell className="text-right">
                    {formatMoney(it.discount)}
                  </TableCell>
                  <TableCell className="text-right">
                    {formatMoney(it.subtotal)}
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>

          <div className="mt-4 ml-auto max-w-xs space-y-1 text-sm">
            {!!p.subtotal_0 && <Row label="Subtotal 0%" value={p.subtotal_0} />}
            {!!p.subtotal_5 && <Row label="Subtotal 5%" value={p.subtotal_5} />}
            {!!p.subtotal_12 && (
              <Row label="Subtotal 12%" value={p.subtotal_12} />
            )}
            {!!p.subtotal_15 && (
              <Row label="Subtotal 15%" value={p.subtotal_15} />
            )}
            <Row label="Descuento" value={p.total_discount} />
            <Row label="IVA" value={p.total_tax} />
            <div className="flex items-center justify-between border-t pt-2 mt-2 font-semibold">
              <span>Total</span>
              <span>{formatMoney(p.total)}</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

function Row({ label, value }: { label: string; value?: number }) {
  if (!value) return null;
  return (
    <div className="flex items-center justify-between text-muted-foreground">
      <span>{label}</span>
      <span>{formatMoney(value)}</span>
    </div>
  );
}
