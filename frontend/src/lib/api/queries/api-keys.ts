import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { api, ClientApiError, type ApiSuccess } from "@/lib/api/client";

export type ApiKey = {
  id: number;
  name: string;
  key_prefix: string;
  scopes: string[];
  rate_limit_per_minute: number;
  effective_rate_limit: number;
  last_used_at: string | null;
  last_used_ip: string | null;
  expires_at: string | null;
  is_active: boolean;
  is_expired: boolean;
  created_by?: { id: number; name: string } | null;
  created_at: string | null;
};

export type ApiKeysData = {
  api_keys: ApiKey[];
  scopes: Record<string, string>;
  limits: { max_keys: number; plan_rate_limit: number; plan_slug: string | null };
  base_url: string;
  docs_url: string;
};

export type CreateApiKeyInput = {
  name: string;
  scopes: string[];
  expires_in_days: 30 | 90 | 365 | null;
  rate_limit_per_minute: number | null;
};

export type UpdateApiKeyInput = Partial<{ name: string; scopes: string[]; is_active: boolean; rate_limit_per_minute: number | null }>;

export type CreatedApiKey = { api_key: ApiKey; plain_key: string };

const KEY = ["api-keys"] as const;

/** El backend responde 403 feature_not_available cuando el plan no incluye API. */
export function isFeatureLocked(err: unknown): boolean {
  if (!(err instanceof ClientApiError)) return false;
  const payload = err.payload as { error?: string } | null;
  return err.status === 403 && payload?.error === "feature_not_available";
}

export function errorMessage(err: unknown, fallback = "Ocurrió un error."): string {
  if (err instanceof ClientApiError) {
    const p = err.payload as { message?: string; errors?: Record<string, string[]> } | null;
    const first = p?.errors ? Object.values(p.errors).flat()[0] : undefined;
    return first ?? p?.message ?? err.message;
  }
  return err instanceof Error ? err.message : fallback;
}

export function useApiKeys() {
  return useQuery({
    queryKey: KEY,
    queryFn: async () => (await api.get<ApiSuccess<ApiKeysData>>("api-keys")).data,
    retry: (count, err) => !isFeatureLocked(err) && count < 2,
  });
}

export function useCreateApiKey() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (input: CreateApiKeyInput) => (await api.post<ApiSuccess<CreatedApiKey>>("api-keys", input)).data,
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  });
}

export function useUpdateApiKey() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, ...input }: UpdateApiKeyInput & { id: number }) =>
      (await api.patch<ApiSuccess<{ api_key: ApiKey }>>(`api-keys/${id}`, input)).data,
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  });
}

export function useRotateApiKey() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => (await api.post<ApiSuccess<CreatedApiKey>>(`api-keys/${id}/rotate`)).data,
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  });
}

export function useDeleteApiKey() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => api.delete<ApiSuccess<null>>(`api-keys/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  });
}
