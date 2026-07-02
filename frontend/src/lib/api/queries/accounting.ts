import {
  useMutation,
  useQuery,
  useQueryClient,
  keepPreviousData,
} from "@tanstack/react-query";
import { api, type ApiPaginated, type ApiSuccess } from "@/lib/api/client";
import type {
  AccountingAccount,
  Budget,
  CostCenter,
  FiscalPeriod,
  JournalEntry,
  TaxFormSubmission,
} from "@/lib/api/types";

const PREFIX = "accounting";

export const accountingKeys = {
  all: ["accounting"] as const,
  accounts: (q: Record<string, unknown> = {}) =>
    [...accountingKeys.all, "accounts", q] as const,
  account: (id: number) => [...accountingKeys.all, "accounts", id] as const,
  journalEntries: (q: Record<string, unknown> = {}) =>
    [...accountingKeys.all, "journal-entries", q] as const,
  journalEntry: (id: number) =>
    [...accountingKeys.all, "journal-entries", id] as const,
  fiscalPeriods: () => [...accountingKeys.all, "fiscal-periods"] as const,
  costCenters: () => [...accountingKeys.all, "cost-centers"] as const,
  budgets: () => [...accountingKeys.all, "budgets"] as const,
  taxForms: () => [...accountingKeys.all, "tax-forms"] as const,
  reports: (q: Record<string, unknown> = {}) =>
    [...accountingKeys.all, "reports", q] as const,
};

// ─── Accounts ───────────────────────────────────────────────

export type AccountInput = {
  code: string;
  name: string;
  account_type: AccountingAccount["account_type"];
  account_nature: AccountingAccount["account_nature"];
  parent_id?: number | null;
  allows_movement?: boolean;
  tax_form_code?: string;
  description?: string;
};

export function useAccounts(query: { search?: string; page?: number; per_page?: number } = {}) {
  return useQuery({
    queryKey: accountingKeys.accounts(query),
    queryFn: () =>
      api.get<ApiPaginated<AccountingAccount>>(`${PREFIX}/accounts`, { query }),
    placeholderData: keepPreviousData,
  });
}

export function useAccount(id: number | null) {
  return useQuery({
    queryKey: id ? accountingKeys.account(id) : ["accounting", "accounts", "none"],
    queryFn: () =>
      api.get<ApiSuccess<{ account: AccountingAccount } | AccountingAccount>>(
        `${PREFIX}/accounts/${id}`,
      ),
    enabled: !!id,
    select: (raw) => {
      const d = raw.data as AccountingAccount | { account: AccountingAccount };
      return "account" in d ? d.account : d;
    },
  });
}

export function useCreateAccount() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: AccountInput) =>
      api.post<ApiSuccess<unknown>>(`${PREFIX}/accounts`, input),
    onSuccess: () => qc.invalidateQueries({ queryKey: accountingKeys.all }),
  });
}

export function useUpdateAccount(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: AccountInput) =>
      api.put<ApiSuccess<unknown>>(`${PREFIX}/accounts/${id}`, input),
    onSuccess: () => qc.invalidateQueries({ queryKey: accountingKeys.all }),
  });
}

export function useDeleteAccount() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) =>
      api.delete<ApiSuccess<unknown>>(`${PREFIX}/accounts/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: accountingKeys.all }),
  });
}

// ─── Journal entries ─────────────────────────────────────────

export type JournalEntryInput = {
  entry_date: string;
  description?: string;
  lines: Array<{
    account_id: number;
    cost_center_id?: number | null;
    debit: number;
    credit: number;
    description?: string;
  }>;
};

export function useJournalEntries(
  query: {
    page?: number;
    per_page?: number;
    status?: string;
    search?: string;
    from?: string;
    to?: string;
  } = {},
) {
  return useQuery({
    queryKey: accountingKeys.journalEntries(query),
    queryFn: () =>
      api.get<ApiPaginated<JournalEntry>>(`${PREFIX}/journal-entries`, { query }),
    placeholderData: keepPreviousData,
  });
}

export function useJournalEntry(id: number | null) {
  return useQuery({
    queryKey: id ? accountingKeys.journalEntry(id) : ["accounting", "journal-entries", "none"],
    queryFn: () =>
      api.get<ApiSuccess<{ journal_entry: JournalEntry } | JournalEntry>>(
        `${PREFIX}/journal-entries/${id}`,
      ),
    enabled: !!id,
    select: (raw) => {
      const d = raw.data as JournalEntry | { journal_entry: JournalEntry };
      return "journal_entry" in d ? d.journal_entry : d;
    },
  });
}

export function useCreateJournalEntry() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: JournalEntryInput) =>
      api.post<ApiSuccess<{ journal_entry: JournalEntry }>>(
        `${PREFIX}/journal-entries`,
        input,
      ),
    onSuccess: () => qc.invalidateQueries({ queryKey: accountingKeys.all }),
  });
}

export function usePostJournalEntry() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) =>
      api.post<ApiSuccess<unknown>>(`${PREFIX}/journal-entries/${id}/post`),
    onSuccess: () => qc.invalidateQueries({ queryKey: accountingKeys.all }),
  });
}

export function useVoidJournalEntry() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: { id: number; reason: string }) =>
      api.post<ApiSuccess<unknown>>(
        `${PREFIX}/journal-entries/${input.id}/void`,
        { reason: input.reason },
      ),
    onSuccess: () => qc.invalidateQueries({ queryKey: accountingKeys.all }),
  });
}

export function useDeleteJournalEntry() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) =>
      api.delete<ApiSuccess<unknown>>(`${PREFIX}/journal-entries/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: accountingKeys.all }),
  });
}

// ─── Fiscal periods ─────────────────────────────────────────

export function useFiscalPeriods() {
  return useQuery({
    queryKey: accountingKeys.fiscalPeriods(),
    queryFn: () =>
      api.get<ApiSuccess<{ periods: FiscalPeriod[] } | FiscalPeriod[]>>(
        `${PREFIX}/fiscal-periods`,
      ),
    select: (raw) => {
      const d = raw.data as FiscalPeriod[] | { periods: FiscalPeriod[] };
      return Array.isArray(d) ? d : d.periods;
    },
  });
}

export function useCreateFiscalYear() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (year: number) =>
      api.post<ApiSuccess<unknown>>(`${PREFIX}/fiscal-periods/create-year`, {
        year,
      }),
    onSuccess: () => qc.invalidateQueries({ queryKey: accountingKeys.fiscalPeriods() }),
  });
}

export function useClosePeriod() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) =>
      api.post<ApiSuccess<unknown>>(`${PREFIX}/fiscal-periods/${id}/close`),
    onSuccess: () => qc.invalidateQueries({ queryKey: accountingKeys.fiscalPeriods() }),
  });
}

export function useLockPeriod() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) =>
      api.post<ApiSuccess<unknown>>(`${PREFIX}/fiscal-periods/${id}/lock`),
    onSuccess: () => qc.invalidateQueries({ queryKey: accountingKeys.fiscalPeriods() }),
  });
}

export function useReopenPeriod() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) =>
      api.post<ApiSuccess<unknown>>(`${PREFIX}/fiscal-periods/${id}/reopen`),
    onSuccess: () => qc.invalidateQueries({ queryKey: accountingKeys.fiscalPeriods() }),
  });
}

// ─── Cost centers ───────────────────────────────────────────

export type CostCenterInput = {
  code: string;
  name: string;
  description?: string;
  is_active?: boolean;
};

export function useCostCenters() {
  return useQuery({
    queryKey: accountingKeys.costCenters(),
    queryFn: () =>
      api.get<ApiPaginated<CostCenter> | ApiSuccess<{ cost_centers: CostCenter[] }>>(
        `${PREFIX}/cost-centers`,
      ),
  });
}

export function useCreateCostCenter() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: CostCenterInput) =>
      api.post<ApiSuccess<unknown>>(`${PREFIX}/cost-centers`, input),
    onSuccess: () => qc.invalidateQueries({ queryKey: accountingKeys.costCenters() }),
  });
}

export function useDeleteCostCenter() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) =>
      api.delete<ApiSuccess<unknown>>(`${PREFIX}/cost-centers/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: accountingKeys.costCenters() }),
  });
}

// ─── Budgets ────────────────────────────────────────────────

export type BudgetInput = {
  name: string;
  year: number;
  notes?: string;
  lines: Array<{
    account_id: number;
    cost_center_id?: number | null;
    month: number;
    budgeted_amount: number;
  }>;
};

export function useBudgets(query: { page?: number; per_page?: number } = {}) {
  return useQuery({
    queryKey: [...accountingKeys.budgets(), query],
    queryFn: () =>
      api.get<ApiPaginated<Budget> | ApiSuccess<{ budgets: Budget[] }>>(
        `${PREFIX}/budgets`,
        { query },
      ),
    placeholderData: keepPreviousData,
  });
}

export function useCreateBudget() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: BudgetInput) =>
      api.post<ApiSuccess<{ budget: Budget }>>(`${PREFIX}/budgets`, input),
    onSuccess: () => qc.invalidateQueries({ queryKey: accountingKeys.budgets() }),
  });
}

export function useApproveBudget() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) =>
      api.post<ApiSuccess<unknown>>(`${PREFIX}/budgets/${id}/approve`),
    onSuccess: () => qc.invalidateQueries({ queryKey: accountingKeys.budgets() }),
  });
}

export function useActivateBudget() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) =>
      api.post<ApiSuccess<unknown>>(`${PREFIX}/budgets/${id}/activate`),
    onSuccess: () => qc.invalidateQueries({ queryKey: accountingKeys.budgets() }),
  });
}

export function useCloseBudget() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) =>
      api.post<ApiSuccess<unknown>>(`${PREFIX}/budgets/${id}/close`),
    onSuccess: () => qc.invalidateQueries({ queryKey: accountingKeys.budgets() }),
  });
}

// ─── Tax forms ──────────────────────────────────────────────

export function useTaxForms(query: { page?: number; per_page?: number } = {}) {
  return useQuery({
    queryKey: [...accountingKeys.taxForms(), query],
    queryFn: () =>
      api.get<ApiPaginated<TaxFormSubmission>>(`${PREFIX}/tax-forms`, {
        query,
      }),
    placeholderData: keepPreviousData,
  });
}

export function useGenerateTaxForm() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: { type: string; year: number; month?: number }) =>
      api.post<ApiSuccess<unknown>>(`${PREFIX}/tax-forms/generate/${input.type}`, {
        year: input.year,
        month: input.month,
      }),
    onSuccess: () => qc.invalidateQueries({ queryKey: accountingKeys.taxForms() }),
  });
}

// ─── Reports ────────────────────────────────────────────────

export type ReportRange = { from: string; to: string };

export function useTrialBalance(range: ReportRange) {
  return useQuery({
    queryKey: accountingKeys.reports({ kind: "trial-balance", ...range }),
    queryFn: () =>
      api.get<ApiSuccess<unknown>>(`${PREFIX}/reports/trial-balance`, {
        query: { from: range.from, to: range.to },
      }),
    select: (raw) => raw.data,
    enabled: !!range.from && !!range.to,
  });
}

export function useBalanceSheet(asOf: string) {
  return useQuery({
    queryKey: accountingKeys.reports({ kind: "balance-sheet", asOf }),
    queryFn: () =>
      api.get<ApiSuccess<unknown>>(`${PREFIX}/reports/balance-sheet`, {
        query: { as_of: asOf },
      }),
    select: (raw) => raw.data,
    enabled: !!asOf,
  });
}

export function useIncomeStatement(range: ReportRange) {
  return useQuery({
    queryKey: accountingKeys.reports({ kind: "income-statement", ...range }),
    queryFn: () =>
      api.get<ApiSuccess<unknown>>(`${PREFIX}/reports/income-statement`, {
        query: { from: range.from, to: range.to },
      }),
    select: (raw) => raw.data,
    enabled: !!range.from && !!range.to,
  });
}

export function useGeneralLedger(
  range: ReportRange,
  accountId: number | null,
) {
  return useQuery({
    queryKey: accountingKeys.reports({ kind: "general-ledger", ...range, accountId }),
    queryFn: () =>
      api.get<ApiSuccess<unknown>>(`${PREFIX}/reports/general-ledger`, {
        query: { from: range.from, to: range.to, account_id: accountId },
      }),
    select: (raw) => raw.data,
    enabled: !!range.from && !!range.to && !!accountId,
  });
}

export function useCashFlow(range: ReportRange) {
  return useQuery({
    queryKey: accountingKeys.reports({ kind: "cash-flow", ...range }),
    queryFn: () =>
      api.get<ApiSuccess<unknown>>(`${PREFIX}/reports/cash-flow`, {
        query: { from: range.from, to: range.to },
      }),
    select: (raw) => raw.data,
    enabled: !!range.from && !!range.to,
  });
}
