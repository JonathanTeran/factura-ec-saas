import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { api, type ApiSuccess } from "@/lib/api/client";
import type { BusinessType } from "@/lib/api/types";
import { profileKeys } from "./profile";

export type OnboardingStatus = {
  completed: boolean;
  business_type?: BusinessType;
  has_company: boolean;
  has_certificate: boolean;
  has_establishment: boolean;
  has_sequentials: boolean;
};

export type SignatureStatus = {
  status: "missing" | "unknown" | "expired" | "expiring_soon" | "valid";
  message: string | null;
  days_remaining: number | null;
  expires_at: string | null;
  subject: string | null;
};

export type CompanyInput = {
  ruc: string;
  business_name: string;
  trade_name?: string;
  address: string;
  city?: string;
  province?: string;
  phone?: string;
  email: string;
  taxpayer_type: string;
  obligated_accounting: boolean;
  rimpe_type?: string;
  sri_environment: string;
};

export type EstablishmentInput = {
  branch_name: string;
  branch_code: string;
  branch_address: string;
  ep_code: string;
  ep_name?: string;
  /** Importar automáticamente las demás sucursales abiertas del SRI. */
  import_sri_establishments?: boolean;
};

export type SequentialItem = { document_type: string; last_number: number };

export const onboardingKeys = {
  status: ["onboarding", "status"] as const,
};

export function useOnboardingStatus() {
  return useQuery({
    queryKey: onboardingKeys.status,
    queryFn: () =>
      api.get<ApiSuccess<OnboardingStatus>>("onboarding/status"),
    select: (raw) => raw.data,
  });
}

export const signatureKeys = { status: ["signature", "status"] as const };

export function useSignatureStatus() {
  return useQuery({
    queryKey: signatureKeys.status,
    queryFn: () =>
      api.get<ApiSuccess<SignatureStatus>>("signature-status"),
    select: (raw) => raw.data,
  });
}

export type RucLookupResult = {
  ruc: string;
  business_name: string;
  status: string;
  taxpayer_type: "natural" | "juridical";
  regime: "general" | "rimpe_emprendedor" | "rimpe_popular";
  obligated_accounting: boolean;
  retention_agent: boolean;
  special_taxpayer: boolean;
  main_activity: string | null;
  establishments: Array<{
    code: string;
    trade_name: string | null;
    address: string | null;
    is_main: boolean;
    is_open: boolean;
  }>;
};

/** Consulta pública del catastro del SRI para autocompletar por RUC. */
export function useRucLookup() {
  return useMutation({
    mutationFn: (ruc: string) =>
      api.get<ApiSuccess<RucLookupResult>>(`sri/ruc/${ruc}`),
  });
}

/** Define el tipo de negocio (activa el vertical de árbitros si aplica). */
export function useSetBusinessType() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (business_type: BusinessType) =>
      api.post<ApiSuccess<{ business_type: BusinessType }>>(
        "onboarding/business-type",
        { business_type },
      ),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: onboardingKeys.status });
      qc.invalidateQueries({ queryKey: profileKeys.all });
    },
  });
}

export function useSaveCompany() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: CompanyInput) =>
      api.post<ApiSuccess<unknown>>("onboarding/company", input),
    onSuccess: () => qc.invalidateQueries({ queryKey: onboardingKeys.status }),
  });
}

export function useSaveEstablishment() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: EstablishmentInput) =>
      api.post<
        ApiSuccess<{
          emission_point: { id: number };
          imported_branches: Array<{
            id: number;
            code: string;
            name: string;
            address: string;
          }>;
        }>
      >(
        "onboarding/establishment",
        {
          // El backend espera name/code/address (+ ep_code/ep_name).
          name: input.branch_name,
          code: input.branch_code,
          address: input.branch_address,
          ep_code: input.ep_code,
          ep_name: input.ep_name,
          import_sri_establishments: input.import_sri_establishments ?? false,
        },
      ),
    onSuccess: () => qc.invalidateQueries({ queryKey: onboardingKeys.status }),
  });
}

export function useSaveSequentials() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: {
      emission_point_id: number;
      sequentials: SequentialItem[];
    }) => api.post<ApiSuccess<unknown>>("onboarding/sequentials", input),
    onSuccess: () => qc.invalidateQueries({ queryKey: onboardingKeys.status }),
  });
}

export function useCompleteOnboarding() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: () => api.post<ApiSuccess<unknown>>("onboarding/complete", {}),
    onSuccess: () => qc.invalidateQueries({ queryKey: onboardingKeys.status }),
  });
}

export type CertificateInfo = {
  signature_subject: string;
  signature_issuer: string;
  signature_identification: string | null;
  signature_serial: string | null;
  signature_valid_from: string | null;
  signature_expires_at: string;
  days_until_expiry: number;
};

/** Certificado: multipart, se envía por fetch directo al proxy. */
export function useUploadCertificate() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (input: { file: File; password: string }) => {
      const fd = new FormData();
      fd.append("certificate", input.file);
      fd.append("password", input.password);
      const res = await fetch("/api/proxy/onboarding/certificate", {
        method: "POST",
        body: fd,
        headers: { Accept: "application/json" },
      });
      const payload = await res.json().catch(() => null);
      if (!res.ok) {
        throw new Error(
          payload?.message ?? "No se pudo validar el certificado.",
        );
      }
      return payload as ApiSuccess<CertificateInfo>;
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: onboardingKeys.status });
      qc.invalidateQueries({ queryKey: signatureKeys.status });
    },
  });
}
