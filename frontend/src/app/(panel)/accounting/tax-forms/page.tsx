"use client";

import { useState } from "react";
import { Download, FileText, Loader2 } from "lucide-react";
import { toast } from "sonner";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
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
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { PageHeader } from "@/components/panel/page-header";
import {
  useGenerateTaxForm,
  useTaxForms,
} from "@/lib/api/queries/accounting";
import { ClientApiError } from "@/lib/api/client";
import { TablePagination } from "@/components/panel/table-pagination";
import { formatDate } from "@/lib/format";

const FORM_TYPES = [
  { value: "104", label: "Form 104 — IVA mensual" },
  { value: "103", label: "Form 103 — Retenciones en la fuente" },
  { value: "107", label: "Form 107 — Retenciones laborales" },
  { value: "ats", label: "ATS — Anexo Transaccional" },
];

const MONTHS = [
  "Enero",
  "Febrero",
  "Marzo",
  "Abril",
  "Mayo",
  "Junio",
  "Julio",
  "Agosto",
  "Septiembre",
  "Octubre",
  "Noviembre",
  "Diciembre",
];

function errMessage(err: unknown): string {
  if (err instanceof ClientApiError) {
    const p = err.payload as { message?: string } | null;
    return p?.message ?? err.message;
  }
  return err instanceof Error ? err.message : "Error inesperado";
}

export default function TaxFormsPage() {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(20);
  const formsQ = useTaxForms({ page, per_page: perPage });
  const generate = useGenerateTaxForm();

  const [open, setOpen] = useState(false);
  const [type, setType] = useState("104");
  const [year, setYear] = useState(new Date().getFullYear());
  const [month, setMonth] = useState(new Date().getMonth() + 1);

  const submissions = formsQ.data?.data ?? [];
  const meta = formsQ.data?.meta;

  return (
    <div>
      <PageHeader
        title="Formularios tributarios"
        description="Generación de formularios SRI"
        actions={
          <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
              <Button>
                <FileText className="size-4" />
                Generar formulario
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Generar formulario tributario</DialogTitle>
                <DialogDescription>
                  Calcula los valores en base a documentos del período.
                </DialogDescription>
              </DialogHeader>
              <div className="space-y-3">
                <div className="space-y-2">
                  <Label>Tipo</Label>
                  <Select value={type} onValueChange={setType}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {FORM_TYPES.map((t) => (
                        <SelectItem key={t.value} value={t.value}>
                          {t.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="grid gap-3 sm:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="year">Año</Label>
                    <Input
                      id="year"
                      type="number"
                      min="2020"
                      value={year}
                      onChange={(e) => setYear(Number(e.target.value))}
                    />
                  </div>
                  {type !== "107" && (
                    <div className="space-y-2">
                      <Label>Mes</Label>
                      <Select
                        value={String(month)}
                        onValueChange={(v) => setMonth(Number(v))}
                      >
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          {MONTHS.map((m, i) => (
                            <SelectItem key={i + 1} value={String(i + 1)}>
                              {m}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>
                  )}
                </div>
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={() => setOpen(false)}>
                  Cancelar
                </Button>
                <Button
                  disabled={generate.isPending}
                  onClick={() =>
                    generate.mutate(
                      { type, year, month: type === "107" ? undefined : month },
                      {
                        onSuccess: () => {
                          toast.success("Formulario generado");
                          setOpen(false);
                        },
                        onError: (e) => toast.error(errMessage(e)),
                      },
                    )
                  }
                >
                  {generate.isPending && (
                    <Loader2 className="size-4 animate-spin" />
                  )}
                  Generar
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        }
      />
      <div className="p-4 lg:p-6">
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Formularios generados</CardTitle>
          </CardHeader>
          <CardContent>
            {formsQ.isLoading ? (
              <div className="flex justify-center py-12">
                <Loader2 className="size-5 animate-spin text-muted-foreground" />
              </div>
            ) : formsQ.error ? (
              <div className="text-sm text-destructive py-6 text-center">
                Error: {(formsQ.error as Error).message}
              </div>
            ) : submissions.length === 0 ? (
              <p className="text-sm text-muted-foreground py-12 text-center">
                Sin formularios generados aún.
              </p>
            ) : (
              <div className="space-y-4">
                <div className="relative">
                  {formsQ.isFetching && (
                    <div className="absolute right-2 top-2 z-10">
                      <Loader2 className="size-4 animate-spin text-muted-foreground" />
                    </div>
                  )}
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Tipo</TableHead>
                    <TableHead>Período</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead>Generado</TableHead>
                    <TableHead className="text-right">Descargar</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {submissions.map((s) => (
                    <TableRow key={s.id}>
                      <TableCell className="font-medium">
                        {s.form_type}
                      </TableCell>
                      <TableCell>
                        {s.year}
                        {s.month ? ` · ${MONTHS[s.month - 1]}` : ""}
                      </TableCell>
                      <TableCell>
                        <Badge variant="secondary">{s.status ?? "—"}</Badge>
                      </TableCell>
                      <TableCell className="text-xs text-muted-foreground">
                        {s.generated_at ? formatDate(s.generated_at) : "—"}
                      </TableCell>
                      <TableCell className="text-right">
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => {
                            window.open(
                              `/api/proxy/accounting/tax-forms/${s.id}/download`,
                              "_blank",
                            );
                          }}
                        >
                          <Download className="size-3" /> Descargar
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
                </div>
                <TablePagination
                  meta={meta}
                  page={page}
                  onPageChange={setPage}
                  perPage={perPage}
                  onPerPageChange={setPerPage}
                  isFetching={formsQ.isFetching}
                />
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
