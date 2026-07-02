"use client";

import { useState } from "react";
import { Loader2, Plus, Search } from "lucide-react";
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
  useCostCenters,
  useCreateCostCenter,
  useDeleteCostCenter,
} from "@/lib/api/queries/accounting";
import { DeleteConfirmButton } from "@/components/forms/delete-confirm-button";
import { ClientApiError } from "@/lib/api/client";
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import type { CostCenter } from "@/lib/api/types";

function errMessage(err: unknown): string {
  if (err instanceof ClientApiError) {
    const p = err.payload as { message?: string } | null;
    return p?.message ?? err.message;
  }
  return err instanceof Error ? err.message : "Error inesperado";
}

export default function CostCentersPage() {
  const ccQ = useCostCenters();
  const create = useCreateCostCenter();
  const del = useDeleteCostCenter();
  const [open, setOpen] = useState(false);
  const [code, setCode] = useState("");
  const [name, setName] = useState("");
  const [desc, setDesc] = useState("");
  const [search, setSearch] = useState("");
  const debouncedSearch = useDebouncedValue(search);

  const items: CostCenter[] = (() => {
    const d = ccQ.data;
    if (!d) return [];
    if ("data" in d && Array.isArray(d.data)) return d.data as CostCenter[];
    if ("data" in d && d.data && typeof d.data === "object") {
      return ((d.data as { cost_centers?: CostCenter[] }).cost_centers ?? []) as CostCenter[];
    }
    return [];
  })();

  const term = debouncedSearch.trim().toLowerCase();
  const filtered = term
    ? items.filter(
        (c) =>
          c.code.toLowerCase().includes(term) ||
          c.name.toLowerCase().includes(term) ||
          (c.description ?? "").toLowerCase().includes(term),
      )
    : items;

  return (
    <div>
      <PageHeader
        title="Centros de costo"
        description="Para asignar movimientos contables a unidades de negocio"
        actions={
          <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
              <Button>
                <Plus className="size-4" />
                Nuevo
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Nuevo centro de costo</DialogTitle>
              </DialogHeader>
              <div className="space-y-3">
                <div className="space-y-2">
                  <Label htmlFor="cc-code">Código</Label>
                  <Input
                    id="cc-code"
                    value={code}
                    onChange={(e) => setCode(e.target.value)}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="cc-name">Nombre</Label>
                  <Input
                    id="cc-name"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="cc-desc">Descripción</Label>
                  <Input
                    id="cc-desc"
                    value={desc}
                    onChange={(e) => setDesc(e.target.value)}
                  />
                </div>
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={() => setOpen(false)}>
                  Cancelar
                </Button>
                <Button
                  disabled={!code || !name || create.isPending}
                  onClick={() =>
                    create.mutate(
                      { code, name, description: desc || undefined },
                      {
                        onSuccess: () => {
                          toast.success("Centro creado");
                          setOpen(false);
                          setCode("");
                          setName("");
                          setDesc("");
                        },
                        onError: (e) => toast.error(errMessage(e)),
                      },
                    )
                  }
                >
                  {create.isPending && (
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
            <CardTitle className="text-base">Centros</CardTitle>
          </CardHeader>
          <CardContent>
            {ccQ.isLoading ? (
              <div className="flex justify-center py-12">
                <Loader2 className="size-5 animate-spin text-muted-foreground" />
              </div>
            ) : ccQ.error ? (
              <div className="text-sm text-destructive py-6 text-center">
                Error: {(ccQ.error as Error).message}
              </div>
            ) : items.length === 0 ? (
              <p className="text-sm text-muted-foreground py-12 text-center">
                Sin centros de costo.
              </p>
            ) : (
              <div className="space-y-4">
                <div className="relative">
                  <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                  <Input
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Buscar por código, nombre o descripción..."
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
                    <TableHead>Código</TableHead>
                    <TableHead>Nombre</TableHead>
                    <TableHead>Descripción</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="w-[60px]"></TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filtered.map((c) => (
                    <TableRow key={c.id}>
                      <TableCell className="font-mono text-xs">{c.code}</TableCell>
                      <TableCell className="font-medium">{c.name}</TableCell>
                      <TableCell className="text-sm text-muted-foreground">
                        {c.description ?? "—"}
                      </TableCell>
                      <TableCell>
                        <Badge variant={c.is_active ? "default" : "secondary"}>
                          {c.is_active ? "Activo" : "Inactivo"}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        <DeleteConfirmButton
                          onConfirm={() => del.mutateAsync(c.id)}
                          isPending={del.isPending}
                          title={`Eliminar ${c.name}?`}
                          description="Si tiene movimientos, la operación puede fallar."
                          successMessage="Centro eliminado"
                          iconOnly
                        />
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
