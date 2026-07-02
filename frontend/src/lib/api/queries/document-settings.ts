import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { api, type ApiSuccess } from "@/lib/api/client";

export type DocumentSettings = {
  auto_send_email: boolean;
  email_subject: string;
  email_message: string;
  ride_footer: string;
};

const keys = { all: ["document-settings"] as const };

export function useDocumentSettings() {
  return useQuery({
    queryKey: keys.all,
    queryFn: () => api.get<ApiSuccess<DocumentSettings>>("document-settings"),
    select: (raw) => raw.data,
  });
}

export function useUpdateDocumentSettings() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: DocumentSettings) =>
      api.put<ApiSuccess<DocumentSettings>>("document-settings", input),
    onSuccess: () => qc.invalidateQueries({ queryKey: keys.all }),
  });
}
