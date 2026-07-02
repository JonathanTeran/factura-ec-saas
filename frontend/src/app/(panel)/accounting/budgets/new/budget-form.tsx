"use client";

import { useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { useFieldArray, useForm, useWatch } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { Loader2, Plus, Trash2 } from "lucide-react";
import { toast } from "sonner";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
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
import { EntityCombobox } from "@/components/forms/entity-combobox";
import {
  useAccounts,
  useCostCenters,
  useCreateBudget,
  type BudgetInput,
} from "@/lib/api/queries/accounting";
import { accountingKeys } from "@/lib/api/queries/accounting";
import { useQueryClient } from "@tanstack/react-query";
import { ClientApiError } from "@/lib/api/client";
import { formatMoney } from "@/lib/format";

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

const lineSchema = z.object({
  account_id: z
    .number({ message: "Selecciona una cuenta." })
    .int()
    .positive("Selecciona una cuenta."),
  cost_center_id: z.number().int().positive().nullable().optional(),
  month: z.number().int().min(1).max(12),
  budgeted_amount: z.coerce
    .number({ message: "Monto inválido." })
    .min(0, "El monto no puede ser negativo."),
});

const budgetSchema = z.object({
  name: z.string().min(1, "El nombre es obligatorio.").max(255),
  year: z.coerce
    .number({ message: "Año inválido." })
    .int()
    .min(2020, "El año debe ser 2020 o posterior."),
  notes: z.string().max(2000).optional(),
  lines: z.array(lineSchema).min(1, "Agrega al menos una línea."),
});

type BudgetFormValues = z.input<typeof budgetSchema>;

function emptyLine(): BudgetFormValues["lines"][number] {
  return {
    account_id: 0,
    cost_center_id: null,
    month: 1,
    budgeted_amount: 0,
  };
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

export function BudgetForm() {
  const router = useRouter();
  const qc = useQueryClient();
  const create = useCreateBudget();
  const [accountSearch, setAccountSearch] = useState("");
  const accountsQ = useAccounts({
    search: accountSearch || undefined,
    per_page: 50,
  });
  const costCentersQ = useCostCenters();

  const {
    register,
    control,
    handleSubmit,
    setValue,
    formState: { errors },
  } = useForm<BudgetFormValues>({
    resolver: zodResolver(budgetSchema),
    defaultValues: {
      name: "",
      year: new Date().getFullYear(),
      notes: "",
      lines: [emptyLine()],
    },
  });

  const { fields, append, remove } = useFieldArray({
    control,
    name: "lines",
  });

  const watchedLines = useWatch({ control, name: "lines" });

  const total = useMemo(() => {
    return (watchedLines ?? []).reduce(
      (sum, l) => sum + (Number(l?.budgeted_amount) || 0),
      0,
    );
  }, [watchedLines]);

  const accountOptions =
    accountsQ.data?.data
      .filter((a) => a.allows_movement !== false)
      .map((a) => ({
        value: a.id,
        label: `${a.code} · ${a.name}`,
        description: a.account_type,
      })) ?? [];

  const costCenterOptions = (() => {
    const d = costCentersQ.data as
      | { data: Array<{ id: number; code: string; name: string }> }
      | {
          data: {
            cost_centers: Array<{ id: number; code: string; name: string }>;
          };
        }
      | undefined;
    if (!d) return [];
    const inner = d.data as
      | Array<{ id: number; code: string; name: string }>
      | { cost_centers: Array<{ id: number; code: string; name: string }> };
    const arr = Array.isArray(inner) ? inner : (inner.cost_centers ?? []);
    return arr.map((c) => ({
      value: c.id,
      label: `${c.code} · ${c.name}`,
    }));
  })();

  const onSubmit = handleSubmit((values) => {
    const payload: BudgetInput = {
      name: values.name,
      year: Number(values.year),
      notes: values.notes ? values.notes : undefined,
      lines: values.lines.map((l) => ({
        account_id: Number(l.account_id),
        cost_center_id: l.cost_center_id ?? undefined,
        month: Number(l.month),
        budgeted_amount: Number(l.budgeted_amount) || 0,
      })),
    };

    create.mutate(payload, {
      onSuccess: () => {
        qc.invalidateQueries({ queryKey: accountingKeys.budgets() });
        toast.success("Presupuesto creado");
        router.push("/accounting/budgets");
      },
      onError: (e) => toast.error(errMessage(e)),
    });
  });

  return (
    <form onSubmit={onSubmit} className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle className="text-base">Datos generales</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2">
          <div className="space-y-2">
            <Label htmlFor="name">Nombre</Label>
            <Input
              id="name"
              placeholder="Presupuesto anual"
              {...register("name")}
            />
            {errors.name && (
              <p className="text-xs text-destructive">{errors.name.message}</p>
            )}
          </div>
          <div className="space-y-2">
            <Label htmlFor="year">Año</Label>
            <Input
              id="year"
              type="number"
              min={2020}
              step={1}
              {...register("year")}
            />
            {errors.year && (
              <p className="text-xs text-destructive">{errors.year.message}</p>
            )}
          </div>
          <div className="space-y-2 sm:col-span-2">
            <Label htmlFor="notes">Notas</Label>
            <Input
              id="notes"
              placeholder="Notas u observaciones (opcional)"
              {...register("notes")}
            />
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle className="text-base">Líneas de presupuesto</CardTitle>
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={() => append(emptyLine())}
          >
            <Plus className="size-4" /> Agregar línea
          </Button>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className="w-[35%]">Cuenta</TableHead>
                <TableHead className="w-[25%]">Centro de costo</TableHead>
                <TableHead className="w-[140px]">Mes</TableHead>
                <TableHead className="w-[140px] text-right">Monto</TableHead>
                <TableHead className="w-[40px]"></TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {fields.map((field, idx) => (
                <TableRow key={field.id}>
                  <TableCell>
                    <EntityCombobox
                      value={watchedLines?.[idx]?.account_id || null}
                      onChange={(v) =>
                        setValue(
                          `lines.${idx}.account_id`,
                          typeof v === "number" ? v : 0,
                          { shouldValidate: true },
                        )
                      }
                      options={accountOptions}
                      isLoading={accountsQ.isFetching}
                      onSearch={setAccountSearch}
                      placeholder="Buscar cuenta..."
                      searchPlaceholder="Código o nombre..."
                      buttonClassName="h-9 text-sm"
                    />
                    {errors.lines?.[idx]?.account_id && (
                      <p className="mt-1 text-xs text-destructive">
                        {errors.lines[idx]?.account_id?.message}
                      </p>
                    )}
                  </TableCell>
                  <TableCell>
                    <EntityCombobox
                      value={watchedLines?.[idx]?.cost_center_id ?? null}
                      onChange={(v) =>
                        setValue(
                          `lines.${idx}.cost_center_id`,
                          typeof v === "number" ? v : null,
                        )
                      }
                      options={costCenterOptions}
                      placeholder="—"
                      buttonClassName="h-9 text-sm"
                    />
                  </TableCell>
                  <TableCell>
                    <Select
                      value={String(watchedLines?.[idx]?.month ?? 1)}
                      onValueChange={(v) =>
                        setValue(`lines.${idx}.month`, Number(v), {
                          shouldValidate: true,
                        })
                      }
                    >
                      <SelectTrigger className="h-9 text-sm">
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
                  </TableCell>
                  <TableCell>
                    <Input
                      type="number"
                      step="0.01"
                      min="0"
                      className="text-right"
                      {...register(`lines.${idx}.budgeted_amount`)}
                    />
                    {errors.lines?.[idx]?.budgeted_amount && (
                      <p className="mt-1 text-xs text-destructive">
                        {errors.lines[idx]?.budgeted_amount?.message}
                      </p>
                    )}
                  </TableCell>
                  <TableCell>
                    <Button
                      type="button"
                      size="icon"
                      variant="ghost"
                      onClick={() => remove(idx)}
                      disabled={fields.length <= 1}
                    >
                      <Trash2 className="size-4" />
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
              <TableRow className="border-t-2">
                <TableCell colSpan={3} className="font-medium">
                  Total presupuestado
                </TableCell>
                <TableCell className="text-right font-mono font-semibold">
                  {formatMoney(total)}
                </TableCell>
                <TableCell></TableCell>
              </TableRow>
            </TableBody>
          </Table>
          {errors.lines?.root && (
            <p className="mt-2 text-xs text-destructive">
              {errors.lines.root.message}
            </p>
          )}
        </CardContent>
      </Card>

      <div className="flex justify-end gap-2">
        <Button type="button" variant="outline" onClick={() => router.back()}>
          Cancelar
        </Button>
        <Button type="submit" disabled={create.isPending}>
          {create.isPending && <Loader2 className="size-4 animate-spin" />}
          Crear presupuesto
        </Button>
      </div>
    </form>
  );
}
