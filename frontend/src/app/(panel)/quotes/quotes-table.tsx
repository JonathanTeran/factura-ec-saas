"use client";

import { useState } from "react";
import Link from "next/link";
import { FileDown, Loader2, Search } from "lucide-react";
import { toast } from "sonner";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  downloadQuotePdf,
  useDeleteQuote,
  useQuoteAction,
  useQuotes,
} from "@/lib/api/queries/quotes";
import { DeleteConfirmButton } from "@/components/forms/delete-confirm-button";
import { ClientApiError } from "@/lib/api/client";
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { TablePagination } from "@/components/panel/table-pagination";
import { formatDate, formatMoney } from "@/lib/format";

const STATUS_OPTIONS = [
  { value: "all", label: "Todos los estados" },
  { value: "draft", label: "Borrador" },
  { value: "sent", label: "Enviada" },
  { value: "accepted", label: "Aceptada" },
  { value: "rejected", label: "Rechazada" },
  { value: "invoiced", label: "Facturada" },
  { value: "expired", label: "Vencida" },
];

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
  const [status, setStatus] = useState("all");
  const debouncedSearch = useDebouncedValue(search);
  const { data, isLoading, isFetching, error } = useQuotes({
    page,
    per_page: perPage,
    search: debouncedSearch || undefined,
    status: status === "all" ? undefined : status,
  });
  const del = useDeleteQuote();
  const action = useQuoteAction();

  const items = data?.data ?? [];
  const meta = data?.meta;

  return (
    <Card>
      <CardContent className="space-y-4 p-4">
        <div className="flex flex-col gap-3 sm:flex-row">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
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
          <Select
            value={status}
            onValueChange={(v) => {
              setStatus(v);
              setPage(1);
            }}
          >
            <SelectTrigger className="sm:w-52">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {STATUS_OPTIONS.map((o) => (
                <SelectItem key={o.value} value={o.value}>
                  {o.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        {error ? (
          <div className="py-6 text-center text-sm text-destructive">
            Error: {(error as Error).message}
          </div>
        ) : (
          <div className="relative overflow-x-auto">
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
                  <TableHead>Válida hasta</TableHead>
                  <TableHead>Estado</TableHead>
                  <TableHead className="text-right">Total</TableHead>
                  <TableHead className="text-right">Acciones</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {isLoading ? (
                  <TableRow>
                    <TableCell colSpan={7} className="py-12 text-center">
                      <Loader2 className="mx-auto size-5 animate-spin text-muted-foreground" />
                    </TableCell>
                  </TableRow>
                ) : items.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={7} className="py-12 text-center text-muted-foreground">
                      Sin cotizaciones.
                    </TableCell>
                  </TableRow>
                ) : (
                  items.map((q) => (
                    <TableRow key={q.id}>
                      <TableCell className="font-mono text-xs">
                        <Link href={`/quotes/${q.id}`} className="block py-1 font-medium">
                          {q.quote_number}
                        </Link>
                      </TableCell>
                      <TableCell>{formatDate(q.issue_date)}</TableCell>
                      <TableCell>{q.customer?.name ?? "—"}</TableCell>
                      <TableCell className={q.is_expired ? "text-destructive" : ""}>
                        {q.expiry_date ? formatDate(q.expiry_date) : "—"}
                      </TableCell>
                      <TableCell>
                        <Badge variant={statusVariant(q.status)}>
                          {q.status_label ?? q.status}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right font-medium tabular-nums">
                        {formatMoney(q.total)}
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex justify-end gap-1">
                          <Button
                            size="icon"
                            variant="ghost"
                            title="Descargar PDF"
                            onClick={() =>
                              downloadQuotePdf(q.id).catch((e) => toast.error(errMessage(e)))
                            }
                          >
                            <FileDown className="size-4" />
                          </Button>
                          {q.can_convert && (
                            <Button size="sm" variant="outline" asChild>
                              <Link href={`/quotes/${q.id}`}>Convertir</Link>
                            </Button>
                          )}
                          {q.can_accept && (
                            <Button
                              size="sm"
                              variant="outline"
                              disabled={action.isPending}
                              onClick={() =>
                                action.mutate(
                                  { id: q.id, action: "accept" },
                                  {
                                    onSuccess: () => toast.success("Aceptada"),
                                    onError: (e) => toast.error(errMessage(e)),
                                  },
                                )
                              }
                            >
                              Aceptar
                            </Button>
                          )}
                          {q.can_reject && (
                            <Button
                              size="sm"
                              variant="ghost"
                              disabled={action.isPending}
                              onClick={() =>
                                action.mutate(
                                  { id: q.id, action: "reject" },
                                  {
                                    onSuccess: () => toast.success("Rechazada"),
                                    onError: (e) => toast.error(errMessage(e)),
                                  },
                                )
                              }
                            >
                              Rechazar
                            </Button>
                          )}
                          {q.can_delete && (
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
