"use client";

import { useState } from "react";
import { Loader2 } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { usePosSessions } from "@/lib/api/queries/pos";
import { TablePagination } from "@/components/panel/table-pagination";
import { formatDate, formatMoney } from "@/lib/format";
import type { ApiPaginated, ApiSuccess } from "@/lib/api/client";
import type { PosSession } from "@/lib/api/types";

export function SessionsList() {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(20);
  const { data, isLoading, isFetching, error } = usePosSessions({
    page,
    per_page: perPage,
  });

  const sessions: PosSession[] = (() => {
    if (!data) return [];
    const d = data as
      | ApiPaginated<PosSession>
      | ApiSuccess<{ sessions: PosSession[] }>;
    if ("data" in d && Array.isArray(d.data)) return d.data;
    if ("data" in d && d.data && typeof d.data === "object") {
      const inner = (d.data as { sessions?: PosSession[] }).sessions;
      return Array.isArray(inner) ? inner : [];
    }
    return [];
  })();

  const meta = data && "meta" in data ? data.meta : undefined;

  if (isLoading) {
    return (
      <div className="flex justify-center py-24">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="text-sm text-destructive py-6">
        Error: {(error as Error).message}
      </div>
    );
  }

  return (
    <Card>
      <CardContent className="p-4 space-y-4">
        <div className="relative">
          {isFetching && (
            <div className="absolute right-2 top-2 z-10">
              <Loader2 className="size-4 animate-spin text-muted-foreground" />
            </div>
          )}
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>ID</TableHead>
              <TableHead>Apertura</TableHead>
              <TableHead>Cierre</TableHead>
              <TableHead>Estado</TableHead>
              <TableHead className="text-right">Inicial</TableHead>
              <TableHead className="text-right">Final</TableHead>
              <TableHead className="text-right">Diferencia</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {sessions.length === 0 ? (
              <TableRow>
                <TableCell
                  colSpan={7}
                  className="text-center py-12 text-muted-foreground"
                >
                  Sin sesiones registradas.
                </TableCell>
              </TableRow>
            ) : (
              sessions.map((s) => (
                <TableRow key={s.id}>
                  <TableCell className="font-mono text-xs">#{s.id}</TableCell>
                  <TableCell>{formatDate(s.opened_at)}</TableCell>
                  <TableCell>
                    {s.closed_at ? formatDate(s.closed_at) : "—"}
                  </TableCell>
                  <TableCell>
                    <Badge
                      variant={s.status === "open" ? "default" : "secondary"}
                    >
                      {s.status === "open" ? "Abierta" : "Cerrada"}
                    </Badge>
                  </TableCell>
                  <TableCell className="text-right">
                    {formatMoney(s.opening_amount ?? 0)}
                  </TableCell>
                  <TableCell className="text-right">
                    {s.closing_amount != null
                      ? formatMoney(s.closing_amount)
                      : "—"}
                  </TableCell>
                  <TableCell className="text-right">
                    {s.difference != null ? (
                      <span
                        className={
                          s.difference < 0
                            ? "text-rose-600"
                            : s.difference > 0
                              ? "text-emerald-600"
                              : ""
                        }
                      >
                        {formatMoney(s.difference)}
                      </span>
                    ) : (
                      "—"
                    )}
                  </TableCell>
                </TableRow>
              ))
            )}
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
      </CardContent>
    </Card>
  );
}
