import {
  useMutation,
  useQuery,
  useQueryClient,
  keepPreviousData,
} from "@tanstack/react-query";
import { api, type ApiPaginated, type ApiSuccess } from "@/lib/api/client";
import type { PersonalExpense } from "@/lib/api/types";

export const personalExpenseKeys = {
  all: ["personal-expenses"] as const,
  list: (q: Record<string, unknown>) =>
    [...personalExpenseKeys.all, "list", q] as const,
  summary: (year: number) =>
    [...personalExpenseKeys.all, "summary", year] as const,
  budgets: () => [...personalExpenseKeys.all, "budget"] as const,
  budget: (year: number, month: number) =>
    [...personalExpenseKeys.budgets(), year, month] as const,
};

export type DeductibleBudgetData = {
  budgets: Record<string, number>;
  spent: Record<string, number>;
  month: number;
  year: number;
};

export function usePersonalExpenses(
  query: {
    fiscal_year?: number;
    category?: string;
    page?: number;
    per_page?: number;
  } = {},
) {
  return useQuery({
    queryKey: personalExpenseKeys.list(query),
    queryFn: () =>
      api.get<ApiPaginated<PersonalExpense>>("personal-expenses", { query }),
    placeholderData: keepPreviousData,
  });
}

export function usePersonalExpenseSummary(year: number) {
  return useQuery({
    queryKey: personalExpenseKeys.summary(year),
    queryFn: () =>
      api.get<
        ApiSuccess<{
          fiscal_year: number;
          total: number;
          count: number;
          by_category: Array<{ category: string; count: number; total: number }>;
        }>
      >("personal-expenses-summary", { query: { fiscal_year: year } }),
    select: (raw) => raw.data,
  });
}

export function useDeductibleBudget(year: number, month: number) {
  return useQuery({
    queryKey: personalExpenseKeys.budget(year, month),
    queryFn: () =>
      api.get<ApiSuccess<DeductibleBudgetData>>("personal-expenses-budget", {
        query: { year, month },
      }),
    select: (raw) => raw.data,
    placeholderData: keepPreviousData,
  });
}

export function useSaveDeductibleBudget() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (budgets: Record<string, number>) =>
      api.put<ApiSuccess<{ budgets: Record<string, number> }>>(
        "personal-expenses-budget",
        { budgets },
      ),
    onSuccess: () =>
      qc.invalidateQueries({ queryKey: personalExpenseKeys.budgets() }),
  });
}

export function useCreatePersonalExpense() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: Record<string, unknown>) =>
      api.post<ApiSuccess<{ expense: PersonalExpense }>>(
        "personal-expenses",
        input,
      ),
    onSuccess: () =>
      qc.invalidateQueries({ queryKey: personalExpenseKeys.all }),
  });
}

export function useDeletePersonalExpense() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) =>
      api.delete<ApiSuccess<unknown>>(`personal-expenses/${id}`),
    onSuccess: () =>
      qc.invalidateQueries({ queryKey: personalExpenseKeys.all }),
  });
}
