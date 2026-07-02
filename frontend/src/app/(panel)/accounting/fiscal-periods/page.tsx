"use client";

import { useState } from "react";
import { Loader2, Lock, Plus, RotateCcw, Search, X } from "lucide-react";
import { toast } from "sonner";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
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
  useClosePeriod,
  useCreateFiscalYear,
  useFiscalPeriods,
  useLockPeriod,
  useReopenPeriod,
} from "@/lib/api/queries/accounting";
import { ClientApiError } from "@/lib/api/client";
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { formatDate } from "@/lib/format";

function statusLabel(s: string): string {
  return s === "open" ? "Abierto" : s === "closed" ? "Cerrado" : "Bloqueado";
}

function errMessage(err: unknown): string {
  if (err instanceof ClientApiError) {
    const p = err.payload as { message?: string } | null;
    return p?.message ?? err.message;
  }
  return err instanceof Error ? err.message : "Error inesperado";
}

function statusVariant(s: string): "default" | "secondary" | "destructive" {
  if (s === "open") return "default";
  if (s === "locked") return "destructive";
  return "secondary";
}

export default function FiscalPeriodsPage() {
  const periodsQ = useFiscalPeriods();
  const createYear = useCreateFiscalYear();
  const close = useClosePeriod();
  const lock = useLockPeriod();
  const reopen = useReopenPeriod();
  const [dialogOpen, setDialogOpen] = useState(false);
  const [year, setYear] = useState(new Date().getFullYear());
  const [search, setSearch] = useState("");
  const debouncedSearch = useDebouncedValue(search);

  const periods = periodsQ.data ?? [];
  const term = debouncedSearch.trim().toLowerCase();
  const filtered = term
    ? periods.filter(
        (p) =>
          String(p.year).includes(term) ||
          String(p.month ?? "").includes(term) ||
          statusLabel(p.status).toLowerCase().includes(term),
      )
    : periods;

  return (
    <div>
      <PageHeader
        title="Períodos fiscales"
        description="Apertura, cierre y bloqueo de períodos"
        actions={
          <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
            <DialogTrigger asChild>
              <Button>
                <Plus className="size-4" />
                Crear año fiscal
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Nuevo año fiscal</DialogTitle>
                <DialogDescription>
                  Crea los 12 períodos mensuales del año.
                </DialogDescription>
              </DialogHeader>
              <div className="space-y-2">
                <Label htmlFor="year">Año</Label>
                <Input
                  id="year"
                  type="number"
                  min="2020"
                  max="2099"
                  value={year}
                  onChange={(e) => setYear(Number(e.target.value))}
                />
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={() => setDialogOpen(false)}>
                  Cancelar
                </Button>
                <Button
                  disabled={createYear.isPending}
                  onClick={() =>
                    createYear.mutate(year, {
                      onSuccess: () => {
                        toast.success(`Año ${year} creado`);
                        setDialogOpen(false);
                      },
                      onError: (e) => toast.error(errMessage(e)),
                    })
                  }
                >
                  {createYear.isPending && (
                    <Loader2 className="size-4 animate-spin" />
                  )}
                  Crear
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        }
      />
      <div className="p-4 lg:p-6">
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Períodos</CardTitle>
          </CardHeader>
          <CardContent>
            {periodsQ.isLoading ? (
              <div className="flex justify-center py-12">
                <Loader2 className="size-5 animate-spin text-muted-foreground" />
              </div>
            ) : periodsQ.error ? (
              <div className="text-sm text-destructive py-6 text-center">
                Error: {(periodsQ.error as Error).message}
              </div>
            ) : periods.length === 0 ? (
              <p className="text-sm text-muted-foreground py-12 text-center">
                Sin períodos. Crea un año fiscal para empezar.
              </p>
            ) : (
              <div className="space-y-4">
                <div className="relative">
                  <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                  <Input
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Buscar por año, mes o estado..."
                    className="pl-9"
                  />
                </div>
                {filtered.length === 0 ? (
                  <p className="text-sm text-muted-foreground py-12 text-center">
                    Sin resultados para la búsqueda.
                  </p>
                ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Año</TableHead>
                    <TableHead>Mes</TableHead>
                    <TableHead>Inicio</TableHead>
                    <TableHead>Fin</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="text-right">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filtered.map((p) => (
                    <TableRow key={p.id}>
                      <TableCell>{p.year}</TableCell>
                      <TableCell>{p.month ?? "—"}</TableCell>
                      <TableCell>{formatDate(p.start_date)}</TableCell>
                      <TableCell>{formatDate(p.end_date)}</TableCell>
                      <TableCell>
                        <Badge
                          variant={statusVariant(p.status)}
                          className="capitalize"
                        >
                          {statusLabel(p.status)}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex justify-end gap-1">
                          {p.status === "open" && (
                            <Button
                              variant="outline"
                              size="sm"
                              disabled={close.isPending}
                              onClick={() =>
                                close.mutate(p.id, {
                                  onSuccess: () =>
                                    toast.success("Período cerrado"),
                                  onError: (e) => toast.error(errMessage(e)),
                                })
                              }
                            >
                              <X className="size-3" /> Cerrar
                            </Button>
                          )}
                          {p.status === "closed" && (
                            <>
                              <Button
                                variant="outline"
                                size="sm"
                                disabled={reopen.isPending}
                                onClick={() =>
                                  reopen.mutate(p.id, {
                                    onSuccess: () =>
                                      toast.success("Período reabierto"),
                                    onError: (e) => toast.error(errMessage(e)),
                                  })
                                }
                              >
                                <RotateCcw className="size-3" /> Reabrir
                              </Button>
                              <Button
                                variant="destructive"
                                size="sm"
                                disabled={lock.isPending}
                                onClick={() =>
                                  lock.mutate(p.id, {
                                    onSuccess: () =>
                                      toast.success("Período bloqueado"),
                                    onError: (e) => toast.error(errMessage(e)),
                                  })
                                }
                              >
                                <Lock className="size-3" /> Bloquear
                              </Button>
                            </>
                          )}
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
                )}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
