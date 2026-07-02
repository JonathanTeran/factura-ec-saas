"use client";

import { useState } from "react";
import Link from "next/link";
import { Search, Loader2, AlertTriangle } from "lucide-react";
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
import { useProducts, useDeleteProduct } from "@/lib/api/queries/products";
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { TablePagination } from "@/components/panel/table-pagination";
import { DeleteConfirmButton } from "@/components/forms/delete-confirm-button";
import { formatMoney, formatNumber } from "@/lib/format";

export function ProductsTable() {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(20);
  const [search, setSearch] = useState("");
  const debouncedSearch = useDebouncedValue(search);

  const { data, isLoading, isFetching, error } = useProducts({
    page,
    per_page: perPage,
    search: debouncedSearch || undefined,
  });
  const del = useDeleteProduct();

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
            placeholder="Buscar por código o nombre..."
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
                Sin productos registrados.
              </div>
            ) : (
              <>
                {/* Tabla (escritorio) */}
                <div className="hidden md:block">
                  <Table>
                    <TableHeader>
                      <TableRow className="hover:bg-transparent">
                        <TableHead>Código</TableHead>
                        <TableHead>Nombre</TableHead>
                        <TableHead className="text-right">Precio</TableHead>
                        <TableHead className="text-right">Stock</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead className="w-[60px]"></TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {items.map((p) => {
                        const stock = p.stock ?? 0;
                        const tracksInventory = p.track_inventory;
                        const lowStock =
                          tracksInventory && stock <= 5 && stock > 0;
                        const noStock = tracksInventory && stock <= 0;
                        return (
                          <TableRow
                            key={p.id}
                            className="cursor-pointer hover:bg-muted/50"
                          >
                            <TableCell className="font-mono text-xs">
                              <Link
                                href={`/products/${p.id}`}
                                className="block py-1"
                              >
                                {p.code}
                              </Link>
                            </TableCell>
                            <TableCell>
                              <Link
                                href={`/products/${p.id}`}
                                className="block py-1"
                              >
                                <div className="font-medium">{p.name}</div>
                                {p.type_label && (
                                  <div className="text-xs text-muted-foreground">
                                    {p.type_label}
                                  </div>
                                )}
                              </Link>
                            </TableCell>
                            <TableCell className="text-right">
                              {formatMoney(p.unit_price)}
                            </TableCell>
                            <TableCell className="text-right">
                              {tracksInventory ? (
                                <span
                                  className={
                                    noStock
                                      ? "text-destructive font-medium"
                                      : lowStock
                                        ? "text-amber-600 font-medium"
                                        : ""
                                  }
                                >
                                  {formatNumber(stock)}
                                  {(lowStock || noStock) && (
                                    <AlertTriangle className="inline size-3 ml-1" />
                                  )}
                                </span>
                              ) : (
                                <span className="text-xs text-muted-foreground">
                                  No aplica
                                </span>
                              )}
                            </TableCell>
                            <TableCell>
                              <Badge
                                variant={p.is_active ? "default" : "secondary"}
                              >
                                {p.is_active ? "Activo" : "Inactivo"}
                              </Badge>
                            </TableCell>
                            <TableCell>
                              <DeleteConfirmButton
                                onConfirm={() => del.mutateAsync(p.id)}
                                isPending={del.isPending}
                                title={`Eliminar "${p.name}"?`}
                                description="Si está en documentos emitidos, la operación puede fallar."
                                successMessage="Producto eliminado"
                                iconOnly
                              />
                            </TableCell>
                          </TableRow>
                        );
                      })}
                    </TableBody>
                  </Table>
                </div>

                {/* Tarjetas (móvil) */}
                <div className="space-y-2.5 md:hidden">
                  {items.map((p) => {
                    const stock = p.stock ?? 0;
                    const tracksInventory = p.track_inventory;
                    const lowStock = tracksInventory && stock <= 5 && stock > 0;
                    const noStock = tracksInventory && stock <= 0;
                    return (
                      <div
                        key={p.id}
                        className="rounded-xl border border-border bg-card p-3.5"
                      >
                        <div className="flex items-start justify-between gap-3">
                          <Link
                            href={`/products/${p.id}`}
                            className="min-w-0 flex-1"
                          >
                            <p className="truncate font-medium">{p.name}</p>
                            <p className="mt-0.5 font-mono text-xs text-muted-foreground">
                              {p.code}
                              {p.type_label ? ` · ${p.type_label}` : ""}
                            </p>
                          </Link>
                          <Badge variant={p.is_active ? "default" : "secondary"}>
                            {p.is_active ? "Activo" : "Inactivo"}
                          </Badge>
                        </div>
                        <div className="mt-3 flex items-center justify-between text-sm">
                          <span className="text-muted-foreground">
                            {tracksInventory ? (
                              <span
                                className={
                                  noStock
                                    ? "text-destructive font-medium"
                                    : lowStock
                                      ? "text-amber-600 font-medium"
                                      : ""
                                }
                              >
                                Stock: {formatNumber(stock)}
                                {(lowStock || noStock) && (
                                  <AlertTriangle className="inline size-3 ml-1" />
                                )}
                              </span>
                            ) : (
                              "Stock: No aplica"
                            )}
                          </span>
                          <span className="font-semibold tabular-nums">
                            {formatMoney(p.unit_price)}
                          </span>
                        </div>
                      </div>
                    );
                  })}
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
