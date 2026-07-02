"use client";

import { useMemo, useState } from "react";
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
import {
  useCategories,
  useDeleteCategory,
} from "@/lib/api/queries/categories";
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { DeleteConfirmButton } from "@/components/forms/delete-confirm-button";

export function CategoriesTable() {
  const [search, setSearch] = useState("");
  const debouncedSearch = useDebouncedValue(search);

  // El endpoint devuelve la lista plana completa: búsqueda en el cliente.
  const { data, isLoading, isFetching, error } = useCategories();
  const del = useDeleteCategory();

  const all = useMemo(() => data?.data ?? [], [data]);
  const items = useMemo(() => {
    const term = debouncedSearch.trim().toLowerCase();
    if (!term) return all;
    return all.filter(
      (c) =>
        c.name.toLowerCase().includes(term) ||
        (c.description ?? "").toLowerCase().includes(term),
    );
  }, [all, debouncedSearch]);

  return (
    <Card>
      <CardContent className="p-4 space-y-4">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
          <Input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Buscar categoría..."
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
                {debouncedSearch.trim()
                  ? "No se encontraron categorías para la búsqueda."
                  : "Sin categorías. Crea una para clasificar productos."}
              </div>
            ) : (
              <>
                {/* Tabla (escritorio) */}
                <div className="hidden md:block">
                  <Table>
                    <TableHeader>
                      <TableRow className="hover:bg-transparent">
                        <TableHead>Nombre</TableHead>
                        <TableHead>Padre</TableHead>
                        <TableHead className="text-right">Productos</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead className="w-[60px]"></TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {items.map((c) => (
                        <TableRow key={c.id}>
                          <TableCell>
                            <Link
                              href={`/categories/${c.id}`}
                              className="block py-1"
                            >
                              <div className="flex items-center gap-2">
                                {c.color && (
                                  <span
                                    aria-hidden
                                    className="inline-block size-2.5 rounded-full"
                                    style={{ backgroundColor: c.color }}
                                  />
                                )}
                                <span className="font-medium">{c.name}</span>
                              </div>
                              {c.description && (
                                <div className="text-xs text-muted-foreground truncate max-w-md">
                                  {c.description}
                                </div>
                              )}
                            </Link>
                          </TableCell>
                          <TableCell className="text-sm text-muted-foreground">
                            {c.parent_id ? `#${c.parent_id}` : "—"}
                          </TableCell>
                          <TableCell className="text-right">
                            {c.product_count ?? "—"}
                          </TableCell>
                          <TableCell>
                            <Badge
                              variant={c.is_active ? "default" : "secondary"}
                            >
                              {c.is_active ? "Activa" : "Inactiva"}
                            </Badge>
                          </TableCell>
                          <TableCell>
                            <DeleteConfirmButton
                              onConfirm={() => del.mutateAsync(c.id)}
                              isPending={del.isPending}
                              title={`Eliminar "${c.name}"?`}
                              description="Si tiene subcategorías o productos, la operación puede fallar."
                              successMessage="Categoría eliminada"
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
                  {items.map((c) => (
                    <div
                      key={c.id}
                      className="rounded-xl border border-border bg-card p-3.5"
                    >
                      <div className="flex items-start justify-between gap-3">
                        <Link
                          href={`/categories/${c.id}`}
                          className="min-w-0 flex-1"
                        >
                          <div className="flex items-center gap-2">
                            {c.color && (
                              <span
                                aria-hidden
                                className="inline-block size-2.5 shrink-0 rounded-full"
                                style={{ backgroundColor: c.color }}
                              />
                            )}
                            <p className="truncate font-medium">{c.name}</p>
                          </div>
                          {c.description && (
                            <p className="mt-0.5 truncate text-xs text-muted-foreground">
                              {c.description}
                            </p>
                          )}
                        </Link>
                        <DeleteConfirmButton
                          onConfirm={() => del.mutateAsync(c.id)}
                          isPending={del.isPending}
                          title={`Eliminar "${c.name}"?`}
                          description="Si tiene subcategorías o productos, la operación puede fallar."
                          successMessage="Categoría eliminada"
                          iconOnly
                        />
                      </div>
                      <div className="mt-3 flex items-center justify-between text-sm">
                        <span className="text-muted-foreground">
                          {c.product_count ?? "—"} productos
                        </span>
                        <Badge variant={c.is_active ? "default" : "secondary"}>
                          {c.is_active ? "Activa" : "Inactiva"}
                        </Badge>
                      </div>
                    </div>
                  ))}
                </div>
              </>
            )}
          </div>
        )}

      </CardContent>
    </Card>
  );
}
