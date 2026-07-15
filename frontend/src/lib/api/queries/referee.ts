import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { api, type ApiSuccess } from "@/lib/api/client";

export type RefereeMatchStatus =
  | "pending"
  | "queued"
  | "invoiced"
  | "blocked_window";

export type RefereeRole =
  | "arbitro"
  | "asistente_1"
  | "asistente_2"
  | "cuarto"
  | "var"
  | "comisario"
  | "delegado";

export type RefereeProfile = {
  referee_name: string | null;
  referee_default_fee: number;
  counts: {
    pending: number;
    queued: number;
    invoiced: number;
    blocked_window: number;
  };
};

export type RefereeMatch = {
  id: number;
  match_date: string;
  championship_id: number | null;
  championship: string | null;
  home_club: string;
  away_club: string;
  role: RefereeRole;
  fee: number | string;
  status: RefereeMatchStatus;
  source: "scraper" | "manual";
  notes: string | null;
  invoiced_at: string | null;
  document: { id: number; status: string; number: string } | null;
  window: {
    open: boolean;
    start_day: number;
    end_day: number;
    reason: string | null;
  };
};

export type RefereeMatchesWindow = {
  today: string;
  start_day: number;
  end_day: number;
  open_today: boolean;
};

export type RefereeMatchesResponse = {
  matches: RefereeMatch[];
  window: RefereeMatchesWindow;
};

export type RefereeChampionship = {
  id: number;
  name: string;
  category: string | null;
  season: string | null;
};

export type RefereeClub = { id: number; name: string };

export type CreateMatchInput = {
  championship_id: number;
  home_club_id: number;
  away_club_id: number;
  match_date: string;
  role: RefereeRole;
  fee: number;
  notes?: string;
};

export type UpdateMatchInput = {
  fee?: number;
  role?: RefereeRole;
  notes?: string;
};

export type InvoiceMatchesResult = {
  results: Array<{
    id: number;
    status: string;
    message: string | null;
    document_id: number | null;
  }>;
  summary: {
    queued: number;
    draft: number;
    blocked_window: number;
    skipped: number;
    error: number;
  };
};

export const refereeKeys = {
  all: ["referee"] as const,
  profile: ["referee", "profile"] as const,
  matches: ["referee", "matches"] as const,
  matchesList: (status?: string) =>
    ["referee", "matches", { status: status ?? "all" }] as const,
  championships: ["referee", "championships"] as const,
  clubs: (search: string) => ["referee", "clubs", search] as const,
};

export function useRefereeProfile() {
  return useQuery({
    queryKey: refereeKeys.profile,
    queryFn: () => api.get<ApiSuccess<RefereeProfile>>("referee/profile"),
    select: (raw) => raw.data,
  });
}

export function useUpdateRefereeProfile() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: { referee_name: string; referee_default_fee?: number }) =>
      api.put<ApiSuccess<RefereeProfile>>("referee/profile", input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: refereeKeys.profile });
      qc.invalidateQueries({ queryKey: refereeKeys.matches });
    },
  });
}

export function useRefereeMatches(status?: string) {
  return useQuery({
    queryKey: refereeKeys.matchesList(status),
    queryFn: () =>
      api.get<ApiSuccess<RefereeMatchesResponse>>("referee/matches", {
        query: status ? { status } : undefined,
      }),
    select: (raw) => raw.data,
  });
}

export function useCreateRefereeMatch() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: CreateMatchInput) =>
      api.post<ApiSuccess<{ match: RefereeMatch }>>("referee/matches", input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: refereeKeys.matches });
      qc.invalidateQueries({ queryKey: refereeKeys.profile });
    },
  });
}

export function useUpdateRefereeMatch() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, ...input }: UpdateMatchInput & { id: number }) =>
      api.put<ApiSuccess<{ match: RefereeMatch }>>(
        `referee/matches/${id}`,
        input,
      ),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: refereeKeys.matches });
      qc.invalidateQueries({ queryKey: refereeKeys.profile });
    },
  });
}

export function useDeleteRefereeMatch() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) =>
      api.delete<ApiSuccess<unknown>>(`referee/matches/${id}`),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: refereeKeys.matches });
      qc.invalidateQueries({ queryKey: refereeKeys.profile });
    },
  });
}

export function useInvoiceRefereeMatches() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: { ids: number[]; customer_id: number }) =>
      api.post<ApiSuccess<InvoiceMatchesResult>>(
        "referee/matches/invoice",
        input,
      ),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: refereeKeys.matches });
      qc.invalidateQueries({ queryKey: refereeKeys.profile });
    },
  });
}

export function useRefereeChampionships() {
  return useQuery({
    queryKey: refereeKeys.championships,
    queryFn: () =>
      api.get<ApiSuccess<{ championships: RefereeChampionship[] }>>(
        "referee/championships",
      ),
    select: (raw) => raw.data.championships,
  });
}

export function useRefereeClubs(search: string, enabled = true) {
  return useQuery({
    queryKey: refereeKeys.clubs(search),
    queryFn: () =>
      api.get<ApiSuccess<{ clubs: RefereeClub[] }>>("referee/clubs", {
        query: search ? { search } : undefined,
      }),
    select: (raw) => raw.data.clubs,
    enabled,
  });
}
