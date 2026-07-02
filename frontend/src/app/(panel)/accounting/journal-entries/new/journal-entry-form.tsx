"use client";

import { useMemo, useState } from "react";
import { useRouter } from "next/navigation";
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
import { EntityCombobox } from "@/components/forms/entity-combobox";
import {
  useAccounts,
  useCostCenters,
  useCreateJournalEntry,
  type JournalEntryInput,
} from "@/lib/api/queries/accounting";
import { ClientApiError } from "@/lib/api/client";
import { formatMoney } from "@/lib/format";

type LineDraft = {
  account_id: number | null;
  cost_center_id: number | null;
  description: string;
  debit: number;
  credit: number;
};

function emptyLine(): LineDraft {
  return {
    account_id: null,
    cost_center_id: null,
    description: "",
    debit: 0,
    credit: 0,
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

export function JournalEntryForm() {
  const router = useRouter();
  const [date, setDate] = useState(() => new Date().toISOString().slice(0, 10));
  const [description, setDescription] = useState("");
  const [lines, setLines] = useState<LineDraft[]>([emptyLine(), emptyLine()]);
  const [accountSearch, setAccountSearch] = useState("");
  const accountsQ = useAccounts({ search: accountSearch || undefined, per_page: 50 });
  const costCentersQ = useCostCenters();
  const create = useCreateJournalEntry();

  const totals = useMemo(() => {
    let debit = 0;
    let credit = 0;
    for (const l of lines) {
      debit += Number(l.debit) || 0;
      credit += Number(l.credit) || 0;
    }
    return {
      debit: round(debit),
      credit: round(credit),
      diff: round(debit - credit),
    };
  }, [lines]);

  const updateLine = (idx: number, patch: Partial<LineDraft>) => {
    setLines((prev) =>
      prev.map((l, i) => (i === idx ? { ...l, ...patch } : l)),
    );
  };

  const onSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (lines.length < 2) {
      toast.error("Un asiento requiere al menos 2 líneas.");
      return;
    }
    if (Math.abs(totals.diff) > 0.001) {
      toast.error("El asiento no está cuadrado: debe = haber.");
      return;
    }
    if (lines.some((l) => !l.account_id)) {
      toast.error("Selecciona una cuenta en cada línea.");
      return;
    }
    if (lines.every((l) => l.debit === 0 && l.credit === 0)) {
      toast.error("Captura montos en al menos una línea.");
      return;
    }
    const payload: JournalEntryInput = {
      entry_date: date,
      description: description || undefined,
      lines: lines.map((l) => ({
        account_id: l.account_id as number,
        cost_center_id: l.cost_center_id ?? undefined,
        debit: Number(l.debit) || 0,
        credit: Number(l.credit) || 0,
        description: l.description || undefined,
      })),
    };
    create.mutate(payload, {
      onSuccess: (res) => {
        toast.success("Asiento creado");
        router.push(`/accounting/journal-entries/${res.data.journal_entry.id}`);
      },
      onError: (e) => toast.error(errMessage(e)),
    });
  };

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
      | { data: { cost_centers: Array<{ id: number; code: string; name: string }> } }
      | undefined;
    if (!d) return [];
    const inner = d.data as
      | Array<{ id: number; code: string; name: string }>
      | { cost_centers: Array<{ id: number; code: string; name: string }> };
    const arr = Array.isArray(inner) ? inner : inner.cost_centers ?? [];
    return arr.map((c) => ({
      value: c.id,
      label: `${c.code} · ${c.name}`,
    }));
  })();

  return (
    <form onSubmit={onSubmit} className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle className="text-base">Cabecera</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2">
          <div className="space-y-2">
            <Label htmlFor="date">Fecha</Label>
            <Input
              id="date"
              type="date"
              value={date}
              onChange={(e) => setDate(e.target.value)}
              required
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="desc">Concepto</Label>
            <Input
              id="desc"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="Descripción del asiento"
              maxLength={500}
            />
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle className="text-base">Líneas</CardTitle>
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={() => setLines((prev) => [...prev, emptyLine()])}
          >
            <Plus className="size-4" /> Agregar línea
          </Button>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className="w-[30%]">Cuenta</TableHead>
                <TableHead className="w-[20%]">Centro de costo</TableHead>
                <TableHead>Descripción</TableHead>
                <TableHead className="w-[120px] text-right">Debe</TableHead>
                <TableHead className="w-[120px] text-right">Haber</TableHead>
                <TableHead className="w-[40px]"></TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {lines.map((line, idx) => (
                <TableRow key={idx}>
                  <TableCell>
                    <EntityCombobox
                      value={line.account_id}
                      onChange={(v) =>
                        updateLine(idx, {
                          account_id: typeof v === "number" ? v : null,
                        })
                      }
                      options={accountOptions}
                      isLoading={accountsQ.isFetching}
                      onSearch={setAccountSearch}
                      placeholder="Buscar cuenta..."
                      searchPlaceholder="Código o nombre..."
                      buttonClassName="h-9 text-sm"
                    />
                  </TableCell>
                  <TableCell>
                    <EntityCombobox
                      value={line.cost_center_id}
                      onChange={(v) =>
                        updateLine(idx, {
                          cost_center_id: typeof v === "number" ? v : null,
                        })
                      }
                      options={costCenterOptions}
                      placeholder="—"
                      buttonClassName="h-9 text-sm"
                    />
                  </TableCell>
                  <TableCell>
                    <Input
                      value={line.description}
                      onChange={(e) =>
                        updateLine(idx, { description: e.target.value })
                      }
                    />
                  </TableCell>
                  <TableCell>
                    <Input
                      type="number"
                      step="0.01"
                      min="0"
                      value={line.debit}
                      onChange={(e) =>
                        updateLine(idx, {
                          debit: Number(e.target.value) || 0,
                          credit: Number(e.target.value) > 0 ? 0 : line.credit,
                        })
                      }
                      className="text-right"
                    />
                  </TableCell>
                  <TableCell>
                    <Input
                      type="number"
                      step="0.01"
                      min="0"
                      value={line.credit}
                      onChange={(e) =>
                        updateLine(idx, {
                          credit: Number(e.target.value) || 0,
                          debit: Number(e.target.value) > 0 ? 0 : line.debit,
                        })
                      }
                      className="text-right"
                    />
                  </TableCell>
                  <TableCell>
                    <Button
                      type="button"
                      size="icon"
                      variant="ghost"
                      onClick={() =>
                        setLines((prev) =>
                          prev.length > 2
                            ? prev.filter((_, i) => i !== idx)
                            : prev,
                        )
                      }
                      disabled={lines.length <= 2}
                    >
                      <Trash2 className="size-4" />
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
              <TableRow className="border-t-2">
                <TableCell colSpan={3} className="font-medium">
                  Totales
                </TableCell>
                <TableCell className="text-right font-mono font-semibold">
                  {formatMoney(totals.debit)}
                </TableCell>
                <TableCell className="text-right font-mono font-semibold">
                  {formatMoney(totals.credit)}
                </TableCell>
                <TableCell></TableCell>
              </TableRow>
              <TableRow>
                <TableCell colSpan={3} className="text-sm">
                  Diferencia
                </TableCell>
                <TableCell
                  colSpan={2}
                  className={`text-right font-mono ${
                    Math.abs(totals.diff) < 0.001
                      ? "text-emerald-600"
                      : "text-rose-600"
                  }`}
                >
                  {Math.abs(totals.diff) < 0.001
                    ? "✓ Cuadrado"
                    : formatMoney(totals.diff)}
                </TableCell>
                <TableCell></TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <div className="flex justify-end gap-2">
        <Button type="button" variant="outline" onClick={() => router.back()}>
          Cancelar
        </Button>
        <Button
          type="submit"
          disabled={create.isPending || Math.abs(totals.diff) > 0.001}
        >
          {create.isPending && <Loader2 className="size-4 animate-spin" />}
          Crear asiento
        </Button>
      </div>
    </form>
  );
}

function round(n: number) {
  return Math.round(n * 100) / 100;
}
