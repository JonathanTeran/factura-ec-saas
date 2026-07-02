"use client";

import { useState } from "react";
import { Loader2, Pause, Play } from "lucide-react";
import { toast } from "sonner";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
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
import { PageHeader } from "@/components/panel/page-header";
import {
  useDeleteRecurringInvoice,
  useRecurringInvoiceAction,
  useRecurringInvoices,
} from "@/lib/api/queries/recurring-invoices";
import { DeleteConfirmButton } from "@/components/forms/delete-confirm-button";
import { ClientApiError } from "@/lib/api/client";
import { TablePagination } from "@/components/panel/table-pagination";
import { formatDate } from "@/lib/format";

const FREQ_LABELS: Record<string, string> = {
  daily: "Diaria",
  weekly: "Semanal",
  biweekly: "Quincenal",
  monthly: "Mensual",
  quarterly: "Trimestral",
  yearly: "Anual",
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

export default function RecurringInvoicesPage() {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(20);
  const { data, isLoading, isFetching, error } = useRecurringInvoices({
    page,
    per_page: perPage,
  });
  const action = useRecurringInvoiceAction();
  const del = useDeleteRecurringInvoice();

  const items = data?.data ?? [];
  const meta = data?.meta;

  return (
    <div>
      <PageHeader
        title="Facturas recurrentes"
        description="Programación automática de emisión de facturas"
      />
      <div className="p-4 lg:p-6">
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Programación</CardTitle>
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <Loader2 className="size-5 animate-spin mx-auto my-12 text-muted-foreground" />
            ) : error ? (
              <div className="text-sm text-destructive py-6 text-center">
                Error: {(error as Error).message}
              </div>
            ) : items.length === 0 ? (
              <p className="text-sm text-muted-foreground py-12 text-center">
                Sin recurrentes configuradas. La creación se hace por API
                (la UI completa se construirá cuando haya demanda).
              </p>
            ) : (
              <div className="space-y-4">
                <div className="relative">
                  {isFetching && (
                    <div className="absolute right-2 top-2 z-10">
                      <Loader2 className="size-4 animate-spin text-muted-foreground" />
                    </div>
                  )}
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Cliente</TableHead>
                    <TableHead>Frecuencia</TableHead>
                    <TableHead>Próxima emisión</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="text-right">Emitidas</TableHead>
                    <TableHead className="text-right">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {items.map((r) => (
                    <TableRow key={r.id}>
                      <TableCell>{r.customer?.name ?? "—"}</TableCell>
                      <TableCell>
                        {FREQ_LABELS[r.frequency] ?? r.frequency}
                      </TableCell>
                      <TableCell>{formatDate(r.next_issue_date)}</TableCell>
                      <TableCell>
                        <Badge variant={statusVariant(r.status)}>
                          {r.status}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right">
                        {r.total_issued}
                        {r.max_issues != null && ` / ${r.max_issues}`}
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
                                    onSuccess: () => toast.success("Pausada"),
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
                                    onSuccess: () => toast.success("Reanudada"),
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
                            successMessage="Eliminada"
                            iconOnly
                          />
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
                </div>
                <TablePagination
                  meta={meta}
                  page={page}
                  onPageChange={setPage}
                  perPage={perPage}
                  onPerPageChange={setPerPage}
                  isFetching={isFetching}
                />
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
