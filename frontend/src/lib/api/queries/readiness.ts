import { useQuery } from "@tanstack/react-query";
import { api, type ApiSuccess } from "@/lib/api/client";

export type EmissionReadiness = {
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

/**
 * Checklist de requisitos para emitir al SRI (firma, clave, establecimientos,
 * datos básicos). Se usa para bloquear preventivamente el envío de
 * documentos en vez de dejar que el backend lo rechace recién al enviar.
 */
export function useEmissionReadiness() {
  return useQuery({
    queryKey: ["readiness"],
    queryFn: () => api.get<ApiSuccess<EmissionReadiness>>("dashboard/readiness"),
    select: (raw) => raw.data,
  });
}
