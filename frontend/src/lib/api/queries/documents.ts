import {
  useMutation,
  useQuery,
  useQueryClient,
  keepPreviousData,
  type UseQueryOptions,
} from "@tanstack/react-query";
import { api, type ApiPaginated, type ApiSuccess } from "@/lib/api/client";
import type { Document } from "@/lib/api/types";

export type DocumentsQuery = {
  page?: number;
  per_page?: number;
  search?: string;
  status?: string;
  document_type?: string;
};

export const documentKeys = {
  all: ["documents"] as const,
  list: (q: DocumentsQuery) => [...documentKeys.all, "list", q] as const,
  detail: (id: number) => [...documentKeys.all, "detail", id] as const,
  sriStatus: (id: number) => [...documentKeys.detail(id), "sri-status"] as const,
};

/** Estados no finales: el SRI todavía no respondió y hay que seguir consultando. */
const PENDING_SRI_STATUSES = new Set(["processing", "signed", "sent"]);

export function isPendingSriStatus(status: string | null | undefined): boolean {
  return !!status && PENDING_SRI_STATUSES.has(status);
}

type DocumentsQueryOptions = Pick<
  UseQueryOptions<ApiPaginated<Document>>,
  "refetchInterval"
>;

export function useDocuments(
  query: DocumentsQuery = {},
  options: DocumentsQueryOptions = {},
) {
  return useQuery({
    queryKey: documentKeys.list(query),
    queryFn: () => api.get<ApiPaginated<Document>>("documents", { query }),
    placeholderData: keepPreviousData,
    refetchInterval: options.refetchInterval,
  });
}

export function useDocument(id: number) {
  return useQuery({
    queryKey: documentKeys.detail(id),
    queryFn: () =>
      api.get<ApiSuccess<{ document: Document }>>(`documents/${id}`),
  });
}

export type DocumentLiveStatus = {
  status: string;
  status_label?: string | null;
  authorization_number?: string | null;
  authorization_date?: string | null;
};

/**
 * Polling del estado SRI mientras el documento sigue en proceso.
 * GET /documents/{id}/status re-consulta al SRI en vivo; cuando el estado
 * cambia se refrescan detalle y listados, y al llegar a un estado final el
 * polling se apaga solo (enabled=false).
 */
export function useDocumentSriPolling(
  id: number,
  status: string | null | undefined,
) {
  const qc = useQueryClient();
  return useQuery({
    queryKey: documentKeys.sriStatus(id),
    queryFn: async () => {
      const res = await api.get<ApiSuccess<DocumentLiveStatus>>(
        `documents/${id}/status`,
      );
      if (res.data.status !== status) {
        qc.invalidateQueries({ queryKey: documentKeys.all });
      }
      return res;
    },
    enabled: isPendingSriStatus(status),
    refetchInterval: 4000,
    refetchIntervalInBackground: false,
  });
}

export type RetentionCode = {
  tax_type: "renta" | "iva";
  code: string;
  name: string;
  description: string | null;
  percentage: number | null;
};

export type RetentionCodesResponse = {
  retention_codes: {
    renta?: RetentionCode[];
    iva?: RetentionCode[];
  };
};

export function useRetentionCodes() {
  return useQuery({
    queryKey: ["catalogs", "retention-codes"] as const,
    queryFn: () =>
      api.get<ApiSuccess<RetentionCodesResponse>>("catalogs/retention-codes"),
    staleTime: 1000 * 60 * 30, // catálogo SRI: cambia muy poco
  });
}

export function useSendDocument(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: () => api.post<ApiSuccess<unknown>>(`documents/${id}/send`),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: documentKeys.detail(id) });
      qc.invalidateQueries({ queryKey: documentKeys.all });
    },
  });
}

export function useVoidDocument(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (reason: string) =>
      api.post<ApiSuccess<unknown>>(`documents/${id}/void`, { reason }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: documentKeys.detail(id) });
      qc.invalidateQueries({ queryKey: documentKeys.all });
    },
  });
}

export function useUpdateDocument(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: unknown) =>
      api.put<ApiSuccess<{ document: Document }>>(`documents/${id}`, payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: documentKeys.all });
      qc.invalidateQueries({ queryKey: documentKeys.detail(id) });
    },
  });
}

export function useDeleteDocument() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) =>
      api.delete<ApiSuccess<unknown>>(`documents/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: documentKeys.all }),
  });
}

export function useResendEmail(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (email?: string) =>
      api.post<ApiSuccess<unknown>>(`documents/${id}/resend-email`, { email }),
    // Refresca el detalle para que "Envío por correo" muestre el nuevo estado.
    onSuccess: () => qc.invalidateQueries({ queryKey: documentKeys.detail(id) }),
  });
}

export function useCheckStatus(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: () =>
      api.get<ApiSuccess<unknown>>(`documents/${id}/status`),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: documentKeys.detail(id) });
    },
  });
}

export async function downloadDocumentRide(id: number, filename?: string) {
  const res = await api.get<ApiSuccess<{ url: string; filename: string }>>(
    `documents/${id}/ride`,
  );
  triggerBrowserDownload(res.data.url, filename ?? res.data.filename);
}

/** URL pública del RIDE (PDF), para compartir por WhatsApp/enlace. */
export async function documentRideUrl(id: number): Promise<string> {
  const res = await api.get<ApiSuccess<{ url: string; filename: string }>>(
    `documents/${id}/ride`,
  );
  return res.data.url;
}

export async function downloadDocumentXml(id: number, filename?: string) {
  const res = await api.get<ApiSuccess<{ url: string; filename: string }>>(
    `documents/${id}/xml`,
  );
  triggerBrowserDownload(res.data.url, filename ?? res.data.filename);
}

function triggerBrowserDownload(url: string, filename: string) {
  if (typeof window === "undefined") return;
  const a = window.document.createElement("a");
  a.href = url;
  a.download = filename;
  a.target = "_blank";
  a.rel = "noopener";
  window.document.body.appendChild(a);
  a.click();
  a.remove();
}
