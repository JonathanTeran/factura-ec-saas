"use client";

import { useState } from "react";
import Link from "next/link";
import { Loader2, Search } from "lucide-react";
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
import { useAccounts, useDeleteAccount } from "@/lib/api/queries/accounting";
import { DeleteConfirmButton } from "@/components/forms/delete-confirm-button";
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { TablePagination } from "@/components/panel/table-pagination";
import { formatMoney } from "@/lib/format";

const TYPE_LABELS: Record<string, string> = {
  activo: "Activo",
  pasivo: "Pasivo",
  patrimonio: "Patrimonio",
  ingreso: "Ingreso",
  costo: "Costo",
  gasto: "Gasto",
};

export function AccountsTable() {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(50);
  const [search, setSearch] = useState("");
  const debouncedSearch = useDebouncedValue(search);
  const { data, isLoading, isFetching, error } = useAccounts({
    page,
    per_page: perPage,
    search: debouncedSearch || undefined,
  });
  const del = useDeleteAccount();

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
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Código</TableHead>
                  <TableHead>Nombre</TableHead>
                  <TableHead>Tipo</TableHead>
                  <TableHead>Naturaleza</TableHead>
                  <TableHead className="text-right">Saldo</TableHead>
                  <TableHead className="w-[60px]"></TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {isLoading ? (
                  <TableRow>
                    <TableCell colSpan={6} className="text-center py-12">
                      <Loader2 className="size-5 animate-spin mx-auto text-muted-foreground" />
                    </TableCell>
                  </TableRow>
                ) : items.length === 0 ? (
                  <TableRow>
                    <TableCell
                      colSpan={6}
                      className="text-center py-12 text-muted-foreground"
                    >
                      Sin cuentas. Crea una para empezar.
                    </TableCell>
                  </TableRow>
                ) : (
                  items.map((a) => {
                    const indent = (a.level ?? 0) * 12;
                    return (
                      <TableRow key={a.id}>
                        <TableCell className="font-mono text-xs">
                          <Link
                            href={`/accounting/accounts/${a.id}`}
                            className="block py-1"
                            style={{ paddingLeft: indent }}
                          >
                            {a.code}
                          </Link>
                        </TableCell>
                        <TableCell>
                          <Link
                            href={`/accounting/accounts/${a.id}`}
                            className="block py-1"
                          >
                            {a.name}
                          </Link>
                        </TableCell>
                        <TableCell>
                          <Badge variant="secondary" className="text-xs">
                            {TYPE_LABELS[a.account_type] ?? a.account_type}
                          </Badge>
                        </TableCell>
                        <TableCell className="text-xs uppercase text-muted-foreground">
                          {a.account_nature === "debit" ? "Deudora" : "Acreedora"}
                        </TableCell>
                        <TableCell className="text-right">
                          {a.current_balance != null
                            ? formatMoney(a.current_balance)
                            : "—"}
                        </TableCell>
                        <TableCell>
                          <DeleteConfirmButton
                            onConfirm={() => del.mutateAsync(a.id)}
                            isPending={del.isPending}
                            title={`Eliminar cuenta ${a.code}?`}
                            description="Si tiene movimientos o subcuentas, la operación puede fallar."
                            successMessage="Cuenta eliminada"
                            iconOnly
                          />
                        </TableCell>
                      </TableRow>
                    );
                  })
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
