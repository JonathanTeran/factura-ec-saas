import Link from "next/link";
import {
  ArrowDownRight,
  ArrowUpRight,
  FileText,
  DollarSign,
  CheckCircle2,
  Clock,
  Plus,
  UserPlus,
  Package,
  FileSpreadsheet,
  Receipt,
  FileMinus,
  FilePlus2,
  Truck,
  Inbox,
  PencilLine,
  FileCheck2,
  FileBox,
  ShieldCheck,
  CalendarClock,
  CreditCard,
} from "lucide-react";
import { apiFetch } from "@/lib/server/api";
import type { ApiSuccess, DashboardStats, Document } from "@/lib/api/types";
import type { SignatureStatus } from "@/lib/api/queries/onboarding";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { PageHeader } from "@/components/panel/page-header";
import { EmptyState } from "@/components/panel/empty-state";
import { RevenueAreaChart, StatusDonutChart } from "@/components/panel/charts";
import { documentStatusMeta } from "@/lib/status";
import { formatDate, formatMoney, deltaPct } from "@/lib/format";

export const metadata = { title: "Dashboard" };

type MonthlySummary = {
  year: number;
  monthly: { month: number; month_name: string; count: number; total: number }[];
};

async function getStats() {
  const res = await apiFetch<ApiSuccess<DashboardStats>>(
    "/api/v1/dashboard/stats",
  );
  return res.data;
}

async function getRecent() {
  const res = await apiFetch<ApiSuccess<{ documents: Document[] }>>(
    "/api/v1/dashboard/recent-documents",
  );
  return res.data.documents;
}

async function getMonthly() {
  try {
    const res = await apiFetch<ApiSuccess<MonthlySummary>>(
      "/api/v1/dashboard/monthly-summary",
    );
    return res.data.monthly;
  } catch {
    return [];
  }
}

type CurrentSubscription = {
  status_label?: string;
  ends_at?: string | null;
  plan?: { name?: string } | null;
} | null;

async function getSubscription(): Promise<CurrentSubscription> {
  try {
    const res = await apiFetch<
      ApiSuccess<{ subscription: CurrentSubscription }>
    >("/api/v1/subscription/current");
    return res.data.subscription;
  } catch {
    return null;
  }
}

async function getSignature(): Promise<SignatureStatus | null> {
  try {
    const res = await apiFetch<ApiSuccess<SignatureStatus>>(
      "/api/v1/signature-status",
    );
    return res.data;
  } catch {
    return null;
  }
}

type Readiness = {
  ready: boolean;
  checklist: {
    basic_data: boolean;
    sri_password: boolean;
    digital_signature: boolean;
    establishments: boolean;
  };
  signature_days_remaining: number;
  signature_expiring_soon: boolean;
  sri_environment: string;
  ruc_active: boolean | null;
};

async function getReadiness(): Promise<Readiness | null> {
  try {
    const res = await apiFetch<ApiSuccess<Readiness>>(
      "/api/v1/dashboard/readiness",
    );
    return res.data;
  } catch {
    return null;
  }
}

const CHECKLIST_META: Record<
  keyof Readiness["checklist"],
  { label: string; href: string }
> = {
  basic_data: { label: "Completa los datos de tu empresa", href: "/settings" },
  sri_password: { label: "Registra tu clave del SRI", href: "/settings" },
  digital_signature: {
    label: "Sube tu firma electrónica",
    href: "/settings/firma",
  },
  establishments: {
    label: "Configura establecimientos y puntos de emisión",
    href: "/settings/establishments",
  },
};

const QUICK_ACTIONS = [
  { label: "Nueva factura", href: "/documents/new", icon: Plus },
  { label: "Nuevo cliente", href: "/customers/new", icon: UserPlus },
  { label: "Nuevo producto", href: "/products/new", icon: Package },
  { label: "Nueva cotización", href: "/quotes/new", icon: FileSpreadsheet },
];

export default async function DashboardPage() {
  const [stats, recent, monthly, subscription, signature, readiness] =
    await Promise.all([
      getStats(),
      getRecent(),
      getMonthly(),
      getSubscription(),
      getSignature(),
      getReadiness(),
    ]);

  const docsDelta = deltaPct(
    stats.current_month.documents_count,
    stats.last_month.documents_count,
  );
  const totalDelta = deltaPct(
    stats.current_month.documents_total,
    stats.last_month.documents_total,
  );

  const MONTHS_ES = [
    "Ene", "Feb", "Mar", "Abr", "May", "Jun",
    "Jul", "Ago", "Sep", "Oct", "Nov", "Dic",
  ];
  const chartMonths =
    monthly.length > 0
      ? monthly.map((m) => ({
          month_name: MONTHS_ES[m.month - 1] ?? m.month_name,
          total: m.total,
          count: m.count,
        }))
      : MONTHS_ES.map((month_name) => ({ month_name, total: 0, count: 0 }));

  return (
    <div className="pb-10">
      <PageHeader
        title="Dashboard"
        description="Resumen de tu facturación este mes"
        actions={
          <Link
            href="/documents/new"
            className="flex h-9 items-center gap-2 rounded-lg bg-primary px-3.5 text-sm font-medium text-primary-foreground shadow-sm shadow-primary/25 transition hover:brightness-105 active:scale-[0.99]"
          >
            <Plus className="size-4" />
            Nueva factura
          </Link>
        }
      />

      <div className="space-y-6 px-4 pt-4 lg:px-6">
        {readiness && <ReadinessBanner readiness={readiness} />}

        {/* KPIs */}
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <StatCard
            label="Facturado este mes"
            value={formatMoney(stats.current_month.documents_total)}
            delta={totalDelta}
            icon={DollarSign}
            tone="blue"
          />
          <StatCard
            label="Documentos emitidos"
            value={stats.current_month.documents_count}
            delta={docsDelta}
            icon={FileText}
            tone="sky"
          />
          <StatCard
            label="Autorizados"
            value={stats.by_status.authorized}
            icon={CheckCircle2}
            tone="green"
          />
          <StatCard
            label="Pendientes / Rechazados"
            value={`${stats.by_status.pending} / ${stats.by_status.rejected}`}
            icon={Clock}
            tone="amber"
          />
        </div>

        {/* Charts */}
        <div className="grid gap-4 lg:grid-cols-3">
          <Card className="lg:col-span-2">
            <CardHeader className="flex-row items-center justify-between">
              <div>
                <CardTitle>Facturación por mes</CardTitle>
                <p className="mt-1 text-sm text-muted-foreground">
                  Año {new Date().getFullYear()}
                </p>
              </div>
            </CardHeader>
            <CardContent>
              <RevenueAreaChart data={chartMonths} />
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Estado de documentos</CardTitle>
              <p className="mt-1 text-sm text-muted-foreground">Este mes</p>
            </CardHeader>
            <CardContent>
              <StatusDonutChart
                authorized={stats.by_status.authorized}
                pending={stats.by_status.pending}
                rejected={stats.by_status.rejected}
              />
            </CardContent>
          </Card>
        </div>

        {/* Tus comprobantes (mosaico por tipo) */}
        {stats.by_type && (
          <Card>
            <CardHeader>
              <CardTitle>Tus comprobantes</CardTitle>
            </CardHeader>
            <CardContent className="grid grid-cols-2 gap-2 sm:grid-cols-4">
              <TypeTile
                label="Facturas"
                count={stats.by_type.facturas}
                href="/documents"
                icon={FileText}
                tint="bg-primary/8 text-primary"
              />
              <TypeTile
                label="Recibidos"
                count={stats.by_type.recibidos}
                href="/received-documents"
                icon={Inbox}
                tint="bg-chart-2/10 text-chart-2"
              />
              <TypeTile
                label="Borradores"
                count={stats.by_type.borradores}
                href="/documents"
                icon={PencilLine}
                tint="bg-warning/10 text-warning"
              />
              <TypeTile
                label="Notas de crédito"
                count={stats.by_type.notas_credito}
                href="/documents"
                icon={FileMinus}
                tint="bg-chart-4/15 text-chart-4"
              />
              <TypeTile
                label="Notas de débito"
                count={stats.by_type.notas_debito}
                href="/documents"
                icon={FilePlus2}
                tint="bg-chart-5/10 text-chart-5"
              />
              <TypeTile
                label="Retenciones"
                count={stats.by_type.retenciones}
                href="/documents"
                icon={FileCheck2}
                tint="bg-success/10 text-success"
              />
              <TypeTile
                label="Guías de remisión"
                count={stats.by_type.guias}
                href="/guides"
                icon={Truck}
                tint="bg-chart-3/10 text-chart-3"
              />
              <TypeTile
                label="Liquidaciones"
                count={stats.by_type.liquidaciones}
                href="/documents"
                icon={FileBox}
                tint="bg-muted text-muted-foreground"
              />
            </CardContent>
          </Card>
        )}

        {/* Cuenta + quick actions */}
        <div className="grid gap-4 lg:grid-cols-3">
          <Card>
            <CardHeader className="flex-row items-center justify-between">
              <CardTitle>Tu cuenta</CardTitle>
              {subscription?.status_label && (
                <Badge
                  variant="outline"
                  className="border-transparent bg-success/10 text-success"
                >
                  {subscription.status_label}
                </Badge>
              )}
            </CardHeader>
            <CardContent className="space-y-3.5">
              <div className="flex items-center gap-2.5 text-sm">
                <CreditCard className="size-4 shrink-0 text-muted-foreground" />
                <span className="text-muted-foreground">Plan</span>
                <span className="ml-auto font-medium">
                  {subscription?.plan?.name ?? "—"}
                </span>
              </div>
              {subscription?.ends_at && (
                <div className="flex items-center gap-2.5 text-sm">
                  <CalendarClock className="size-4 shrink-0 text-muted-foreground" />
                  <span className="text-muted-foreground">Vence</span>
                  <span className="ml-auto font-medium">
                    {formatDate(subscription.ends_at)}
                  </span>
                </div>
              )}
              {signature && (
                <div className="flex items-center gap-2.5 text-sm">
                  <ShieldCheck className="size-4 shrink-0 text-muted-foreground" />
                  <span className="text-muted-foreground">Firma</span>
                  <span
                    className={`ml-auto font-medium ${
                      signature.status === "expired"
                        ? "text-destructive"
                        : signature.status === "expiring_soon"
                          ? "text-warning"
                          : signature.status === "valid"
                            ? "text-success"
                            : "text-muted-foreground"
                    }`}
                  >
                    {signature.status === "missing"
                      ? "Sin configurar"
                      : signature.status === "expired"
                        ? "Vencida"
                        : signature.expires_at
                          ? `Vence ${formatDate(signature.expires_at)}`
                          : "—"}
                  </span>
                </div>
              )}
              <div className="border-t border-border pt-3">
                <div className="flex items-baseline justify-between">
                  <span className="text-sm text-muted-foreground">
                    Documentos este mes
                  </span>
                  <span className="text-sm font-semibold tabular-nums">
                    {stats.plan_usage.documents_used}
                    <span className="text-muted-foreground">
                      {" / "}
                      {stats.plan_usage.documents_limit === -1
                        ? "∞"
                        : stats.plan_usage.documents_limit}
                    </span>
                  </span>
                </div>
                <div className="mt-2 h-2 w-full overflow-hidden rounded-full bg-muted">
                  <div
                    className="h-full rounded-full bg-primary transition-all"
                    style={{
                      width:
                        stats.plan_usage.documents_limit > 0
                          ? `${Math.min(100, stats.plan_usage.percentage)}%`
                          : "8%",
                    }}
                  />
                </div>
                <p className="mt-1.5 text-xs text-muted-foreground">
                  {stats.plan_usage.documents_limit === -1
                    ? "Tu plan incluye documentos ilimitados."
                    : `${(100 - stats.plan_usage.percentage).toFixed(0)}% disponible este mes.`}
                </p>
              </div>
            </CardContent>
          </Card>

          <Card className="lg:col-span-2">
            <CardHeader>
              <CardTitle>Accesos rápidos</CardTitle>
            </CardHeader>
            <CardContent className="grid grid-cols-2 gap-2 sm:grid-cols-4">
              {QUICK_ACTIONS.map(({ label, href, icon: Icon }) => (
                <Link
                  key={href}
                  href={href}
                  className="flex flex-col items-start gap-2 rounded-lg border border-border bg-card p-3 text-sm transition hover:border-primary/40 hover:bg-accent hover:shadow-sm"
                >
                  <span className="grid size-9 place-items-center rounded-lg bg-accent text-accent-foreground">
                    <Icon className="size-4.5" />
                  </span>
                  <span className="font-medium leading-tight">{label}</span>
                </Link>
              ))}
            </CardContent>
          </Card>
        </div>

        {/* Recent documents */}
        <Card>
          <CardHeader className="flex-row items-center justify-between">
            <CardTitle>Documentos recientes</CardTitle>
            <Link
              href="/documents"
              className="text-sm font-medium text-primary hover:underline"
            >
              Ver todos
            </Link>
          </CardHeader>
          <CardContent className="p-0">
            {recent.length === 0 ? (
              <EmptyState
                icon={Receipt}
                title="Aún no has emitido documentos"
                description="Cuando emitas tu primera factura aparecerá aquí el historial reciente."
                action={
                  <Link
                    href="/documents/new"
                    className="flex h-9 items-center gap-2 rounded-lg bg-primary px-3.5 text-sm font-medium text-primary-foreground shadow-sm shadow-primary/25 transition hover:brightness-105"
                  >
                    <Plus className="size-4" />
                    Emitir factura
                  </Link>
                }
              />
            ) : (
              <div className="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow className="hover:bg-transparent">
                      <TableHead>Fecha</TableHead>
                      <TableHead>Número</TableHead>
                      <TableHead>Cliente</TableHead>
                      <TableHead>Estado</TableHead>
                      <TableHead className="text-right">Total</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {recent.map((doc) => {
                      const meta = documentStatusMeta(doc.status);
                      return (
                        <TableRow key={doc.id}>
                          <TableCell className="text-muted-foreground">
                            {formatDate(doc.issue_date ?? doc.date)}
                          </TableCell>
                          <TableCell className="font-mono text-xs">
                            {doc.document_number ?? doc.number ?? `#${doc.id}`}
                          </TableCell>
                          <TableCell className="font-medium">
                            {doc.customer?.name ?? doc.customer_name ?? "—"}
                          </TableCell>
                          <TableCell>
                            <Badge
                              variant="outline"
                              className={meta.className}
                            >
                              {meta.label}
                            </Badge>
                          </TableCell>
                          <TableCell className="text-right font-medium tabular-nums">
                            {formatMoney(doc.total)}
                          </TableCell>
                        </TableRow>
                      );
                    })}
                  </TableBody>
                </Table>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

/* -------------------------------------------------------------------------- */

function ReadinessBanner({ readiness }: { readiness: Readiness }) {
  const pendingItems = (
    Object.keys(CHECKLIST_META) as Array<keyof Readiness["checklist"]>
  ).filter((key) => !readiness.checklist[key]);

  const rucInactive = readiness.ruc_active === false;
  const signatureWarning =
    readiness.checklist.digital_signature && readiness.signature_expiring_soon;
  const testingEnv = readiness.sri_environment !== "2";

  const needsAttention =
    pendingItems.length > 0 || rucInactive || signatureWarning;

  if (!needsAttention) {
    if (!testingEnv) return null;
    return (
      <div className="flex items-center gap-3 rounded-xl border border-warning/30 bg-warning/5 px-4 py-3 text-sm">
        <Clock className="size-4 shrink-0 text-warning" />
        <span>
          Estás emitiendo en <strong>ambiente de pruebas</strong> — tus
          comprobantes no tienen validez tributaria.
        </span>
        <Link
          href="/settings"
          className="ml-auto shrink-0 font-medium text-primary hover:underline"
        >
          Pasar a producción
        </Link>
      </div>
    );
  }

  return (
    <Card className="border-warning/40 bg-warning/5">
      <CardContent className="p-4">
        <div className="flex items-center gap-2 text-sm font-semibold">
          <ShieldCheck className="size-4.5 text-warning" />
          Te falta poco para estar listo para facturar
        </div>
        <ul className="mt-3 space-y-1.5 text-sm">
          {pendingItems.map((key) => (
            <li key={key} className="flex items-center gap-2">
              <span className="size-1.5 shrink-0 rounded-full bg-warning" />
              <Link
                href={CHECKLIST_META[key].href}
                className="text-foreground hover:text-primary hover:underline"
              >
                {CHECKLIST_META[key].label}
              </Link>
            </li>
          ))}
          {signatureWarning && (
            <li className="flex items-center gap-2">
              <span className="size-1.5 shrink-0 rounded-full bg-destructive" />
              <Link
                href="/settings/firma"
                className="text-foreground hover:text-primary hover:underline"
              >
                Tu firma electrónica vence en{" "}
                {readiness.signature_days_remaining} día(s) — renuévala
              </Link>
            </li>
          )}
          {rucInactive && (
            <li className="flex items-center gap-2">
              <span className="size-1.5 shrink-0 rounded-full bg-destructive" />
              <span>
                El SRI reporta tu RUC como <strong>inactivo</strong> — regulariza
                tu estado antes de emitir.
              </span>
            </li>
          )}
        </ul>
      </CardContent>
    </Card>
  );
}

const TONES: Record<string, string> = {
  blue: "bg-primary/10 text-primary",
  sky: "bg-chart-2/10 text-chart-2",
  green: "bg-success/10 text-success",
  amber: "bg-warning/10 text-warning",
};

function TypeTile({
  label,
  count,
  href,
  icon: Icon,
  tint,
}: {
  label: string;
  count: number;
  href: string;
  icon: typeof FileText;
  tint: string;
}) {
  return (
    <Link
      href={href}
      className="flex items-center gap-3 rounded-xl border border-border bg-card p-3 transition hover:border-primary/40 hover:shadow-sm"
    >
      <span className={`grid size-10 shrink-0 place-items-center rounded-lg ${tint}`}>
        <Icon className="size-5" />
      </span>
      <span className="min-w-0">
        <span className="block text-lg font-semibold leading-none tabular-nums">
          {count}
        </span>
        <span className="mt-1 block truncate text-xs text-muted-foreground">
          {label}
        </span>
      </span>
    </Link>
  );
}

function StatCard({
  label,
  value,
  delta,
  icon: Icon,
  tone,
}: {
  label: string;
  value: number | string;
  delta?: number;
  icon: typeof FileText;
  tone: keyof typeof TONES | string;
}) {
  const positive = delta !== undefined ? delta >= 0 : null;
  return (
    <Card className="gap-0">
      <CardContent className="p-5">
        <div className="flex items-center justify-between">
          <span className="text-sm text-muted-foreground">{label}</span>
          <span
            className={`grid size-9 place-items-center rounded-lg ${TONES[tone] ?? TONES.blue}`}
          >
            <Icon className="size-4.5" />
          </span>
        </div>
        <div className="mt-3 text-[1.65rem] font-semibold leading-none tracking-tight tabular-nums">
          {value}
        </div>
        {delta !== undefined && (
          <div className="mt-2.5 flex items-center gap-1 text-xs">
            <span
              className={`inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 font-medium ${
                positive
                  ? "bg-success/10 text-success"
                  : "bg-destructive/10 text-destructive"
              }`}
            >
              {positive ? (
                <ArrowUpRight className="size-3" />
              ) : (
                <ArrowDownRight className="size-3" />
              )}
              {Math.abs(delta).toFixed(1)}%
            </span>
            <span className="text-muted-foreground">vs. mes anterior</span>
          </div>
        )}
      </CardContent>
    </Card>
  );
}

