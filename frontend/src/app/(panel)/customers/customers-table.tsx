"use client";

import { useState } from "react";
import Link from "next/link";
import { Search, Loader2, Mail, Phone } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { useCustomers, useDeleteCustomer } from "@/lib/api/queries/customers";
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { TablePagination } from "@/components/panel/table-pagination";
import { DeleteConfirmButton } from "@/components/forms/delete-confirm-button";

export function CustomersTable() {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(20);
  const [search, setSearch] = useState("");
  const debouncedSearch = useDebouncedValue(search);

  const { data, isLoading, isFetching, error } = useCustomers({
    page,
    per_page: perPage,
    search: debouncedSearch || undefined,
  });
  const del = useDeleteCustomer();

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
            placeholder="Buscar por nombre, identificación o correo..."
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
                Sin clientes registrados.
              </div>
            ) : (
              <>
                {/* Tabla (escritorio) */}
                <div className="hidden md:block">
                  <Table>
                    <TableHeader>
                      <TableRow className="hover:bg-transparent">
                        <TableHead>Identificación</TableHead>
                        <TableHead>Nombre</TableHead>
                        <TableHead>Contacto</TableHead>
                        <TableHead>Dirección</TableHead>
                        <TableHead className="w-[60px]"></TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {items.map((c) => (
                        <TableRow key={c.id} className="hover:bg-muted/50">
                          <TableCell className="font-mono text-xs">
                            <Link href={`/customers/${c.id}`} className="block py-1">
                              <div>{c.identification_number}</div>
                              {c.identification_type_label && (
                                <div className="text-[10px] uppercase text-muted-foreground">
                                  {c.identification_type_label}
                                </div>
                              )}
                            </Link>
                          </TableCell>
                          <TableCell className="font-medium">
                            <Link href={`/customers/${c.id}`} className="block py-1">
                              {c.name}
                            </Link>
                          </TableCell>
                          <TableCell>
                            <div className="flex flex-col gap-1 text-xs text-muted-foreground">
                              {c.email && (
                                <span className="flex items-center gap-1">
                                  <Mail className="size-3" /> {c.email}
                                </span>
                              )}
                              {c.phone && (
                                <span className="flex items-center gap-1">
                                  <Phone className="size-3" /> {c.phone}
                                </span>
                              )}
                              {!c.email && !c.phone && "—"}
                            </div>
                          </TableCell>
                          <TableCell className="text-sm text-muted-foreground">
                            {c.address ?? "—"}
                          </TableCell>
                          <TableCell>
                            <DeleteConfirmButton
                              onConfirm={() => del.mutateAsync(c.id)}
                              isPending={del.isPending}
                              title={`Eliminar "${c.name}"?`}
                              description="Si tiene documentos emitidos, la operación puede fallar."
                              successMessage="Cliente eliminado"
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
                          href={`/customers/${c.id}`}
                          className="min-w-0 flex-1"
                        >
                          <p className="truncate font-medium">{c.name}</p>
                          <p className="mt-0.5 font-mono text-xs text-muted-foreground">
                            {c.identification_number}
                            {c.identification_type_label
                              ? ` · ${c.identification_type_label}`
                              : ""}
                          </p>
                        </Link>
                        <DeleteConfirmButton
                          onConfirm={() => del.mutateAsync(c.id)}
                          isPending={del.isPending}
                          title={`Eliminar "${c.name}"?`}
                          description="Si tiene documentos emitidos, la operación puede fallar."
                          successMessage="Cliente eliminado"
                          iconOnly
                        />
                      </div>
                      {(c.email || c.phone) && (
                        <div className="mt-2.5 flex flex-col gap-1 text-xs text-muted-foreground">
                          {c.email && (
                            <span className="flex items-center gap-1.5">
                              <Mail className="size-3" /> {c.email}
                            </span>
                          )}
                          {c.phone && (
                            <span className="flex items-center gap-1.5">
                              <Phone className="size-3" /> {c.phone}
                            </span>
                          )}
                        </div>
                      )}
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
