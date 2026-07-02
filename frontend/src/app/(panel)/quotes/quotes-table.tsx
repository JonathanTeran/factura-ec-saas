"use client";

import { useState } from "react";
import Link from "next/link";
import { Loader2, Search } from "lucide-react";
import { toast } from "sonner";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  useDeleteQuote,
  useQuoteAction,
  useQuotes,
} from "@/lib/api/queries/quotes";
import { DeleteConfirmButton } from "@/components/forms/delete-confirm-button";
import { ClientApiError } from "@/lib/api/client";
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { TablePagination } from "@/components/panel/table-pagination";
import { formatDate, formatMoney } from "@/lib/format";

function statusVariant(s: string): "default" | "secondary" | "destructive" {
  if (s === "accepted" || s === "invoiced") return "default";
  if (s === "rejected" || s === "expired") return "destructive";
  return "secondary";
}

function errMessage(err: unknown): string {
  if (err instanceof ClientApiError) {
    const p = err.payload as { message?: string } | null;
    return p?.message ?? err.message;
  }
  return err instanceof Error ? err.message : "Error inesperado";
}

export function QuotesTable() {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(20);
  const [search, setSearch] = useState("");
  const debouncedSearch = useDebouncedValue(search);
  const { data, isLoading, isFetching, error } = useQuotes({
    page,
    per_page: perPage,
    search: debouncedSearch || undefined,
  });
  const del = useDeleteQuote();
  const action = useQuoteAction();

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
            placeholder="Buscar por número o cliente..."
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
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Número</TableHead>
                  <TableHead>Fecha</TableHead>
                  <TableHead>Cliente</TableHead>
                  <TableHead>Estado</TableHead>
                  <TableHead className="text-right">Total</TableHead>
                  <TableHead className="text-right">Acciones</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {isLoading ? (
                  <TableRow>
                    <TableCell colSpan={6} className="text-center py-12">
                      <Loader2 className="size-5 animate-spin mx-auto text-muted-foreground" />
                    </TableCell>
                  </TableRow>
                ) : items.length === 0 ? (
                  <TableRow>
                    <TableCell
                      colSpan={6}
                      className="text-center py-12 text-muted-foreground"
                    >
                      Sin cotizaciones.
                    </TableCell>
                  </TableRow>
                ) : (
                  items.map((q) => (
                    <TableRow key={q.id}>
                      <TableCell className="font-mono text-xs">
                        <Link href={`/quotes/${q.id}`} className="block py-1">
                          {q.quote_number}
                        </Link>
                      </TableCell>
                      <TableCell>{formatDate(q.issue_date)}</TableCell>
                      <TableCell>{q.customer?.name ?? "—"}</TableCell>
                      <TableCell>
                        <Badge variant={statusVariant(q.status)}>
                          {q.status_label ?? q.status}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right font-medium">
                        {formatMoney(q.total)}
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex justify-end gap-1">
                          {q.status === "draft" && (
                            <Button
                              size="sm"
                              variant="outline"
                              disabled={action.isPending}
                              onClick={() =>
                                action.mutate(
                                  { id: q.id, action: "send" },
                                  {
                                    onSuccess: () => toast.success("Marcada como enviada"),
                                    onError: (e) => toast.error(errMessage(e)),
                                  },
                                )
                              }
                            >
                              Enviar
                            </Button>
                          )}
                          {q.status === "sent" && (
                            <>
                              <Button
                                size="sm"
                                variant="outline"
                                disabled={action.isPending}
                                onClick={() =>
                                  action.mutate(
                                    { id: q.id, action: "accept" },
                                    {
                                      onSuccess: () =>
                                        toast.success("Aceptada"),
                                      onError: (e) =>
                                        toast.error(errMessage(e)),
                                    },
                                  )
                                }
                              >
                                Aceptar
                              </Button>
                              <Button
                                size="sm"
                                variant="ghost"
                                disabled={action.isPending}
                                onClick={() =>
                                  action.mutate(
                                    { id: q.id, action: "reject" },
                                    {
                                      onSuccess: () =>
                                        toast.success("Rechazada"),
                                      onError: (e) =>
                                        toast.error(errMessage(e)),
                                    },
                                  )
                                }
                              >
                                Rechazar
                              </Button>
                            </>
                          )}
                          {!q.converted_to_document_id && (
                            <DeleteConfirmButton
                              onConfirm={() => del.mutateAsync(q.id)}
                              isPending={del.isPending}
                              title={`Eliminar ${q.quote_number}?`}
                              successMessage="Cotización eliminada"
                              iconOnly
                            />
                          )}
                        </div>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
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
