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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useJournalEntries } from "@/lib/api/queries/accounting";
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { TablePagination } from "@/components/panel/table-pagination";
import { formatDate, formatMoney } from "@/lib/format";

const STATUS_LABELS: Record<string, string> = {
  draft: "Borrador",
  posted: "Posteado",
  void: "Anulado",
};

function statusVariant(status: string): "default" | "secondary" | "destructive" {
  if (status === "posted") return "default";
  if (status === "void") return "destructive";
  return "secondary";
}

export function JournalEntriesTable() {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(20);
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState<string>("all");
  const debouncedSearch = useDebouncedValue(search);

  const { data, isLoading, isFetching, error } = useJournalEntries({
    page,
    per_page: perPage,
    search: debouncedSearch || undefined,
    status: status === "all" ? undefined : status,
  });

  const items = data?.data ?? [];
  const meta = data?.meta;

  return (
    <Card>
      <CardContent className="p-4 space-y-4">
        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
            <Input
              value={search}
              onChange={(e) => {
                setSearch(e.target.value);
                setPage(1);
              }}
              placeholder="Buscar por número o descripción..."
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
              <SelectItem value="all">Todos</SelectItem>
              <SelectItem value="draft">Borrador</SelectItem>
              <SelectItem value="posted">Posteado</SelectItem>
              <SelectItem value="void">Anulado</SelectItem>
            </SelectContent>
          </Select>
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
                  <TableHead>Número</TableHead>
                  <TableHead>Fecha</TableHead>
                  <TableHead>Descripción</TableHead>
                  <TableHead>Estado</TableHead>
                  <TableHead className="text-right">Debe</TableHead>
                  <TableHead className="text-right">Haber</TableHead>
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
                    <TableCell colSpan={6} className="text-center py-12 text-muted-foreground">
                      Sin asientos.
                    </TableCell>
                  </TableRow>
                ) : (
                  items.map((j) => (
                    <TableRow
                      key={j.id}
                      className="cursor-pointer hover:bg-muted/50"
                    >
                      <TableCell className="font-mono text-xs">
                        <Link href={`/accounting/journal-entries/${j.id}`} className="block py-1">
                          {j.entry_number ?? `#${j.id}`}
                        </Link>
                      </TableCell>
                      <TableCell>
                        <Link href={`/accounting/journal-entries/${j.id}`} className="block py-1">
                          {formatDate(j.entry_date)}
                        </Link>
                      </TableCell>
                      <TableCell>
                        <Link href={`/accounting/journal-entries/${j.id}`} className="block py-1">
                          {j.description ?? "—"}
                        </Link>
                      </TableCell>
                      <TableCell>
                        <Badge variant={statusVariant(j.status)}>
                          {STATUS_LABELS[j.status] ?? j.status}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right font-mono">
                        {formatMoney(j.total_debit)}
                      </TableCell>
                      <TableCell className="text-right font-mono">
                        {formatMoney(j.total_credit)}
                      </TableCell>
                    </TableRow>
                  ))
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
