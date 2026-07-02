"use client";

import { useState } from "react";
import Link from "next/link";
import { Loader2, Plus, Search } from "lucide-react";
import {
  Card,
  CardContent,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
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
import { useDocuments } from "@/lib/api/queries/documents";
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { TablePagination } from "@/components/panel/table-pagination";
import { documentStatusMeta } from "@/lib/status";
import { formatDate, formatMoney } from "@/lib/format";

export default function GuidesPage() {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(20);
  const [search, setSearch] = useState("");
  const debouncedSearch = useDebouncedValue(search);

  const { data, isLoading, isFetching, error } = useDocuments({
    page,
    per_page: perPage,
    search: debouncedSearch || undefined,
    document_type: "06",
  });

  const items = data?.data ?? [];
  const meta = data?.meta;

  return (
    <div>
      <PageHeader
        title="Guías de remisión"
        description="Comprobantes SRI tipo 06 — traslado de mercadería"
        actions={
          <Button asChild>
            <Link href="/guides/new">
              <Plus className="size-4" />
              Nueva guía
            </Link>
          </Button>
        }
      />
      <div className="p-4 lg:p-6">
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
                placeholder="Buscar por número o cliente..."
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
                  <Loader2 className="absolute right-2 top-2 size-4 animate-spin text-muted-foreground" />
                )}
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Fecha</TableHead>
                      <TableHead>Número</TableHead>
                      <TableHead>Destinatario</TableHead>
                      <TableHead>Estado</TableHead>
                      <TableHead className="text-right">Total</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {isLoading ? (
                      <TableRow>
                        <TableCell colSpan={5} className="text-center py-12">
                          <Loader2 className="size-5 animate-spin mx-auto text-muted-foreground" />
                        </TableCell>
                      </TableRow>
                    ) : items.length === 0 ? (
                      <TableRow>
                        <TableCell
                          colSpan={5}
                          className="text-center py-12 text-muted-foreground"
                        >
                          Sin guías de remisión registradas.
                        </TableCell>
                      </TableRow>
                    ) : (
                      items.map((d) => (
                        <TableRow key={d.id}>
                          <TableCell>{formatDate(d.issue_date)}</TableCell>
                          <TableCell className="font-mono text-xs">
                            <Link
                              href={`/documents/${d.id}`}
                              className="hover:underline"
                            >
                              {d.document_number ?? `#${d.id}`}
                            </Link>
                          </TableCell>
                          <TableCell>{d.customer?.name ?? "—"}</TableCell>
                          <TableCell>
                            <Badge
                              variant="outline"
                              className={documentStatusMeta(d.status).className}
                            >
                              {documentStatusMeta(d.status).label}
                            </Badge>
                          </TableCell>
                          <TableCell className="text-right">
                            {formatMoney(d.total)}
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
      </div>
    </div>
  );
}
