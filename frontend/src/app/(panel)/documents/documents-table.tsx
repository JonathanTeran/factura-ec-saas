"use client";

import { useState } from "react";
import Link from "next/link";
import { Search, Loader2, MailCheck } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { useDocuments, isPendingSriStatus } from "@/lib/api/queries/documents";
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { TablePagination } from "@/components/panel/table-pagination";
import { documentStatusMeta, documentTypeLabel } from "@/lib/status";
import { formatDate, formatMoney } from "@/lib/format";

const STATUS_OPTIONS = [
  { value: "all", label: "Todos" },
  { value: "draft", label: "Borrador" },
  { value: "processing", label: "Procesando" },
  { value: "sent", label: "Enviado" },
  { value: "authorized", label: "Autorizado" },
  { value: "rejected", label: "Rechazado" },
];


export function DocumentsTable({
  documentType,
}: {
  /** Filtra a un tipo SRI fijo (01, 03, 04, 05…) para las listas dedicadas. */
  documentType?: string;
}) {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(20);
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("all");
  const debouncedSearch = useDebouncedValue(search);

  const { data, isLoading, isFetching, error } = useDocuments(
    {
      page,
      per_page: perPage,
      search: debouncedSearch || undefined,
      status: status === "all" ? undefined : status,
      document_type: documentType,
    },
    {
      // Si hay documentos esperando respuesta del SRI, la lista se refresca
      // sola hasta que todos lleguen a un estado final.
      refetchInterval: (q) =>
        q.state.data?.data.some((d) => isPendingSriStatus(d.status))
          ? 6000
          : false,
    },
  );

  const docs = data?.data ?? [];
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
              placeholder="Buscar por cliente, número o clave de acceso..."
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
              <SelectValue placeholder="Estado" />
            </SelectTrigger>
            <SelectContent>
              {STATUS_OPTIONS.map((o) => (
                <SelectItem key={o.value} value={o.value}>
                  {o.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        {error ? (
          <div className="text-sm text-destructive py-6 text-center">
            Error cargando documentos: {(error as Error).message}
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
            ) : docs.length === 0 ? (
              <div className="py-12 text-center text-sm text-muted-foreground">
                No se encontraron documentos.
              </div>
            ) : (
              <>
                {/* Tabla (escritorio) */}
                <div className="hidden md:block">
                  <Table>
                    <TableHeader>
                      <TableRow className="hover:bg-transparent">
                        <TableHead>Fecha</TableHead>
                        {!documentType && <TableHead>Tipo</TableHead>}
                        <TableHead>Número</TableHead>
                        <TableHead>Cliente</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead className="text-right">Total</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {docs.map((doc) => (
                        <TableRow
                          key={doc.id}
                          className="cursor-pointer hover:bg-muted/50"
                        >
                          <TableCell className="text-muted-foreground">
                            <Link href={`/documents/${doc.id}`} className="block py-1">
                              {formatDate(doc.issue_date ?? doc.date)}
                            </Link>
                          </TableCell>
                          {!documentType && (
                            <TableCell>
                              <Link
                                href={`/documents/${doc.id}`}
                                className="block py-1 text-sm"
                              >
                                {documentTypeLabel(doc.document_type ?? doc.type)}
                              </Link>
                            </TableCell>
                          )}
                          <TableCell>
                            <Link href={`/documents/${doc.id}`} className="block py-1 font-mono text-xs">
                              {doc.document_number ?? doc.number ?? `#${doc.id}`}
                            </Link>
                          </TableCell>
                          <TableCell className="font-medium">
                            <Link href={`/documents/${doc.id}`} className="block py-1">
                              {doc.customer?.name ?? doc.customer_name ?? "—"}
                            </Link>
                          </TableCell>
                          <TableCell>
                            <Link
                              href={`/documents/${doc.id}`}
                              className="flex items-center gap-1.5 py-1"
                            >
                              <Badge
                                variant="outline"
                                className={documentStatusMeta(doc.status).className}
                              >
                                {documentStatusMeta(doc.status).label}
                              </Badge>
                              {doc.email_sent && (
                                <span
                                  title={`Enviado por correo a ${doc.email_sent_to ?? "cliente"}`}
                                  className="grid size-5 place-items-center rounded-full bg-success/10 text-success"
                                >
                                  <MailCheck className="size-3" />
                                </span>
                              )}
                            </Link>
                          </TableCell>
                          <TableCell className="text-right font-medium tabular-nums">
                            <Link href={`/documents/${doc.id}`} className="block py-1">
                              {formatMoney(doc.total)}
                            </Link>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>

                {/* Tarjetas (móvil) */}
                <div className="space-y-2.5 md:hidden">
                  {docs.map((doc) => {
                    const meta = documentStatusMeta(doc.status);
                    return (
                      <Link
                        key={doc.id}
                        href={`/documents/${doc.id}`}
                        className="block rounded-xl border border-border bg-card p-3.5 transition active:bg-muted/50"
                      >
                        <div className="flex items-start justify-between gap-3">
                          <div className="min-w-0">
                            <p className="truncate font-medium">
                              {doc.customer?.name ?? doc.customer_name ?? "—"}
                            </p>
                            <p className="mt-0.5 font-mono text-xs text-muted-foreground">
                              {doc.document_number ?? doc.number ?? `#${doc.id}`}
                            </p>
                          </div>
                          <span className="flex shrink-0 items-center gap-1.5">
                            <Badge variant="outline" className={meta.className}>
                              {meta.label}
                            </Badge>
                            {doc.email_sent && (
                              <span
                                title={`Enviado por correo a ${doc.email_sent_to ?? "cliente"}`}
                                className="grid size-5 place-items-center rounded-full bg-success/10 text-success"
                              >
                                <MailCheck className="size-3" />
                              </span>
                            )}
                          </span>
                        </div>
                        <div className="mt-3 flex items-center justify-between text-sm">
                          <span className="text-muted-foreground">
                            {documentType
                              ? formatDate(doc.issue_date ?? doc.date)
                              : `${documentTypeLabel(doc.document_type ?? doc.type)} · ${formatDate(doc.issue_date ?? doc.date)}`}
                          </span>
                          <span className="font-semibold tabular-nums">
                            {formatMoney(doc.total)}
                          </span>
                        </div>
                      </Link>
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
