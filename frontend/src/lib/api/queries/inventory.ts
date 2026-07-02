import {
  useMutation,
  useQuery,
  useQueryClient,
  keepPreviousData,
} from "@tanstack/react-query";
import { api, type ApiPaginated, type ApiSuccess } from "@/lib/api/client";
import type {
  InventoryMovement,
  InventorySummary,
  Product,
} from "@/lib/api/types";
import { productKeys } from "./products";

export const inventoryKeys = {
  all: ["inventory"] as const,
  summary: () => [...inventoryKeys.all, "summary"] as const,
  index: (q: Record<string, unknown>) =>
    [...inventoryKeys.all, "index", q] as const,
  lowStock: () => [...inventoryKeys.all, "low-stock"] as const,
  productMovements: (productId: number) =>
    [...inventoryKeys.all, "movements", productId] as const,
};

export function useInventorySummary() {
  return useQuery({
    queryKey: inventoryKeys.summary(),
    queryFn: () =>
      api.get<ApiSuccess<InventorySummary>>("inventory/summary"),
    select: (raw) => raw.data,
  });
}

export function useInventoryMovements(query: {
  page?: number;
  per_page?: number;
  product_id?: number;
} = {}) {
  return useQuery({
    queryKey: inventoryKeys.index(query),
    queryFn: () =>
      api.get<ApiPaginated<InventoryMovement>>("inventory", { query }),
    placeholderData: keepPreviousData,
  });
}

export function useLowStockProducts() {
  return useQuery({
    queryKey: inventoryKeys.lowStock(),
    queryFn: () =>
      api.get<ApiSuccess<{ products: Product[] } | Product[]>>(
        "inventory/low-stock",
      ),
    select: (raw) => {
      const data = raw.data as Product[] | { products: Product[] };
      return Array.isArray(data) ? data : data.products;
    },
  });
}

export function useProductMovements(productId: number | null) {
  return useQuery({
    queryKey: productId
      ? inventoryKeys.productMovements(productId)
      : ["inventory", "movements", "none"],
    queryFn: () =>
      api.get<ApiPaginated<InventoryMovement>>(
        `inventory/products/${productId}/movements`,
      ),
    enabled: !!productId,
  });
}

export function useAdjustStock() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: {
      productId: number;
      new_stock: number;
      reason: string;
    }) =>
      api.post<ApiSuccess<unknown>>(
        `inventory/products/${input.productId}/adjust`,
        { new_stock: input.new_stock, reason: input.reason },
      ),
    onSuccess: (_d, input) => {
      qc.invalidateQueries({ queryKey: inventoryKeys.all });
      qc.invalidateQueries({ queryKey: productKeys.all });
      qc.invalidateQueries({ queryKey: productKeys.detail(input.productId) });
    },
  });
}

export function usePurchaseStock() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: {
      productId: number;
      quantity: number;
      unit_cost: number;
      batch_number?: string;
      expiry_date?: string;
      notes?: string;
    }) =>
      api.post<ApiSuccess<unknown>>(
        `inventory/products/${input.productId}/purchase`,
        {
          quantity: input.quantity,
          unit_cost: input.unit_cost,
          batch_number: input.batch_number,
          expiry_date: input.expiry_date,
          notes: input.notes,
        },
      ),
    onSuccess: (_d, input) => {
      qc.invalidateQueries({ queryKey: inventoryKeys.all });
      qc.invalidateQueries({ queryKey: productKeys.all });
      qc.invalidateQueries({ queryKey: productKeys.detail(input.productId) });
    },
  });
}
