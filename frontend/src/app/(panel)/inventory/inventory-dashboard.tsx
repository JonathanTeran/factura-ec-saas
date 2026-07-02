"use client";

import { useMemo, useState } from "react";
import Link from "next/link";
import {
  AlertTriangle,
  Loader2,
  Package,
  Search,
  TrendingDown,
  Wallet,
} from "lucide-react";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  useInventorySummary,
  useLowStockProducts,
} from "@/lib/api/queries/inventory";
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { formatDate, formatMoney, formatNumber } from "@/lib/format";

export function InventoryDashboard() {
  const summaryQ = useInventorySummary();
  const lowStockQ = useLowStockProducts();
  const [search, setSearch] = useState("");
  const debouncedSearch = useDebouncedValue(search);

  // Los movimientos vienen planos dentro del resumen: filtrado en el cliente.
  const allMovements = useMemo(
    () => summaryQ.data?.recent_movements ?? [],
    [summaryQ.data],
  );
  const recent = useMemo(() => {
    const term = debouncedSearch.trim().toLowerCase();
    if (!term) return allMovements;
    return allMovements.filter(
      (m) =>
        (m.product?.name ?? "").toLowerCase().includes(term) ||
        (m.movement_type_label ?? m.movement_type ?? "")
          .toLowerCase()
          .includes(term),
    );
  }, [allMovements, debouncedSearch]);

  if (summaryQ.isLoading) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (summaryQ.error) {
    return (
      <div className="text-sm text-destructive py-6">
        Error: {(summaryQ.error as Error).message}
      </div>
    );
  }

  const summary = summaryQ.data?.summary;
  const lowStock = lowStockQ.data ?? [];

  return (
    <div className="space-y-6">
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Kpi
          label="Productos con stock"
          value={summary ? formatNumber(summary.total_products_with_inventory) : "—"}
          icon={<Package className="size-5 text-muted-foreground" />}
        />
        <Kpi
          label="Stock bajo"
          value={summary ? formatNumber(summary.low_stock_count) : "—"}
          tone={
            (summary?.low_stock_count ?? 0) > 0 ? "warning" : "default"
          }
          icon={<TrendingDown className="size-5 text-amber-500" />}
        />
        <Kpi
          label="Sin stock"
          value={summary ? formatNumber(summary.out_of_stock_count) : "—"}
          tone={
            (summary?.out_of_stock_count ?? 0) > 0 ? "danger" : "default"
          }
          icon={<AlertTriangle className="size-5 text-rose-500" />}
        />
        <Kpi
          label="Valor del inventario"
          value={summary ? formatMoney(summary.total_inventory_value) : "—"}
          icon={<Wallet className="size-5 text-muted-foreground" />}
        />
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Productos en alerta</CardTitle>
          </CardHeader>
          <CardContent>
            {lowStockQ.isLoading ? (
              <div className="py-6 flex justify-center">
                <Loader2 className="size-5 animate-spin text-muted-foreground" />
              </div>
            ) : lowStock.length === 0 ? (
              <p className="text-sm text-muted-foreground py-4 text-center">
                Ningún producto en stock bajo. ✓
              </p>
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Código</TableHead>
                    <TableHead>Nombre</TableHead>
                    <TableHead className="text-right">Stock</TableHead>
                    <TableHead className="text-right">Mín</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {lowStock.slice(0, 10).map((p) => {
                    const noStock = (p.stock ?? 0) <= 0;
                    return (
                      <TableRow key={p.id}>
                        <TableCell className="font-mono text-xs">
                          <Link href={`/products/${p.id}`} className="block py-1">
                            {p.code}
                          </Link>
                        </TableCell>
                        <TableCell>
                          <Link href={`/products/${p.id}`} className="block py-1">
                            {p.name}
                          </Link>
                        </TableCell>
                        <TableCell className="text-right">
                          <Badge
                            variant={noStock ? "destructive" : "secondary"}
                          >
                            {formatNumber(p.stock ?? 0)}
                          </Badge>
                        </TableCell>
                        <TableCell className="text-right text-muted-foreground">
                          {formatNumber(p.min_stock ?? 0)}
                        </TableCell>
                      </TableRow>
                    );
                  })}
                </TableBody>
              </Table>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="text-base">Movimientos recientes</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
              <Input
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="Filtrar por producto o tipo..."
                className="pl-9"
              />
            </div>
            {recent.length === 0 ? (
              <p className="text-sm text-muted-foreground py-4 text-center">
                {debouncedSearch.trim()
                  ? "Sin movimientos para el filtro."
                  : "Sin movimientos registrados."}
              </p>
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Fecha</TableHead>
                    <TableHead>Producto</TableHead>
                    <TableHead>Tipo</TableHead>
                    <TableHead className="text-right">Cant.</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {recent.map((m) => (
                    <TableRow key={m.id}>
                      <TableCell className="text-xs text-muted-foreground">
                        {formatDate(m.created_at)}
                      </TableCell>
                      <TableCell className="text-sm">
                        {m.product?.name ?? "—"}
                      </TableCell>
                      <TableCell>
                        <Badge variant="outline" className="capitalize">
                          {m.movement_type_label ?? m.movement_type}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right font-mono text-xs">
                        <span
                          className={
                            m.is_outgoing
                              ? "text-rose-600"
                              : m.is_incoming
                                ? "text-emerald-600"
                                : ""
                          }
                        >
                          {formatNumber(
                            m.absolute_quantity ?? Math.abs(m.quantity),
                          )}
                        </span>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

function Kpi({
  label,
  value,
  icon,
  tone = "default",
}: {
  label: string;
  value: string;
  icon: React.ReactNode;
  tone?: "default" | "warning" | "danger";
}) {
  const valueClass =
    tone === "danger"
      ? "text-rose-600 dark:text-rose-400"
      : tone === "warning"
        ? "text-amber-600 dark:text-amber-400"
        : "";
  return (
    <Card>
      <CardHeader className="flex-row items-center justify-between pb-2">
        <CardTitle className="text-sm font-medium text-muted-foreground">
          {label}
        </CardTitle>
        {icon}
      </CardHeader>
      <CardContent>
        <div className={`text-2xl font-semibold ${valueClass}`}>{value}</div>
      </CardContent>
    </Card>
  );
}
