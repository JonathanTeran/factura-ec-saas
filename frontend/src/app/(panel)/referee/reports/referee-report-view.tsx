"use client";

import { useState } from "react";
import { Download, Loader2, Trophy, CalendarDays } from "lucide-react";
import { formatMoney } from "@/lib/format";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
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
import {
  useRefereeReport,
  downloadRefereeReportExcel,
} from "@/lib/api/queries/referee";

export function RefereeReportView() {
  const [year, setYear] = useState(() => new Date().getFullYear());
  const { data, isLoading } = useRefereeReport(year);

  const years = data?.available_years?.length
    ? data.available_years
    : [new Date().getFullYear()];
  const maxMonth = Math.max(
    1,
    ...(data?.by_month ?? []).map((m) => m.invoiced_amount + m.pending_amount),
  );

  return (
    <div className="space-y-6">
      {/* Controles */}
      <div className="flex flex-wrap items-center justify-between gap-3">
        <Select
          value={String(year)}
          onValueChange={(v) => setYear(Number(v))}
        >
          <SelectTrigger className="w-36">
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
        <Button
          variant="outline"
          onClick={() => downloadRefereeReportExcel(year)}
          disabled={!data || data.summary.total_matches === 0}
        >
          <Download className="size-4" />
          Exportar Excel
        </Button>
      </div>

      {isLoading || !data ? (
        <div className="flex justify-center py-16">
          <Loader2 className="size-6 animate-spin text-muted-foreground" />
        </div>
      ) : data.summary.total_matches === 0 ? (
        <Card>
          <CardContent className="py-16 text-center text-sm text-muted-foreground">
            No hay partidos registrados en {year}.
          </CardContent>
        </Card>
      ) : (
        <>
          {/* Resumen */}
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <SummaryCard
              label="Facturado"
              amount={data.summary.total_billed}
              hint={`${data.summary.invoiced.count} ${data.summary.invoiced.count === 1 ? "partido" : "partidos"}`}
              tone="success"
            />
            <SummaryCard
              label="Pendiente por facturar"
              amount={data.summary.total_pending}
              hint={`${data.summary.pending.count + data.summary.queued.count} ${data.summary.pending.count + data.summary.queued.count === 1 ? "partido" : "partidos"}`}
              tone="warning"
            />
            <SummaryCard
              label="Total del año"
              amount={data.summary.total_billed + data.summary.total_pending}
              hint={`${data.summary.total_matches} partidos pitados`}
              tone="muted"
            />
          </div>

          {/* Por mes */}
          <Card>
            <CardContent className="p-5 sm:p-6">
              <div className="mb-4 flex items-center gap-2 text-sm font-medium">
                <CalendarDays className="size-4 text-muted-foreground" />
                Por mes
              </div>
              <div className="space-y-3">
                {data.by_month.map((m) => {
                  const total = m.invoiced_amount + m.pending_amount;
                  return (
                    <div key={m.month} className="flex items-center gap-3">
                      <span className="w-10 shrink-0 text-xs capitalize text-muted-foreground">
                        {m.label.replace(".", "")}
                      </span>
                      <div className="flex h-5 flex-1 overflow-hidden rounded bg-muted">
                        <div
                          className="h-full bg-success/70"
                          style={{ width: `${(m.invoiced_amount / maxMonth) * 100}%` }}
                          title={`Facturado: ${formatMoney(m.invoiced_amount)}`}
                        />
                        <div
                          className="h-full bg-warning/60"
                          style={{ width: `${(m.pending_amount / maxMonth) * 100}%` }}
                          title={`Pendiente: ${formatMoney(m.pending_amount)}`}
                        />
                      </div>
                      <span className="w-20 shrink-0 text-right text-xs font-medium tabular-nums">
                        {formatMoney(total)}
                      </span>
                    </div>
                  );
                })}
              </div>
              <div className="mt-4 flex gap-4 text-xs text-muted-foreground">
                <span className="flex items-center gap-1.5">
                  <span className="size-2.5 rounded-sm bg-success/70" /> Facturado
                </span>
                <span className="flex items-center gap-1.5">
                  <span className="size-2.5 rounded-sm bg-warning/60" /> Pendiente
                </span>
              </div>
            </CardContent>
          </Card>

          {/* Por campeonato */}
          <Card>
            <CardContent className="p-5 sm:p-6">
              <div className="mb-3 flex items-center gap-2 text-sm font-medium">
                <Trophy className="size-4 text-muted-foreground" />
                Por campeonato
              </div>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Campeonato</TableHead>
                    <TableHead className="text-right">Partidos</TableHead>
                    <TableHead className="text-right">Facturado</TableHead>
                    <TableHead className="text-right">Pendiente</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data.by_championship.map((c) => (
                    <TableRow key={c.championship}>
                      <TableCell className="max-w-[280px] truncate">
                        {c.championship}
                      </TableCell>
                      <TableCell className="text-right tabular-nums">
                        {c.count}
                      </TableCell>
                      <TableCell className="text-right tabular-nums text-success">
                        {formatMoney(c.invoiced_amount)}
                      </TableCell>
                      <TableCell className="text-right tabular-nums text-warning">
                        {formatMoney(c.pending_amount)}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </>
      )}
    </div>
  );
}

function SummaryCard({
  label,
  amount,
  hint,
  tone,
}: {
  label: string;
  amount: number;
  hint: string;
  tone: "success" | "warning" | "muted";
}) {
  const amountClass =
    tone === "success"
      ? "text-success"
      : tone === "warning"
        ? "text-warning"
        : "text-foreground";
  return (
    <Card>
      <CardContent className="p-5">
        <p className="text-sm text-muted-foreground">{label}</p>
        <p className={`mt-1 text-2xl font-semibold tabular-nums ${amountClass}`}>
          {formatMoney(amount)}
        </p>
        <p className="mt-1 text-xs text-muted-foreground">{hint}</p>
      </CardContent>
    </Card>
  );
}
