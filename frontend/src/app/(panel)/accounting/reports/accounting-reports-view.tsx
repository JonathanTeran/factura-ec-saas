"use client";

import { useState } from "react";
import { Loader2 } from "lucide-react";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from "@/components/ui/tabs";
import {
  useAccounts,
  useBalanceSheet,
  useCashFlow,
  useGeneralLedger,
  useIncomeStatement,
  useTrialBalance,
} from "@/lib/api/queries/accounting";
import { EntityCombobox } from "@/components/forms/entity-combobox";
import { formatMoney } from "@/lib/format";

function defaultRange() {
  const now = new Date();
  const from = new Date(now.getFullYear(), now.getMonth(), 1);
  const to = new Date();
  return {
    from: from.toISOString().slice(0, 10),
    to: to.toISOString().slice(0, 10),
  };
}

export function AccountingReportsView() {
  const [range, setRange] = useState(() => defaultRange());

  return (
    <div className="space-y-6">
      <Card>
        <CardContent className="p-4 grid gap-4 sm:grid-cols-2">
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
        </CardContent>
      </Card>

      <Tabs defaultValue="trial">
        <TabsList>
          <TabsTrigger value="trial">Balance de comprobación</TabsTrigger>
          <TabsTrigger value="balance">Balance general</TabsTrigger>
          <TabsTrigger value="income">Estado de resultados</TabsTrigger>
          <TabsTrigger value="ledger">Mayor general</TabsTrigger>
          <TabsTrigger value="cash">Flujo de caja</TabsTrigger>
        </TabsList>

        <TabsContent value="trial" className="mt-4">
          <TrialBalanceTab range={range} />
        </TabsContent>

        <TabsContent value="balance" className="mt-4">
          <BalanceSheetTab asOf={range.to} />
        </TabsContent>

        <TabsContent value="income" className="mt-4">
          <IncomeStatementTab range={range} />
        </TabsContent>

        <TabsContent value="ledger" className="mt-4">
          <GeneralLedgerTab range={range} />
        </TabsContent>

        <TabsContent value="cash" className="mt-4">
          <CashFlowTab range={range} />
        </TabsContent>
      </Tabs>
    </div>
  );
}

function TrialBalanceTab({ range }: { range: { from: string; to: string } }) {
  const q = useTrialBalance(range);
  return <ReportPanel title="Balance de comprobación" {...q} />;
}

function BalanceSheetTab({ asOf }: { asOf: string }) {
  const q = useBalanceSheet(asOf);
  return <ReportPanel title={`Balance general al ${asOf}`} {...q} />;
}

function IncomeStatementTab({ range }: { range: { from: string; to: string } }) {
  const q = useIncomeStatement(range);
  return <ReportPanel title="Estado de resultados" {...q} />;
}

function CashFlowTab({ range }: { range: { from: string; to: string } }) {
  const q = useCashFlow(range);
  return <ReportPanel title="Flujo de caja" {...q} />;
}

function GeneralLedgerTab({ range }: { range: { from: string; to: string } }) {
  const [accountId, setAccountId] = useState<number | null>(null);
  const [search, setSearch] = useState("");
  const accountsQ = useAccounts({ search: search || undefined, per_page: 50 });
  const ledger = useGeneralLedger(range, accountId);
  return (
    <div className="space-y-4">
      <Card>
        <CardContent className="p-4">
          <div className="space-y-2">
            <Label>Cuenta</Label>
            <EntityCombobox
              value={accountId}
              onChange={(v) =>
                setAccountId(typeof v === "number" ? v : null)
              }
              options={
                accountsQ.data?.data
                  .filter((a) => a.allows_movement !== false)
                  .map((a) => ({
                    value: a.id,
                    label: `${a.code} · ${a.name}`,
                  })) ?? []
              }
              isLoading={accountsQ.isFetching}
              onSearch={setSearch}
              placeholder="Selecciona una cuenta..."
              searchPlaceholder="Buscar..."
            />
          </div>
        </CardContent>
      </Card>
      {accountId && (
        <ReportPanel title="Mayor general" {...ledger} />
      )}
    </div>
  );
}

function ReportPanel({
  title,
  data,
  isLoading,
  error,
}: {
  title: string;
  data: unknown;
  isLoading: boolean;
  error: unknown;
}) {
  if (isLoading) {
    return (
      <div className="flex justify-center py-12">
        <Loader2 className="size-5 animate-spin text-muted-foreground" />
      </div>
    );
  }
  if (error) {
    return (
      <div className="text-sm text-destructive py-6 text-center">
        Error: {(error as Error).message}
      </div>
    );
  }
  if (!data) {
    return (
      <div className="text-sm text-muted-foreground py-6 text-center">
        Sin datos.
      </div>
    );
  }
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">{title}</CardTitle>
      </CardHeader>
      <CardContent>
        <ReportRenderer data={data} />
      </CardContent>
    </Card>
  );
}

type ReportEntry = {
  account_code?: string;
  code?: string;
  account_name?: string;
  name?: string;
  debit?: number;
  credit?: number;
  balance?: number;
  total?: number;
  amount?: number;
};

function isReportEntryArray(v: unknown): v is ReportEntry[] {
  return Array.isArray(v) && v.every((x) => typeof x === "object");
}

function ReportRenderer({ data }: { data: unknown }) {
  if (typeof data !== "object" || data === null) {
    return <pre className="text-xs">{String(data)}</pre>;
  }
  const entries = Object.entries(data as Record<string, unknown>);
  return (
    <div className="space-y-4">
      {entries.map(([key, value]) => (
        <Section key={key} title={prettyKey(key)} value={value} />
      ))}
    </div>
  );
}

function Section({ title, value }: { title: string; value: unknown }) {
  if (typeof value === "number") {
    return (
      <div className="flex justify-between items-center text-sm">
        <span className="text-muted-foreground">{title}</span>
        <span className="font-medium font-mono">{formatMoney(value)}</span>
      </div>
    );
  }
  if (typeof value === "string") {
    return (
      <div className="flex justify-between items-center text-sm">
        <span className="text-muted-foreground">{title}</span>
        <span>{value}</span>
      </div>
    );
  }
  if (isReportEntryArray(value)) {
    return (
      <div>
        <h4 className="text-sm font-semibold mb-2">{title}</h4>
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b text-xs text-muted-foreground">
              <th className="text-left py-1">Cuenta</th>
              <th className="text-right py-1">Debe</th>
              <th className="text-right py-1">Haber</th>
              <th className="text-right py-1">Saldo</th>
            </tr>
          </thead>
          <tbody>
            {value.map((row, i) => (
              <tr key={i} className="border-b border-muted">
                <td className="py-1">
                  <span className="font-mono text-xs">{row.account_code ?? row.code ?? "—"}</span>
                  {" "}
                  {row.account_name ?? row.name ?? ""}
                </td>
                <td className="text-right font-mono">
                  {row.debit != null ? formatMoney(row.debit) : ""}
                </td>
                <td className="text-right font-mono">
                  {row.credit != null ? formatMoney(row.credit) : ""}
                </td>
                <td className="text-right font-mono">
                  {row.balance != null
                    ? formatMoney(row.balance)
                    : row.total != null
                      ? formatMoney(row.total)
                      : row.amount != null
                        ? formatMoney(row.amount)
                        : ""}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    );
  }
  if (Array.isArray(value)) {
    return (
      <div>
        <h4 className="text-sm font-semibold mb-2">{title}</h4>
        <pre className="text-xs bg-muted p-3 rounded overflow-auto max-h-60">
          {JSON.stringify(value, null, 2)}
        </pre>
      </div>
    );
  }
  if (typeof value === "object" && value !== null) {
    return (
      <div>
        <h4 className="text-sm font-semibold mb-2">{title}</h4>
        <div className="space-y-1 pl-4 border-l-2 border-muted">
          {Object.entries(value as Record<string, unknown>).map(([k, v]) => (
            <Section key={k} title={prettyKey(k)} value={v} />
          ))}
        </div>
      </div>
    );
  }
  return null;
}

function prettyKey(key: string) {
  return key
    .replace(/_/g, " ")
    .replace(/\b\w/g, (c) => c.toUpperCase());
}
