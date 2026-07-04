import { useMutation, useQueryClient } from "@tanstack/react-query";
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
        {},
      ),
    onSuccess: () => {
      if (companyId) {
        qc.invalidateQueries({ queryKey: companyKeys.branches(companyId) });
        qc.invalidateQueries({
          queryKey: companyKeys.emissionPoints(companyId),
        });
      }
    },
  });
}
