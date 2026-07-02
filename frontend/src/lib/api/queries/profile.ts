import {
  useMutation,
  useQuery,
  useQueryClient,
} from "@tanstack/react-query";
import { api, type ApiSuccess } from "@/lib/api/client";
import type { User } from "@/lib/api/types";

export const profileKeys = {
  all: ["profile"] as const,
  current: () => [...profileKeys.all, "current"] as const,
};

export function useProfile() {
  return useQuery({
    queryKey: profileKeys.current(),
    queryFn: () => api.get<ApiSuccess<{ user: User }>>("profile"),
    select: (raw) => raw.data.user,
  });
}

export function useUpdateProfile() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: { name?: string; phone?: string }) =>
      api.put<ApiSuccess<{ user: User }>>("profile", input),
    onSuccess: () => qc.invalidateQueries({ queryKey: profileKeys.all }),
  });
}

export function useUpdatePassword() {
  return useMutation({
    mutationFn: (input: {
      current_password: string;
      password: string;
      password_confirmation: string;
    }) => api.put<ApiSuccess<unknown>>("profile/password", input),
  });
}
