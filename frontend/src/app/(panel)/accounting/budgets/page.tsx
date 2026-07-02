"use client";

import { useState } from "react";
import Link from "next/link";
import { CheckCircle2, Loader2, Play, Plus, X } from "lucide-react";
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
  useActivateBudget,
  useApproveBudget,
  useBudgets,
  useCloseBudget,
} from "@/lib/api/queries/accounting";
import { ClientApiError } from "@/lib/api/client";
import { TablePagination } from "@/components/panel/table-pagination";
import { formatMoney } from "@/lib/format";
import type { Budget } from "@/lib/api/types";

function errMessage(err: unknown): string {
  if (err instanceof ClientApiError) {
    const p = err.payload as { message?: string } | null;
    return p?.message ?? err.message;
  }
  return err instanceof Error ? err.message : "Error inesperado";
}

function statusVariant(s: string): "default" | "secondary" | "destructive" {
  if (s === "active") return "default";
  if (s === "closed") return "destructive";
  return "secondary";
}

export default function BudgetsPage() {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(20);
  const budgetsQ = useBudgets({ page, per_page: perPage });
  const approve = useApproveBudget();
  const activate = useActivateBudget();
  const close = useCloseBudget();

  const items: Budget[] = (() => {
    const d = budgetsQ.data;
    if (!d) return [];
    if ("data" in d && Array.isArray(d.data)) return d.data as Budget[];
    if ("data" in d && d.data && typeof d.data === "object") {
      return ((d.data as { budgets?: Budget[] }).budgets ?? []) as Budget[];
    }
    return [];
  })();

  const meta =
    budgetsQ.data && "meta" in budgetsQ.data ? budgetsQ.data.meta : undefined;

  return (
    <div>
      <PageHeader
        title="Presupuestos"
        description="Planificación financiera por año"
        actions={
          <Button asChild>
            <Link href="/accounting/budgets/new">
              <Plus className="size-4" />
              Nuevo presupuesto
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Presupuestos</CardTitle>
          </CardHeader>
          <CardContent>
            {budgetsQ.isLoading ? (
              <div className="flex justify-center py-12">
                <Loader2 className="size-5 animate-spin text-muted-foreground" />
              </div>
            ) : budgetsQ.error ? (
              <div className="text-sm text-destructive py-6 text-center">
                Error: {(budgetsQ.error as Error).message}
              </div>
            ) : items.length === 0 ? (
              <div className="flex flex-col items-center gap-3 py-12 text-center">
                <p className="text-sm text-muted-foreground">
                  Sin presupuestos todavía.
                </p>
                <Button asChild>
                  <Link href="/accounting/budgets/new">
                    <Plus className="size-4" />
                    Crear el primero
                  </Link>
                </Button>
              </div>
            ) : (
              <div className="space-y-4">
                <div className="relative">
                  {budgetsQ.isFetching && (
                    <div className="absolute right-2 top-2 z-10">
                      <Loader2 className="size-4 animate-spin text-muted-foreground" />
                    </div>
                  )}
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Nombre</TableHead>
                    <TableHead>Año</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="text-right">Total</TableHead>
                    <TableHead className="text-right">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {items.map((b) => (
                    <TableRow key={b.id}>
                      <TableCell className="font-medium">{b.name}</TableCell>
                      <TableCell>{b.year}</TableCell>
                      <TableCell>
                        <Badge
                          variant={statusVariant(b.status)}
                          className="capitalize"
                        >
                          {b.status}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right">
                        {b.total_amount != null
                          ? formatMoney(b.total_amount)
                          : "—"}
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex justify-end gap-1">
                          {b.status === "draft" && (
                            <Button
                              variant="outline"
                              size="sm"
                              disabled={approve.isPending}
                              onClick={() =>
                                approve.mutate(b.id, {
                                  onSuccess: () => toast.success("Aprobado"),
                                  onError: (e) => toast.error(errMessage(e)),
                                })
                              }
                            >
                              <CheckCircle2 className="size-3" /> Aprobar
                            </Button>
                          )}
                          {b.status === "approved" && (
                            <Button
                              variant="outline"
                              size="sm"
                              disabled={activate.isPending}
                              onClick={() =>
                                activate.mutate(b.id, {
                                  onSuccess: () => toast.success("Activado"),
                                  onError: (e) => toast.error(errMessage(e)),
                                })
                              }
                            >
                              <Play className="size-3" /> Activar
                            </Button>
                          )}
                          {b.status === "active" && (
                            <Button
                              variant="destructive"
                              size="sm"
                              disabled={close.isPending}
                              onClick={() =>
                                close.mutate(b.id, {
                                  onSuccess: () => toast.success("Cerrado"),
                                  onError: (e) => toast.error(errMessage(e)),
                                })
                              }
                            >
                              <X className="size-3" /> Cerrar
                            </Button>
                          )}
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
                  isFetching={budgetsQ.isFetching}
                />
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
