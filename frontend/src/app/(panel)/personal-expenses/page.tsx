"use client";

import { useState } from "react";
import { Loader2, Plus } from "lucide-react";
import { toast } from "sonner";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
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
import {
  useCreatePersonalExpense,
  useDeductibleBudget,
  useDeletePersonalExpense,
  usePersonalExpenseSummary,
  usePersonalExpenses,
  useSaveDeductibleBudget,
} from "@/lib/api/queries/personal-expenses";
import { DeleteConfirmButton } from "@/components/forms/delete-confirm-button";
import { ClientApiError } from "@/lib/api/client";
import { TablePagination } from "@/components/panel/table-pagination";
import { formatDate, formatMoney } from "@/lib/format";
import { cn } from "@/lib/utils";

const CATEGORIES = [
  { value: "housing", label: "Vivienda" },
  { value: "education", label: "Educación" },
  { value: "health", label: "Salud" },
  { value: "food", label: "Alimentación" },
  { value: "clothing", label: "Vestimenta" },
  { value: "tourism", label: "Turismo" },
  { value: "art", label: "Arte y cultura" },
  { value: "other", label: "Otros" },
];

function categoryLabel(v: string) {
  return CATEGORIES.find((c) => c.value === v)?.label ?? v;
}

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

function BudgetRow({
  label,
  spent,
  budget,
  strong = false,
}: {
  label: string;
  spent: number;
  budget: number;
  strong?: boolean;
}) {
  const exceeded = budget > 0 && spent > budget;
  const pct = budget > 0 ? Math.min((spent / budget) * 100, 100) : 0;

  return (
    <div className="flex flex-col gap-1.5 sm:flex-row sm:items-center sm:gap-4">
      <div
        className={cn(
          "w-full sm:w-32 shrink-0 text-sm",
          strong ? "font-semibold" : "text-muted-foreground",
        )}
      >
        {label}
      </div>
      <div className="flex-1 min-w-0">
        <div
          className={cn(
            "rounded-full bg-muted overflow-hidden",
            strong ? "h-2.5" : "h-2",
          )}
        >
          <div
            className={cn(
              "h-full rounded-full transition-all",
              exceeded ? "bg-destructive" : "bg-primary",
            )}
            style={{ width: `${pct}%` }}
          />
        </div>
      </div>
      <div className="flex items-center justify-between gap-3 sm:w-72 sm:justify-end">
        <span
          className={cn(
            "text-sm tabular-nums",
            strong ? "font-semibold" : "font-medium",
          )}
        >
          {formatMoney(spent)}
          <span className="text-muted-foreground font-normal">
            {" "}
            / {formatMoney(budget)}
          </span>
        </span>
        <span
          className={cn(
            "text-xs tabular-nums text-right sm:w-32",
            budget === 0
              ? "text-muted-foreground"
              : exceeded
                ? "text-destructive font-medium"
                : "text-emerald-600",
            strong && "font-medium",
          )}
        >
          {budget === 0
            ? "Sin presupuesto"
            : exceeded
              ? `Se excedió ${formatMoney(spent - budget)}`
              : `Restan ${formatMoney(budget - spent)}`}
        </span>
      </div>
    </div>
  );
}

function DeductibleBudgetCard() {
  const now = new Date();
  const currentYear = now.getFullYear();
  const [month, setMonth] = useState(now.getMonth() + 1);
  const [year, setYear] = useState(currentYear);
  const [configOpen, setConfigOpen] = useState(false);
  const [draft, setDraft] = useState<Record<string, string>>({});

  const budgetQ = useDeductibleBudget(year, month);
  const save = useSaveDeductibleBudget();

  const budgets = budgetQ.data?.budgets ?? {};
  const spent = budgetQ.data?.spent ?? {};
  const totalBudget = CATEGORIES.reduce(
    (sum, c) => sum + (budgets[c.value] ?? 0),
    0,
  );
  const totalSpent = CATEGORIES.reduce(
    (sum, c) => sum + (spent[c.value] ?? 0),
    0,
  );

  const years = [currentYear - 2, currentYear - 1, currentYear];

  const openConfig = () => {
    setDraft(
      Object.fromEntries(
        CATEGORIES.map((c) => [
          c.value,
          budgets[c.value] ? String(budgets[c.value]) : "",
        ]),
      ),
    );
    setConfigOpen(true);
  };

  const handleSave = () => {
    const payload = Object.fromEntries(
      CATEGORIES.map((c) => [c.value, Number(draft[c.value]) || 0]),
    );
    save.mutate(payload, {
      onSuccess: () => {
        toast.success("Presupuesto guardado");
        setConfigOpen(false);
      },
      onError: (e) => toast.error(errMessage(e)),
    });
  };

  return (
    <Card>
      <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <CardTitle className="text-base">Presupuesto deducible</CardTitle>
        <div className="flex flex-wrap items-center gap-2">
          <Select
            value={String(month)}
            onValueChange={(v) => setMonth(Number(v))}
          >
            <SelectTrigger className="w-36" size="sm">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {MONTHS.map((m, i) => (
                <SelectItem key={m} value={String(i + 1)}>
                  {m}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <Select value={String(year)} onValueChange={(v) => setYear(Number(v))}>
            <SelectTrigger className="w-24" size="sm">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {years.map((y) => (
                <SelectItem key={y} value={String(y)}>
                  {y}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <Dialog open={configOpen} onOpenChange={setConfigOpen}>
            <Button variant="outline" size="sm" onClick={openConfig}>
              Configurar presupuesto
            </Button>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Configurar presupuesto mensual</DialogTitle>
                <DialogDescription>
                  Define un monto mensual por categoría de gasto deducible.
                </DialogDescription>
              </DialogHeader>
              <div className="grid gap-3 sm:grid-cols-2">
                {CATEGORIES.map((c) => (
                  <div key={c.value} className="space-y-2">
                    <Label htmlFor={`budget-${c.value}`}>{c.label}</Label>
                    <Input
                      id={`budget-${c.value}`}
                      type="number"
                      step="0.01"
                      min="0"
                      placeholder="0.00"
                      value={draft[c.value] ?? ""}
                      onChange={(e) =>
                        setDraft((d) => ({ ...d, [c.value]: e.target.value }))
                      }
                    />
                  </div>
                ))}
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={() => setConfigOpen(false)}>
                  Cancelar
                </Button>
                <Button disabled={save.isPending} onClick={handleSave}>
                  {save.isPending && (
                    <Loader2 className="size-4 animate-spin" />
                  )}
                  Guardar
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        </div>
      </CardHeader>
      <CardContent>
        {budgetQ.isLoading ? (
          <Loader2 className="size-5 animate-spin mx-auto my-8 text-muted-foreground" />
        ) : (
          <div className="space-y-3">
            {CATEGORIES.map((c) => (
              <BudgetRow
                key={c.value}
                label={c.label}
                spent={spent[c.value] ?? 0}
                budget={budgets[c.value] ?? 0}
              />
            ))}
            <div className="border-t pt-3">
              <BudgetRow
                label="Total"
                spent={totalSpent}
                budget={totalBudget}
                strong
              />
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
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

export default function PersonalExpensesPage() {
  const currentYear = new Date().getFullYear();
  const [year, setYear] = useState(currentYear);
  const [open, setOpen] = useState(false);
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(20);

  const expensesQ = usePersonalExpenses({
    fiscal_year: year,
    page,
    per_page: perPage,
  });
  const summaryQ = usePersonalExpenseSummary(year);
  const create = useCreatePersonalExpense();
  const del = useDeletePersonalExpense();

  const [form, setForm] = useState({
    fiscal_year: currentYear,
    category: "housing",
    description: "",
    issuer_ruc: "",
    issuer_name: "",
    document_number: "",
    issue_date: new Date().toISOString().slice(0, 10),
    amount: 0,
    notes: "",
  });

  const items = expensesQ.data?.data ?? [];
  const meta = expensesQ.data?.meta;

  return (
    <div>
      <PageHeader
        title="Gastos personales"
        description="Para deducción del impuesto a la renta"
        actions={
          <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
              <Button>
                <Plus className="size-4" />
                Nuevo gasto
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Registrar gasto deducible</DialogTitle>
                <DialogDescription>
                  Categoría según tabla SRI Form 107.
                </DialogDescription>
              </DialogHeader>
              <div className="grid gap-3 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label>Año fiscal</Label>
                  <Input
                    type="number"
                    min="2020"
                    value={form.fiscal_year}
                    onChange={(e) =>
                      setForm((f) => ({
                        ...f,
                        fiscal_year: Number(e.target.value),
                      }))
                    }
                  />
                </div>
                <div className="space-y-2">
                  <Label>Categoría</Label>
                  <Select
                    value={form.category}
                    onValueChange={(v) =>
                      setForm((f) => ({ ...f, category: v }))
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
                <div className="space-y-2 sm:col-span-2">
                  <Label>Descripción</Label>
                  <Input
                    value={form.description}
                    onChange={(e) =>
                      setForm((f) => ({ ...f, description: e.target.value }))
                    }
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
                  <Label>Monto</Label>
                  <Input
                    type="number"
                    step="0.01"
                    min="0"
                    value={form.amount}
                    onChange={(e) =>
                      setForm((f) => ({
                        ...f,
                        amount: Number(e.target.value) || 0,
                      }))
                    }
                  />
                </div>
                <div className="space-y-2">
                  <Label>RUC emisor (opcional)</Label>
                  <Input
                    value={form.issuer_ruc}
                    onChange={(e) =>
                      setForm((f) => ({ ...f, issuer_ruc: e.target.value }))
                    }
                    maxLength={13}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Nombre emisor</Label>
                  <Input
                    value={form.issuer_name}
                    onChange={(e) =>
                      setForm((f) => ({ ...f, issuer_name: e.target.value }))
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
                    !form.description ||
                    form.amount <= 0
                  }
                  onClick={() =>
                    create.mutate(form, {
                      onSuccess: () => {
                        toast.success("Gasto registrado");
                        setOpen(false);
                      },
                      onError: (e) => toast.error(errMessage(e)),
                    })
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
      <div className="p-4 lg:p-6 space-y-6">
        <DeductibleBudgetCard />

        <Card>
          <CardContent className="p-4 flex items-center gap-4">
            <Label htmlFor="year-filter" className="shrink-0">
              Año fiscal:
            </Label>
            <Input
              id="year-filter"
              type="number"
              min="2020"
              value={year}
              onChange={(e) => {
                setYear(Number(e.target.value));
                setPage(1);
              }}
              className="w-32"
            />
          </CardContent>
        </Card>

        {summaryQ.data && (
          <Card>
            <CardHeader>
              <CardTitle className="text-base">
                Resumen {summaryQ.data.fiscal_year}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex items-center justify-between mb-4">
                <div>
                  <p className="text-xs text-muted-foreground">Total deducible</p>
                  <p className="text-2xl font-semibold">
                    {formatMoney(summaryQ.data.total)}
                  </p>
                </div>
                <div className="text-right">
                  <p className="text-xs text-muted-foreground">Comprobantes</p>
                  <p className="text-2xl font-semibold">
                    {summaryQ.data.count}
                  </p>
                </div>
              </div>
              {summaryQ.data.by_category.length > 0 && (
                <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                  {summaryQ.data.by_category.map((c) => (
                    <div key={c.category} className="rounded border p-2">
                      <p className="text-xs text-muted-foreground">
                        {categoryLabel(c.category)}
                      </p>
                      <p className="font-mono text-sm">
                        {formatMoney(c.total)}
                      </p>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        )}

        <Card>
          <CardHeader>
            <CardTitle className="text-base">Gastos {year}</CardTitle>
          </CardHeader>
          <CardContent>
            {expensesQ.isLoading ? (
              <Loader2 className="size-5 animate-spin mx-auto my-12 text-muted-foreground" />
            ) : items.length === 0 ? (
              <p className="text-sm text-muted-foreground py-12 text-center">
                Sin gastos registrados para {year}.
              </p>
            ) : (
              <div className="space-y-4">
                <div className="relative">
                  {expensesQ.isFetching && (
                    <div className="absolute right-2 top-2 z-10">
                      <Loader2 className="size-4 animate-spin text-muted-foreground" />
                    </div>
                  )}
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Fecha</TableHead>
                    <TableHead>Categoría</TableHead>
                    <TableHead>Descripción</TableHead>
                    <TableHead>Emisor</TableHead>
                    <TableHead className="text-right">Monto</TableHead>
                    <TableHead className="w-[60px]"></TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {items.map((e) => (
                    <TableRow key={e.id}>
                      <TableCell>{formatDate(e.issue_date)}</TableCell>
                      <TableCell>
                        <Badge variant="secondary">
                          {categoryLabel(e.category)}
                        </Badge>
                      </TableCell>
                      <TableCell>{e.description}</TableCell>
                      <TableCell className="text-sm text-muted-foreground">
                        {e.issuer_name ?? "—"}
                      </TableCell>
                      <TableCell className="text-right font-medium">
                        {formatMoney(e.amount)}
                      </TableCell>
                      <TableCell>
                        <DeleteConfirmButton
                          onConfirm={() => del.mutateAsync(e.id)}
                          isPending={del.isPending}
                          title="¿Eliminar gasto?"
                          successMessage="Eliminado"
                          iconOnly
                        />
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
                  isFetching={expensesQ.isFetching}
                />
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
