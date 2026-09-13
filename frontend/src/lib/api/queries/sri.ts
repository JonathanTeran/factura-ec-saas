import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { api, type ApiSuccess } from "@/lib/api/client";
import { companyKeys } from "@/lib/api/queries/companies";

export type SriTaxpayer = {
  ruc: string;
  business_name: string;
  status: string;
  taxpayer_type: "natural" | "juridical";
  regime: "general" | "rimpe_emprendedor" | "rimpe_popular";
  obligated_accounting: boolean;
  retention_agent: boolean;
  special_taxpayer: boolean;
  main_activity: string | null;
  address: string | null;
};

/**
 * Consulta el catastro público del SRI por cédula (10 dígitos) o RUC (13)
 * para autocompletar clientes y proveedores.
 */
export function useSriIdentificationLookup() {
  return useMutation({
    mutationFn: (identification: string) =>
      api.get<ApiSuccess<SriTaxpayer>>(`sri/identification/${identification}`),
  });
}

export type ImportedBranch = {
  id: number;
  code: string;
  name: string;
  address: string;
};

/** Importa las sucursales abiertas del SRI que aún no existen. */
export function useImportSriEstablishments(companyId: number | null) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: () =>
      api.post<ApiSuccess<{ imported: ImportedBranch[] }>>(
        "sri/import-establishments",
        companyId ? { company_id: companyId } : {},
      ),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["sri", "establishments", companyId] });
      if (companyId) {
        qc.invalidateQueries({ queryKey: companyKeys.branches(companyId) });
        qc.invalidateQueries({
          queryKey: companyKeys.emissionPoints(companyId),
        });
      }
    },
  });
}

export type SriEstablishment = {
  code: string;
  trade_name: string | null;
  address: string | null;
  is_main: boolean;
  is_open: boolean;
  configured: boolean;
  branch_id: number | null;
  branch_name: string | null;
  branch_is_active: boolean | null;
};

export type SriEstablishmentsResult = {
  company: { id: number; ruc: string; business_name: string };
  establishments: SriEstablishment[];
  pending_import: number;
};

export const sriKeys = {
  establishments: (companyId: number | null) =>
    ["sri", "establishments", companyId] as const,
};

/** Establecimientos del RUC según el catastro del SRI, marcando los ya configurados. */
export function useSriEstablishments(companyId: number | null, enabled = true) {
  return useQuery({
    queryKey: sriKeys.establishments(companyId),
    queryFn: () =>
      api.get<ApiSuccess<SriEstablishmentsResult>>("sri/establishments", {
        query: { company_id: companyId },
      }),
    enabled: !!companyId && enabled,
    staleTime: 60_000,
    retry: false,
    select: (raw) => raw.data,
  });
}
