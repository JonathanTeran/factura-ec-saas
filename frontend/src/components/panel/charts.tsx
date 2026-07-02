"use client";

import {
  Area,
  AreaChart,
  CartesianGrid,
  Cell,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import { formatMoney } from "@/lib/format";

/* -------------------------------------------------------------------------- */
/*  Facturación mensual — área con degradado                                   */
/* -------------------------------------------------------------------------- */

type MonthlyPoint = { month_name: string; total: number; count: number };

function shortMoney(v: number) {
  if (v >= 1000) return `$${(v / 1000).toFixed(v >= 10000 ? 0 : 1)}k`;
  return `$${v.toFixed(0)}`;
}

function RevenueTooltip({
  active,
  payload,
  label,
}: {
  active?: boolean;
  payload?: Array<{ payload: MonthlyPoint }>;
  label?: string;
}) {
  if (!active || !payload?.length) return null;
  const p = payload[0].payload;
  return (
    <div className="rounded-lg border border-border bg-popover px-3 py-2 shadow-lg">
      <p className="text-xs font-medium text-muted-foreground">{label}</p>
      <p className="mt-0.5 text-sm font-semibold tabular-nums">
        {formatMoney(p.total)}
      </p>
      <p className="text-xs text-muted-foreground">
        {p.count} {p.count === 1 ? "documento" : "documentos"}
      </p>
    </div>
  );
}

export function RevenueAreaChart({ data }: { data: MonthlyPoint[] }) {
  return (
    <ResponsiveContainer width="100%" height={260}>
      <AreaChart data={data} margin={{ top: 8, right: 8, left: -12, bottom: 0 }}>
        <defs>
          <linearGradient id="revFill" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stopColor="var(--primary)" stopOpacity={0.28} />
            <stop offset="100%" stopColor="var(--primary)" stopOpacity={0} />
          </linearGradient>
        </defs>
        <CartesianGrid
          strokeDasharray="3 3"
          vertical={false}
          stroke="var(--border)"
        />
        <XAxis
          dataKey="month_name"
          tickLine={false}
          axisLine={false}
          tickMargin={10}
          tick={{ fill: "var(--muted-foreground)", fontSize: 11 }}
        />
        <YAxis
          tickLine={false}
          axisLine={false}
          width={52}
          tick={{ fill: "var(--muted-foreground)", fontSize: 11 }}
          tickFormatter={shortMoney}
        />
        <Tooltip
          content={<RevenueTooltip />}
          cursor={{ stroke: "var(--primary)", strokeOpacity: 0.25 }}
        />
        <Area
          type="monotone"
          dataKey="total"
          stroke="var(--primary)"
          strokeWidth={2.5}
          fill="url(#revFill)"
          dot={false}
          isAnimationActive={false}
          activeDot={{
            r: 4,
            fill: "var(--primary)",
            stroke: "var(--card)",
            strokeWidth: 2,
          }}
        />
      </AreaChart>
    </ResponsiveContainer>
  );
}

/* -------------------------------------------------------------------------- */
/*  Estado de documentos — donut                                               */
/* -------------------------------------------------------------------------- */

type StatusSlice = { name: string; value: number; color: string };

function DonutTooltip({
  active,
  payload,
}: {
  active?: boolean;
  payload?: Array<{ payload: StatusSlice }>;
}) {
  if (!active || !payload?.length) return null;
  const p = payload[0].payload;
  return (
    <div className="rounded-lg border border-border bg-popover px-3 py-1.5 shadow-lg">
      <p className="text-sm">
        <span className="font-semibold tabular-nums">{p.value}</span>{" "}
        <span className="text-muted-foreground">{p.name}</span>
      </p>
    </div>
  );
}

export function StatusDonutChart({
  authorized,
  pending,
  rejected,
}: {
  authorized: number;
  pending: number;
  rejected: number;
}) {
  const data: StatusSlice[] = [
    { name: "Autorizados", value: authorized, color: "var(--success)" },
    { name: "Pendientes", value: pending, color: "var(--warning)" },
    { name: "Rechazados", value: rejected, color: "var(--destructive)" },
  ];
  const total = authorized + pending + rejected;

  return (
    <div className="flex flex-col items-center">
      <div className="relative h-[180px] w-full">
        <ResponsiveContainer width="100%" height="100%">
          <PieChart>
            <Tooltip content={<DonutTooltip />} />
            <Pie
              data={total === 0 ? [{ name: "Sin datos", value: 1, color: "var(--muted)" }] : data}
              dataKey="value"
              nameKey="name"
              innerRadius={58}
              outerRadius={82}
              paddingAngle={total === 0 ? 0 : 3}
              strokeWidth={0}
              isAnimationActive={false}
            >
              {(total === 0
                ? [{ color: "var(--muted)" }]
                : data
              ).map((entry, i) => (
                <Cell key={i} fill={entry.color} />
              ))}
            </Pie>
          </PieChart>
        </ResponsiveContainer>
        <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
          <span className="text-2xl font-semibold tabular-nums">{total}</span>
          <span className="text-xs text-muted-foreground">documentos</span>
        </div>
      </div>
      <div className="mt-3 grid w-full grid-cols-3 gap-2">
        {data.map((s) => (
          <div key={s.name} className="flex flex-col items-center gap-1">
            <span className="flex items-center gap-1.5 text-xs text-muted-foreground">
              <span
                className="size-2 rounded-full"
                style={{ background: s.color }}
              />
              {s.name}
            </span>
            <span className="text-sm font-semibold tabular-nums">{s.value}</span>
          </div>
        ))}
      </div>
    </div>
  );
}
