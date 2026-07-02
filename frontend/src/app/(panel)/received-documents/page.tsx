"use client";

import { useState } from "react";
import { Loader2, Plus, Search } from "lucide-react";
import { toast } from "sonner";
import {
  Card,
  CardContent,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Label } from "@/components/ui/label";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
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
import { PageHeader } from "@/components/panel/page-header";
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { TablePagination } from "@/components/panel/table-pagination";
import {
  useCreateReceivedDocument,
  useDeleteReceivedDocument,
  useReceivedDocuments,
} from "@/lib/api/queries/received-documents";
import { EntityCombobox } from "@/components/forms/entity-combobox";
import { useCompanies } from "@/lib/api/queries/companies";
import { DeleteConfirmButton } from "@/components/forms/delete-confirm-button";
import { ClientApiError } from "@/lib/api/client";
import { formatDate, formatMoney } from "@/lib/format";
import { documentTypeLabel } from "@/lib/status";

const DOC_TYPES = [
  { value: "01", label: "Factura" },
  { value: "03", label: "Liquidación" },
  { value: "04", label: "Nota de crédito" },
  { value: "05", label: "Nota de débito" },
  { value: "07", label: "Retención" },
];

const CATEGORIES = [
  { value: "operating", label: "Operativo" },
  { value: "administrative", label: "Administrativo" },
  { value: "marketing", label: "Marketing" },
  { value: "logistics", label: "Logística" },
  { value: "utilities", label: "Servicios" },
  { value: "professional_services", label: "Servicios profesionales" },
  { value: "other", label: "Otro" },
];

function categoryLabel(v: string | null | undefined): string {
  if (!v) return "—";
  return CATEGORIES.find((c) => c.value === v)?.label ?? v;
}

function errMessage(err: unknown): string {
  if (err instanceof ClientApiError) {
    const p = err.payload as
      | { message?: string; errors?: Record<string, string[]> }
      | null;
    const first = p?.errors ? Object.values(p.errors).flat()[0] : null;
    return first ?? p?.message ?? err.message;
  }
  return err instanceof Error ? err.message : "Error inesperado";
}

export default function ReceivedDocumentsPage() {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(20);
  const [search, setSearch] = useState("");
  const [open, setOpen] = useState(false);
  const debouncedSearch = useDebouncedValue(search);

  const { data, isLoading, isFetching, error } = useReceivedDocuments({
    page,
    per_page: perPage,
    search: debouncedSearch || undefined,
  });
  const create = useCreateReceivedDocument();
  const del = useDeleteReceivedDocument();
  const companiesQ = useCompanies();

  const [form, setForm] = useState({
    company_id: null as number | null,
    document_type: "01",
    issuer_ruc: "",
    issuer_name: "",
    access_key: "",
    authorization_number: "",
    issue_date: new Date().toISOString().slice(0, 10),
    subtotal_0: 0,
    subtotal_5: 0,
    subtotal_12: 0,
    subtotal_15: 0,
    total_tax: 0,
    total: 0,
    expense_category: "operating",
    notes: "",
  });

  const items = data?.data ?? [];
  const meta = data?.meta;

  return (
    <div>
      <PageHeader
        title="Documentos recibidos"
        description="Comprobantes electrónicos de proveedores (compras SRI)"
        actions={
          <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
              <Button>
                <Plus className="size-4" />
                Registrar documento
              </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-2xl">
              <DialogHeader>
                <DialogTitle>Registrar documento recibido</DialogTitle>
                <DialogDescription>
                  Registra un comprobante recibido de un proveedor SRI.
                </DialogDescription>
              </DialogHeader>
              <div className="grid gap-3 sm:grid-cols-2">
                <div className="space-y-2 sm:col-span-2">
                  <Label>Empresa</Label>
                  <EntityCombobox
                    value={form.company_id}
                    onChange={(v) =>
                      setForm((f) => ({
                        ...f,
                        company_id: typeof v === "number" ? v : null,
                      }))
                    }
                    options={
                      companiesQ.data?.map((c) => ({
                        value: c.id,
                        label: c.legal_name,
                        description: `RUC ${c.ruc}`,
                      })) ?? []
                    }
                    isLoading={companiesQ.isLoading}
                    placeholder="Selecciona empresa..."
                  />
                </div>
                <div className="space-y-2">
                  <Label>Tipo</Label>
                  <Select
                    value={form.document_type}
                    onValueChange={(v) =>
                      setForm((f) => ({ ...f, document_type: v }))
                    }
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {DOC_TYPES.map((t) => (
                        <SelectItem key={t.value} value={t.value}>
                          {t.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Categoría de gasto</Label>
                  <Select
                    value={form.expense_category}
                    onValueChange={(v) =>
                      setForm((f) => ({ ...f, expense_category: v }))
                    }
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {CATEGORIES.map((c) => (
                        <SelectItem key={c.value} value={c.value}>
                          {c.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>RUC del emisor</Label>
                  <Input
                    value={form.issuer_ruc}
                    onChange={(e) =>
                      setForm((f) => ({ ...f, issuer_ruc: e.target.value }))
                    }
                    maxLength={13}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Nombre del emisor</Label>
                  <Input
                    value={form.issuer_name}
                    onChange={(e) =>
                      setForm((f) => ({ ...f, issuer_name: e.target.value }))
                    }
                  />
                </div>
                <div className="space-y-2 sm:col-span-2">
                  <Label>Clave de acceso (49 dígitos)</Label>
                  <Input
                    value={form.access_key}
                    onChange={(e) =>
                      setForm((f) => ({ ...f, access_key: e.target.value }))
                    }
                    maxLength={49}
                    className="font-mono text-xs"
                  />
                </div>
                <div className="space-y-2">
                  <Label>Fecha</Label>
                  <Input
                    type="date"
                    value={form.issue_date}
                    onChange={(e) =>
                      setForm((f) => ({ ...f, issue_date: e.target.value }))
                    }
                  />
                </div>
                <div className="space-y-2">
                  <Label>Total</Label>
                  <Input
                    type="number"
                    step="0.01"
                    min="0"
                    value={form.total}
                    onChange={(e) =>
                      setForm((f) => ({
                        ...f,
                        total: Number(e.target.value) || 0,
                      }))
                    }
                  />
                </div>
                <div className="space-y-2">
                  <Label>IVA total</Label>
                  <Input
                    type="number"
                    step="0.01"
                    min="0"
                    value={form.total_tax}
                    onChange={(e) =>
                      setForm((f) => ({
                        ...f,
                        total_tax: Number(e.target.value) || 0,
                      }))
                    }
                  />
                </div>
                <div className="space-y-2 sm:col-span-2">
                  <Label>Notas</Label>
                  <Input
                    value={form.notes}
                    onChange={(e) =>
                      setForm((f) => ({ ...f, notes: e.target.value }))
                    }
                  />
                </div>
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={() => setOpen(false)}>
                  Cancelar
                </Button>
                <Button
                  disabled={
                    create.isPending ||
                    !form.company_id ||
                    !form.issuer_ruc ||
                    !form.issuer_name
                  }
                  onClick={() =>
                    create.mutate(
                      {
                        ...form,
                        company_id: form.company_id as number,
                      },
                      {
                        onSuccess: () => {
                          toast.success("Documento registrado");
                          setOpen(false);
                        },
                        onError: (e) => toast.error(errMessage(e)),
                      },
                    )
                  }
                >
                  {create.isPending && (
                    <Loader2 className="size-4 animate-spin" />
                  )}
                  Registrar
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
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
                placeholder="Buscar por proveedor o clave..."
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
                  <Loader2 className="absolute right-2 top-2 z-10 size-4 animate-spin text-muted-foreground" />
                )}
                {isLoading ? (
                  <div className="flex justify-center py-12">
                    <Loader2 className="size-5 animate-spin text-muted-foreground" />
                  </div>
                ) : items.length === 0 ? (
                  <div className="py-12 text-center text-sm text-muted-foreground">
                    Sin documentos recibidos.
                  </div>
                ) : (
                  <>
                    {/* Tabla (escritorio) */}
                    <div className="hidden md:block">
                      <Table>
                        <TableHeader>
                          <TableRow className="hover:bg-transparent">
                            <TableHead>Fecha</TableHead>
                            <TableHead>Tipo</TableHead>
                            <TableHead>Emisor</TableHead>
                            <TableHead>RUC</TableHead>
                            <TableHead>Categoría</TableHead>
                            <TableHead className="text-right">Total</TableHead>
                            <TableHead className="w-[60px]"></TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          {items.map((d) => (
                            <TableRow key={d.id}>
                              <TableCell>{formatDate(d.issue_date)}</TableCell>
                              <TableCell className="text-xs">
                                {documentTypeLabel(d.document_type)}
                              </TableCell>
                              <TableCell className="font-medium">
                                {d.issuer_name}
                              </TableCell>
                              <TableCell className="font-mono text-xs">
                                {d.issuer_ruc}
                              </TableCell>
                              <TableCell>
                                <Badge variant="secondary">
                                  {categoryLabel(d.expense_category)}
                                </Badge>
                              </TableCell>
                              <TableCell className="text-right font-medium">
                                {formatMoney(d.total)}
                              </TableCell>
                              <TableCell>
                                <DeleteConfirmButton
                                  onConfirm={() => del.mutateAsync(d.id)}
                                  isPending={del.isPending}
                                  title="¿Eliminar documento?"
                                  successMessage="Eliminado"
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
                      {items.map((d) => (
                        <div
                          key={d.id}
                          className="rounded-xl border border-border bg-card p-3.5"
                        >
                          <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0 flex-1">
                              <p className="truncate font-medium">
                                {d.issuer_name}
                              </p>
                              <p className="mt-0.5 font-mono text-xs text-muted-foreground">
                                {d.issuer_ruc}
                              </p>
                            </div>
                            <DeleteConfirmButton
                              onConfirm={() => del.mutateAsync(d.id)}
                              isPending={del.isPending}
                              title="¿Eliminar documento?"
                              successMessage="Eliminado"
                              iconOnly
                            />
                          </div>
                          <div className="mt-3 flex items-center justify-between gap-3 text-sm">
                            <span className="min-w-0 flex-1 truncate text-muted-foreground">
                              {documentTypeLabel(d.document_type)} ·{" "}
                              {categoryLabel(d.expense_category)} ·{" "}
                              {formatDate(d.issue_date)}
                            </span>
                            <span className="font-semibold tabular-nums">
                              {formatMoney(d.total)}
                            </span>
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
      </div>
    </div>
  );
}
