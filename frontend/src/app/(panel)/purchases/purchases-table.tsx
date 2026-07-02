"use client";

import { useState } from "react";
import Link from "next/link";
import { Search, Loader2 } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { usePurchases, useDeletePurchase } from "@/lib/api/queries/purchases";
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { TablePagination } from "@/components/panel/table-pagination";
import { DeleteConfirmButton } from "@/components/forms/delete-confirm-button";
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

export function PurchasesTable() {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(20);
  const [search, setSearch] = useState("");
  const debouncedSearch = useDebouncedValue(search);

  const { data, isLoading, isFetching, error } = usePurchases({
    page,
    per_page: perPage,
    search: debouncedSearch || undefined,
  });
  const del = useDeletePurchase();

  const items = data?.data ?? [];
  const meta = data?.meta;

  return (
    <Card>
      <CardContent className="p-4 space-y-4">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
          <Input
            value={search}
            onChange={(e) => {
              setSearch(e.target.value);
              setPage(1);
            }}
            placeholder="Buscar por número, proveedor, autorización..."
            className="pl-9"
          />
        </div>

        {error ? (
          <div className="text-sm text-destructive py-6 text-center">
            Error: {(error as Error).message}
          </div>
        ) : (
          <div className="relative">
            {isFetching && (
              <div className="absolute right-2 top-2 z-10">
                <Loader2 className="size-4 animate-spin text-muted-foreground" />
              </div>
            )}
            {isLoading ? (
              <div className="flex justify-center py-12">
                <Loader2 className="size-5 animate-spin text-muted-foreground" />
              </div>
            ) : items.length === 0 ? (
              <div className="py-12 text-center text-sm text-muted-foreground">
                Sin compras registradas.
              </div>
            ) : (
              <>
                {/* Tabla (escritorio) */}
                <div className="hidden md:block">
                  <Table>
                    <TableHeader>
                      <TableRow className="hover:bg-transparent">
                        <TableHead>Fecha</TableHead>
                        <TableHead>Tipo</TableHead>
                        <TableHead>Número</TableHead>
                        <TableHead>Proveedor</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead className="text-right">Total</TableHead>
                        <TableHead className="w-[60px]"></TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {items.map((p) => (
                        <TableRow key={p.id}>
                          <TableCell>
                            <Link
                              href={`/purchases/${p.id}`}
                              className="block py-1"
                            >
                              {formatDate(p.issue_date)}
                            </Link>
                          </TableCell>
                          <TableCell className="text-xs">
                            {documentTypeLabel(p.document_type)}
                          </TableCell>
                          <TableCell className="font-mono text-xs">
                            <Link
                              href={`/purchases/${p.id}`}
                              className="block py-1"
                            >
                              {p.supplier_document_number}
                            </Link>
                          </TableCell>
                          <TableCell>
                            {p.supplier?.business_name ??
                              `Proveedor #${p.supplier_id}`}
                          </TableCell>
                          <TableCell>
                            <Badge variant="secondary">
                              {purchaseStatusLabel(p.status)}
                            </Badge>
                          </TableCell>
                          <TableCell className="text-right font-medium">
                            {formatMoney(p.total)}
                          </TableCell>
                          <TableCell>
                            <DeleteConfirmButton
                              onConfirm={() => del.mutateAsync(p.id)}
                              isPending={del.isPending}
                              title="¿Eliminar compra?"
                              description="Si tiene retención asociada, la operación puede fallar."
                              successMessage="Compra eliminada"
                              iconOnly
                            />
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>

                {/* Tarjetas (móvil) */}
                <div className="space-y-2.5 md:hidden">
                  {items.map((p) => (
                    <div
                      key={p.id}
                      className="rounded-xl border border-border bg-card p-3.5"
                    >
                      <div className="flex items-start justify-between gap-3">
                        <Link
                          href={`/purchases/${p.id}`}
                          className="min-w-0 flex-1"
                        >
                          <p className="truncate font-medium">
                            {p.supplier?.business_name ??
                              `Proveedor #${p.supplier_id}`}
                          </p>
                          <p className="mt-0.5 font-mono text-xs text-muted-foreground">
                            {p.supplier_document_number}
                          </p>
                        </Link>
                        <Badge variant="secondary">
                          {purchaseStatusLabel(p.status)}
                        </Badge>
                      </div>
                      <div className="mt-3 flex items-center justify-between text-sm">
                        <span className="text-muted-foreground">
                          {documentTypeLabel(p.document_type)} ·{" "}
                          {formatDate(p.issue_date)}
                        </span>
                        <span className="font-semibold tabular-nums">
                          {formatMoney(p.total)}
                        </span>
                      </div>
                    </div>
                  ))}
                </div>
              </>
            )}
          </div>
        )}

        <TablePagination
          meta={meta}
          page={page}
          onPageChange={setPage}
          perPage={perPage}
          onPerPageChange={setPerPage}
          isFetching={isFetching}
        />
      </CardContent>
    </Card>
  );
}
