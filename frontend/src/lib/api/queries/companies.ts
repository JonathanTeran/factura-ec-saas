import {
  useMutation,
  useQuery,
  useQueryClient,
} from "@tanstack/react-query";
import { api, type ApiSuccess } from "@/lib/api/client";
import type { Branch, Company, EmissionPoint } from "@/lib/api/types";

export type BranchInput = {
  company_id: number;
  code: string;
  name: string;
  address: string;
  phone?: string;
  email?: string;
  is_main?: boolean;
  is_active?: boolean;
};

export type EmissionPointInput = {
  branch_id: number;
  code: string;
  description?: string;
  is_active?: boolean;
};

export const companyKeys = {
  all: ["companies"] as const,
  list: () => [...companyKeys.all, "list"] as const,
  detail: (id: number) => [...companyKeys.all, "detail", id] as const,
  branches: (companyId: number) =>
    [...companyKeys.all, companyId, "branches"] as const,
  emissionPoints: (companyId: number) =>
    [...companyKeys.all, companyId, "emission-points"] as const,
};

// La API devuelve `business_name` (razón social) mientras que el tipo/consumo
// del panel espera `legal_name`. Normalizamos para que `c.legal_name` no sea
// undefined en los selectores de empresa.
function normalizeCompany(raw: Company & { business_name?: string }): Company {
  return {
    ...raw,
    legal_name: raw.legal_name ?? raw.business_name ?? "",
  };
}

export function useCompanies() {
  return useQuery({
    queryKey: companyKeys.list(),
    queryFn: () =>
      api.get<ApiSuccess<{ companies: Company[] } | Company[]>>("companies"),
    select: (raw) => {
      const data = raw.data as Company[] | { companies: Company[] };
      const list = Array.isArray(data) ? data : data.companies;
      return (list ?? []).map(normalizeCompany);
    },
  });
}

export type CompanyDetail = {
  id: number;
  ruc: string;
  business_name: string;
  trade_name: string | null;
  address: string;
  phone: string | null;
  email: string;
  logo_url: string | null;
  sri_environment: "1" | "2";
  is_special_taxpayer: boolean;
  special_taxpayer_number: string | null;
  retention_agent_number: string | null;
  taxpayer_type: "natural" | "juridical" | "rise";
  rimpe_type: "none" | "emprendedor" | "negocio_popular" | null;
  is_accounting_required: boolean;
  has_sri_password: boolean;
  has_valid_signature: boolean;
  is_ready_for_emission: boolean;
};

export type CompanyUpdateInput = {
  ruc: string;
  business_name: string;
  trade_name?: string;
  taxpayer_type: string;
  rimpe_type?: string;
  address: string;
  special_taxpayer?: boolean;
  special_taxpayer_number?: string | null;
  retention_agent_number?: string | null;
  obligated_accounting?: boolean;
  sri_environment: string;
  email: string;
  phone?: string | null;
  sri_password?: string;
};

export function useCompanyDetail(companyId: number | null) {
  return useQuery({
    queryKey: companyId
      ? companyKeys.detail(companyId)
      : ["companies", "detail", "none"],
    queryFn: () =>
      api.get<ApiSuccess<{ company: CompanyDetail }>>(
        `companies/${companyId}`,
      ),
    select: (raw) => raw.data.company,
    enabled: !!companyId,
  });
}

export function useUpdateCompany(companyId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: CompanyUpdateInput) =>
      api.put<ApiSuccess<{ company: CompanyDetail }>>(
        `companies/${companyId}`,
        input,
      ),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: companyKeys.all });
    },
  });
}

export function useUploadCompanyLogo(companyId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (file: File) => {
      const fd = new FormData();
      fd.append("logo", file);
      const res = await fetch(`/api/proxy/companies/${companyId}/logo`, {
        method: "POST",
        body: fd,
        headers: { Accept: "application/json" },
      });
      const payload = await res.json().catch(() => null);
      if (!res.ok) {
        throw new Error(payload?.message ?? "No se pudo subir el logo.");
      }
      return payload as ApiSuccess<{ logo_url: string }>;
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: companyKeys.all }),
  });
}

export function useDeleteCompanyLogo(companyId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: () =>
      api.delete<ApiSuccess<{ logo_url: null }>>(`companies/${companyId}/logo`),
    onSuccess: () => qc.invalidateQueries({ queryKey: companyKeys.all }),
  });
}

export function useCompanyBranches(companyId: number | null) {
  return useQuery({
    queryKey: companyId
      ? companyKeys.branches(companyId)
      : ["companies", "branches", "none"],
    queryFn: () =>
      api.get<ApiSuccess<{ branches: Branch[] } | Branch[]>>(
        `companies/${companyId}/branches`,
      ),
    select: (raw) => {
      const data = raw.data as Branch[] | { branches: Branch[] };
      return Array.isArray(data) ? data : data.branches;
    },
    enabled: !!companyId,
  });
}

export function useEmissionPoints(companyId: number | null) {
  return useQuery({
    queryKey: companyId
      ? companyKeys.emissionPoints(companyId)
      : ["companies", "emission-points", "none"],
    queryFn: () =>
      api.get<
        ApiSuccess<
          { emission_points: EmissionPoint[] } | EmissionPoint[]
        >
      >(`companies/${companyId}/emission-points`),
    select: (raw) => {
      const data = raw.data as
        | EmissionPoint[]
        | { emission_points: EmissionPoint[] };
      return Array.isArray(data) ? data : data.emission_points;
    },
    enabled: !!companyId,
  });
}

export function useCreateBranch() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: BranchInput) =>
      api.post<ApiSuccess<unknown>>(
        `companies/${input.company_id}/branches`,
        input,
      ),
    onSuccess: (_d, input) => {
      qc.invalidateQueries({ queryKey: companyKeys.branches(input.company_id) });
      qc.invalidateQueries({
        queryKey: companyKeys.emissionPoints(input.company_id),
      });
    },
  });
}

export function useDeleteBranch(companyId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (branchId: number) =>
      api.delete<ApiSuccess<unknown>>(
        `companies/${companyId}/branches/${branchId}`,
      ),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: companyKeys.branches(companyId) });
    },
  });
}

export function useCreateEmissionPoint() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: EmissionPointInput) =>
      api.post<ApiSuccess<unknown>>(
        `branches/${input.branch_id}/emission-points`,
        input,
      ),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: companyKeys.all });
    },
  });
}

export function useDeleteEmissionPoint(branchId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) =>
      api.delete<ApiSuccess<unknown>>(
        `branches/${branchId}/emission-points/${id}`,
      ),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: companyKeys.all });
    },
  });
}
