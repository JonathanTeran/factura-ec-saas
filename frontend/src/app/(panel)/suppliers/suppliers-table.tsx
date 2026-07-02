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
import {
  useSuppliers,
  useDeleteSupplier,
} from "@/lib/api/queries/suppliers";
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { TablePagination } from "@/components/panel/table-pagination";
import { DeleteConfirmButton } from "@/components/forms/delete-confirm-button";

export function SuppliersTable() {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(20);
  const [search, setSearch] = useState("");
  const debouncedSearch = useDebouncedValue(search);

  const { data, isLoading, isFetching, error } = useSuppliers({
    page,
    per_page: perPage,
    search: debouncedSearch || undefined,
  });
  const del = useDeleteSupplier();

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
            placeholder="Buscar por razón social, identificación..."
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
                Sin proveedores registrados.
              </div>
            ) : (
              <>
                {/* Tabla (escritorio) */}
                <div className="hidden md:block">
                  <Table>
                    <TableHeader>
                      <TableRow className="hover:bg-transparent">
                        <TableHead>Identificación</TableHead>
                        <TableHead>Razón social</TableHead>
                        <TableHead>Contacto</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead className="w-[60px]"></TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {items.map((s) => (
                        <TableRow key={s.id}>
                          <TableCell className="font-mono text-xs">
                            <Link
                              href={`/suppliers/${s.id}`}
                              className="block py-1"
                            >
                              {s.identification}
                            </Link>
                          </TableCell>
                          <TableCell>
                            <Link
                              href={`/suppliers/${s.id}`}
                              className="block py-1"
                            >
                              <div className="font-medium">
                                {s.business_name}
                              </div>
                              {s.commercial_name && (
                                <div className="text-xs text-muted-foreground">
                                  {s.commercial_name}
                                </div>
                              )}
                            </Link>
                          </TableCell>
                          <TableCell className="text-xs text-muted-foreground">
                            {s.email && <div>{s.email}</div>}
                            {s.phone && <div>{s.phone}</div>}
                            {!s.email && !s.phone && "—"}
                          </TableCell>
                          <TableCell>
                            <Badge
                              variant={s.is_active ? "default" : "secondary"}
                            >
                              {s.is_active ? "Activo" : "Inactivo"}
                            </Badge>
                          </TableCell>
                          <TableCell>
                            <DeleteConfirmButton
                              onConfirm={() => del.mutateAsync(s.id)}
                              isPending={del.isPending}
                              title={`Eliminar "${s.business_name}"?`}
                              description="Si tiene compras registradas, la operación puede fallar."
                              successMessage="Proveedor eliminado"
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
                  {items.map((s) => (
                    <div
                      key={s.id}
                      className="rounded-xl border border-border bg-card p-3.5"
                    >
                      <div className="flex items-start justify-between gap-3">
                        <Link
                          href={`/suppliers/${s.id}`}
                          className="min-w-0 flex-1"
                        >
                          <p className="truncate font-medium">
                            {s.business_name}
                          </p>
                          <p className="mt-0.5 font-mono text-xs text-muted-foreground">
                            {s.identification}
                            {s.commercial_name ? ` · ${s.commercial_name}` : ""}
                          </p>
                        </Link>
                        <DeleteConfirmButton
                          onConfirm={() => del.mutateAsync(s.id)}
                          isPending={del.isPending}
                          title={`Eliminar "${s.business_name}"?`}
                          description="Si tiene compras registradas, la operación puede fallar."
                          successMessage="Proveedor eliminado"
                          iconOnly
                        />
                      </div>
                      <div className="mt-3 flex items-center justify-between text-sm">
                        <span className="min-w-0 flex-1 truncate text-muted-foreground">
                          {s.email || s.phone || "—"}
                        </span>
                        <Badge variant={s.is_active ? "default" : "secondary"}>
                          {s.is_active ? "Activo" : "Inactivo"}
                        </Badge>
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
