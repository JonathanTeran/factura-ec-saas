"use client";

import { useState } from "react";
import { Loader2, Download } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
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
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from "@/components/ui/tabs";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import {
  useDocumentsByStatus,
  useSalesReport,
  useTaxReport,
  useTaxSummary,
  useTopCustomers,
  useTopProducts,
  downloadSalesExcel,
  type SalesReport,
  type TaxReport,
  type TaxSummary,
  type TopRow,
} from "@/lib/api/queries/reports";
import { formatMoney, formatNumber } from "@/lib/format";
import { documentStatusMeta } from "@/lib/status";

function defaultRange() {
  const to = new Date();
  const from = new Date();
  from.setDate(from.getDate() - 30);
  return {
    from: from.toISOString().slice(0, 10),
    to: to.toISOString().slice(0, 10),
  };
}

export function ReportsView() {
  const [range, setRange] = useState(() => defaultRange());
  const [groupBy, setGroupBy] = useState<"day" | "week" | "month">("day");

  const sales = useSalesReport(range, groupBy);
  const taxes = useTaxReport(range);
  const topCustomers = useTopCustomers(range, 10);
  const topProducts = useTopProducts(range, 10);
  const byStatus = useDocumentsByStatus(range);

  return (
    <div className="space-y-6">
      <Card>
        <CardContent className="p-4 grid gap-4 sm:grid-cols-3">
          <div className="space-y-2">
            <Label htmlFor="from">Desde</Label>
            <Input
              id="from"
              type="date"
              value={range.from}
              onChange={(e) =>
                setRange((r) => ({ ...r, from: e.target.value }))
              }
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="to">Hasta</Label>
            <Input
              id="to"
              type="date"
              value={range.to}
              onChange={(e) => setRange((r) => ({ ...r, to: e.target.value }))}
            />
          </div>
          <div className="space-y-2">
            <Label>Agrupación</Label>
            <Select
              value={groupBy}
              onValueChange={(v) =>
                setGroupBy(v as "day" | "week" | "month")
              }
            >
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="day">Día</SelectItem>
                <SelectItem value="week">Semana</SelectItem>
                <SelectItem value="month">Mes</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      <Tabs defaultValue="sales">
        <TabsList>
          <TabsTrigger value="sales">Ventas</TabsTrigger>
          <TabsTrigger value="taxes">IVA</TabsTrigger>
          <TabsTrigger value="declaracion">Declaración IVA</TabsTrigger>
          <TabsTrigger value="customers">Top clientes</TabsTrigger>
          <TabsTrigger value="products">Top productos</TabsTrigger>
          <TabsTrigger value="status">Por estado</TabsTrigger>
        </TabsList>

        <TabsContent value="sales" className="mt-4">
          <SalesPanel data={sales.data} isLoading={sales.isLoading} error={sales.error} />
        </TabsContent>

        <TabsContent value="taxes" className="mt-4">
          <TaxPanel data={taxes.data} isLoading={taxes.isLoading} error={taxes.error} />
        </TabsContent>

        <TabsContent value="declaracion" className="mt-4">
          <MonthlyTaxPanel />
        </TabsContent>

        <TabsContent value="customers" className="mt-4">
          <TopPanel
            title="Top 10 clientes por facturación"
            rows={topCustomers.data ?? []}
            isLoading={topCustomers.isLoading}
            error={topCustomers.error}
          />
        </TabsContent>

        <TabsContent value="products" className="mt-4">
          <TopPanel
            title="Top 10 productos por venta"
            rows={topProducts.data ?? []}
            isLoading={topProducts.isLoading}
            error={topProducts.error}
            note="Si no carga: bug del backend (columna document_items.total)."
          />
        </TabsContent>

        <TabsContent value="status" className="mt-4">
          <StatusPanel
            data={byStatus.data ?? {}}
            isLoading={byStatus.isLoading}
            error={byStatus.error}
          />
        </TabsContent>
      </Tabs>
    </div>
  );
}

const MONTHS = [
  "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
  "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre",
];

function MonthlyTaxPanel() {
  const now = new Date();
  const [year, setYear] = useState(now.getFullYear());
  const [month, setMonth] = useState(now.getMonth() + 1);
  const summary = useTaxSummary(year, month);
  const years = Array.from({ length: 6 }, (_, i) => now.getFullYear() - i);

  return (
    <div className="space-y-4">
      <Card>
        <CardContent className="p-4 flex flex-wrap items-end gap-4">
          <div className="space-y-2">
            <Label>Año</Label>
            <Select value={String(year)} onValueChange={(v) => setYear(Number(v))}>
              <SelectTrigger className="w-32">
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
          </div>
          <div className="space-y-2">
            <Label>Mes</Label>
            <Select value={String(month)} onValueChange={(v) => setMonth(Number(v))}>
              <SelectTrigger className="w-40">
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
          </div>
          <Button
            variant="outline"
            className="ml-auto"
            onClick={() => downloadSalesExcel(year, month)}
          >
            <Download className="size-4" />
            Exportar ventas (Excel)
          </Button>
        </CardContent>
      </Card>

      {summary.isLoading ? (
        <Loading />
      ) : summary.error ? (
        <Err error={summary.error} />
      ) : summary.data ? (
        <TaxSummaryCards data={summary.data} />
      ) : null}
    </div>
  );
}

function TaxSummaryCards({ data }: { data: TaxSummary }) {
  return (
    <div className="space-y-4">
      <div className="grid gap-3 sm:grid-cols-3">
        <Kpi
          label={`IVA en ventas (${data.ventas.count} fact.)`}
          value={formatMoney(data.ventas.iva)}
          primary
        />
        <Kpi
          label={`IVA notas de crédito (${data.notas_credito.count})`}
          value={`− ${formatMoney(data.notas_credito.iva)}`}
        />
        <Kpi
          label="IVA crédito compras (RUC)"
          value={`− ${formatMoney(data.iva_credito_compras)}`}
        />
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Compras del período</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Tipo de proveedor</TableHead>
                <TableHead className="text-right">Comprob.</TableHead>
                <TableHead className="text-right">Base</TableHead>
                <TableHead className="text-right">IVA</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow>
                <TableCell>
                  Con RUC{" "}
                  <span className="text-xs text-muted-foreground">
                    (genera crédito tributario)
                  </span>
                </TableCell>
                <TableCell className="text-right">{data.compras.con_ruc.count}</TableCell>
                <TableCell className="text-right">{formatMoney(data.compras.con_ruc.base)}</TableCell>
                <TableCell className="text-right font-medium">{formatMoney(data.compras.con_ruc.iva)}</TableCell>
              </TableRow>
              <TableRow>
                <TableCell>
                  Con cédula{" "}
                  <span className="text-xs text-muted-foreground">
                    (no deduce en declaración)
                  </span>
                </TableCell>
                <TableCell className="text-right">{data.compras.con_cedula.count}</TableCell>
                <TableCell className="text-right">{formatMoney(data.compras.con_cedula.base)}</TableCell>
                <TableCell className="text-right font-medium">{formatMoney(data.compras.con_cedula.iva)}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <Card className="border-primary/30">
        <CardContent className="p-4 flex items-center justify-between">
          <div>
            <p className="font-medium">IVA a pagar (estimado)</p>
            <p className="text-xs text-muted-foreground">
              IVA ventas − notas de crédito − crédito de compras con RUC. Referencial, no reemplaza el formulario del SRI.
            </p>
          </div>
          <span className="text-2xl font-semibold tabular-nums">
            {formatMoney(data.iva_a_pagar)}
          </span>
        </CardContent>
      </Card>
    </div>
  );
}

function SalesPanel({
  data,
  isLoading,
  error,
}: {
  data: SalesReport | undefined;
  isLoading: boolean;
  error: unknown;
}) {
  if (isLoading) return <Loading />;
  if (error) return <Err error={error} />;
  if (!data) return null;
  const max = Math.max(0, ...data.data.map((s) => s.total));
  return (
    <div className="space-y-4">
      <div className="grid gap-3 sm:grid-cols-4">
        <Kpi label="Documentos" value={formatNumber(data.totals.count)} />
        <Kpi label="Total facturado" value={formatMoney(data.totals.total)} primary />
        <Kpi label="IVA" value={formatMoney(data.totals.tax)} />
        <Kpi label="Promedio" value={formatMoney(data.totals.average)} />
      </div>
      <Card>
        <CardHeader>
          <CardTitle className="text-base">
            Serie · {data.from} → {data.to} (por {data.group_by})
          </CardTitle>
        </CardHeader>
        <CardContent>
          {data.data.length === 0 ? (
            <p className="text-sm text-muted-foreground py-6 text-center">
              Sin datos para el período.
            </p>
          ) : (
            <div className="space-y-2">
              {data.data.map((s) => (
                <div key={s.period} className="flex items-center gap-3">
                  <span className="w-24 text-xs text-muted-foreground">
                    {s.period}
                  </span>
                  <div className="flex-1 h-7 bg-muted rounded overflow-hidden">
                    <div
                      className="h-full bg-primary"
                      style={{
                        width: max > 0 ? `${(s.total / max) * 100}%` : "0%",
                      }}
                    />
                  </div>
                  <span className="w-28 text-right text-sm font-medium">
                    {formatMoney(s.total)}
                  </span>
                  <span className="w-12 text-right text-xs text-muted-foreground">
                    {s.count}
                  </span>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

function TaxPanel({
  data,
  isLoading,
  error,
}: {
  data: TaxReport | undefined;
  isLoading: boolean;
  error: unknown;
}) {
  if (isLoading) return <Loading />;
  if (error) return <Err error={error} />;
  if (!data) return null;
  const subtotals = data.subtotals ?? {};
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">IVA por alícuota</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
          {Object.entries(subtotals).map(([rate, amount]) => (
            <Kpi key={rate} label={rate} value={formatMoney(amount as number)} />
          ))}
        </div>
        <div className="border-t pt-3 grid gap-2 sm:grid-cols-2">
          <div className="flex justify-between items-center text-sm">
            <span className="text-muted-foreground">Total IVA</span>
            <span className="font-semibold">{formatMoney(data.total_tax)}</span>
          </div>
          <div className="flex justify-between items-center text-base">
            <span>Total facturado</span>
            <span className="font-semibold">{formatMoney(data.total)}</span>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}

function TopPanel({
  title,
  rows,
  isLoading,
  error,
  note,
}: {
  title: string;
  rows: TopRow[];
  isLoading: boolean;
  error: unknown;
  note?: string;
}) {
  if (isLoading) return <Loading />;
  if (error) return <Err error={error} note={note} />;
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">{title}</CardTitle>
      </CardHeader>
      <CardContent>
        {rows.length === 0 ? (
          <p className="text-sm text-muted-foreground py-6 text-center">
            Sin datos para el período.
          </p>
        ) : (
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>#</TableHead>
                <TableHead>Nombre</TableHead>
                <TableHead className="text-right">Documentos</TableHead>
                <TableHead className="text-right">Total</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {rows.map((r, i) => (
                <TableRow key={r.id}>
                  <TableCell className="text-muted-foreground">
                    {i + 1}
                  </TableCell>
                  <TableCell>{r.name}</TableCell>
                  <TableCell className="text-right">
                    {formatNumber(r.count ?? r.total_purchases ?? 0)}
                  </TableCell>
                  <TableCell className="text-right font-medium">
                    {formatMoney(
                      r.total ?? r.total_amount ?? r.total_revenue ?? 0,
                    )}
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </CardContent>
    </Card>
  );
}

function StatusPanel({
  data,
  isLoading,
  error,
}: {
  data: Record<string, number>;
  isLoading: boolean;
  error: unknown;
}) {
  if (isLoading) return <Loading />;
  if (error) return <Err error={error} />;
  const entries = Object.entries(data);
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Documentos por estado</CardTitle>
      </CardHeader>
      <CardContent>
        {entries.length === 0 ? (
          <p className="text-sm text-muted-foreground py-6 text-center">
            Sin datos.
          </p>
        ) : (
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Estado</TableHead>
                <TableHead className="text-right">Documentos</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {entries.map(([status, count]) => (
                <TableRow key={status}>
                  <TableCell>
                    <Badge
                      variant="outline"
                      className={documentStatusMeta(status).className}
                    >
                      {documentStatusMeta(status).label}
                    </Badge>
                  </TableCell>
                  <TableCell className="text-right font-medium">
                    {formatNumber(count)}
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </CardContent>
    </Card>
  );
}

function Kpi({
  label,
  value,
  primary = false,
}: {
  label: string;
  value: string;
  primary?: boolean;
}) {
  return (
    <Card>
      <CardContent className="p-4">
        <p className="text-xs text-muted-foreground">{label}</p>
        <p
          className={`text-xl font-semibold mt-1 ${
            primary ? "text-primary" : ""
          }`}
        >
          {value}
        </p>
      </CardContent>
    </Card>
  );
}

function Loading() {
  return (
    <div className="flex justify-center py-12">
      <Loader2 className="size-5 animate-spin text-muted-foreground" />
    </div>
  );
}

function Err({ error, note }: { error: unknown; note?: string }) {
  return (
    <div className="text-sm py-6 text-center space-y-2">
      <p className="text-destructive">
        Error: {(error as Error).message}
      </p>
      {note && <p className="text-xs text-muted-foreground">{note}</p>}
    </div>
  );
}
