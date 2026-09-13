"use client";

import { useState } from "react";
import Link from "next/link";
import { AlertTriangle, Loader2, Pause, Play, RefreshCw, Search } from "lucide-react";
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
  FREQ_LABELS,
  useDeleteRecurringInvoice,
  useRecurringInvoiceAction,
  useRecurringInvoices,
} from "@/lib/api/queries/recurring-invoices";
import { DeleteConfirmButton } from "@/components/forms/delete-confirm-button";
import { ClientApiError } from "@/lib/api/client";
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { TablePagination } from "@/components/panel/table-pagination";
import { formatDate, formatMoney } from "@/lib/format";

const STATUS_OPTIONS = [
  { value: "all", label: "Todos los estados" },
  { value: "active", label: "Activas" },
  { value: "paused", label: "Pausadas" },
  { value: "completed", label: "Completadas" },
  { value: "cancelled", label: "Canceladas" },
];

const STATUS_LABELS: Record<string, string> = {
  active: "Activa",
  paused: "Pausada",
  completed: "Completada",
  cancelled: "Cancelada",
};

function statusVariant(s: string): "default" | "secondary" | "destructive" {
  if (s === "active") return "default";
  if (s === "cancelled") return "destructive";
  return "secondary";
}

function errMessage(err: unknown): string {
  if (err instanceof ClientApiError) {
    const p = err.payload as { message?: string } | null;
    return p?.message ?? err.message;
  }
  return err instanceof Error ? err.message : "Error inesperado";
}

export function RecurringTable() {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(20);
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("all");
  const debouncedSearch = useDebouncedValue(search);
  const { data, isLoading, isFetching, error } = useRecurringInvoices({
    page,
    per_page: perPage,
    search: debouncedSearch || undefined,
    status: status === "all" ? undefined : status,
  });
  const action = useRecurringInvoiceAction();
  const del = useDeleteRecurringInvoice();

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
              placeholder="Buscar por nombre o cliente..."
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
            <SelectTrigger className="sm:w-48">
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
        ) : isLoading ? (
          <Loader2 className="mx-auto my-12 size-5 animate-spin text-muted-foreground" />
        ) : items.length === 0 ? (
          <div className="flex flex-col items-center gap-3 py-12 text-center">
            <RefreshCw className="size-6 text-muted-foreground" />
            <p className="text-sm text-muted-foreground">
              {debouncedSearch || status !== "all"
                ? "Sin recurrentes con ese filtro."
                : "Aún no tienes facturas recurrentes. Programa un cobro periódico y olvídate de emitirlo a mano."}
            </p>
            {!debouncedSearch && status === "all" && (
              <Button asChild size="sm">
                <Link href="/recurring-invoices/new">Crear la primera</Link>
              </Button>
            )}
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
                  <TableHead>Recurrente</TableHead>
                  <TableHead>Cliente</TableHead>
                  <TableHead>Frecuencia</TableHead>
                  <TableHead>Próxima emisión</TableHead>
                  <TableHead>Estado</TableHead>
                  <TableHead className="text-right">Emitidas</TableHead>
                  <TableHead className="text-right">Por emisión</TableHead>
                  <TableHead className="text-right">Acciones</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {items.map((r) => (
                  <TableRow key={r.id}>
                    <TableCell>
                      <Link href={`/recurring-invoices/${r.id}`} className="block py-1 font-medium">
                        {r.name || r.customer?.name || `Recurrente #${r.id}`}
                      </Link>
                    </TableCell>
                    <TableCell>{r.customer?.name ?? "—"}</TableCell>
                    <TableCell>{r.frequency_label ?? FREQ_LABELS[r.frequency] ?? r.frequency}</TableCell>
                    <TableCell>
                      {r.status === "active" && r.next_issue_date ? formatDate(r.next_issue_date) : "—"}
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <Badge variant={statusVariant(r.status)}>
                          {r.status_label ?? STATUS_LABELS[r.status] ?? r.status}
                        </Badge>
                        {r.last_error && (
                          <span title={r.last_error} className="text-warning">
                            <AlertTriangle className="size-4" />
                          </span>
                        )}
                      </div>
                    </TableCell>
                    <TableCell className="text-right tabular-nums">
                      {r.total_issued}
                      {r.max_issues != null && ` / ${r.max_issues}`}
                    </TableCell>
                    <TableCell className="text-right font-medium tabular-nums">
                      {formatMoney(r.estimated_total ?? 0)}
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-1">
                        {r.status === "active" && (
                          <Button
                            size="sm"
                            variant="outline"
                            disabled={action.isPending}
                            onClick={() =>
                              action.mutate(
                                { id: r.id, action: "pause" },
                                {
                                  onSuccess: () => toast.success("Recurrente pausada"),
                                  onError: (e) => toast.error(errMessage(e)),
                                },
                              )
                            }
                          >
                            <Pause className="size-3" /> Pausar
                          </Button>
                        )}
                        {r.status === "paused" && (
                          <Button
                            size="sm"
                            variant="outline"
                            disabled={action.isPending}
                            onClick={() =>
                              action.mutate(
                                { id: r.id, action: "resume" },
                                {
                                  onSuccess: () => toast.success("Recurrente reanudada"),
                                  onError: (e) => toast.error(errMessage(e)),
                                },
                              )
                            }
                          >
                            <Play className="size-3" /> Reanudar
                          </Button>
                        )}
                        <DeleteConfirmButton
                          onConfirm={() => del.mutateAsync(r.id)}
                          isPending={del.isPending}
                          title="¿Eliminar recurrente?"
                          description="Las facturas ya generadas se conservan; solo se detiene la programación."
                          successMessage="Recurrente eliminada"
                          iconOnly
                        />
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
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
