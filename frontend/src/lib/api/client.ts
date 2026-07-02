import type { ApiError, ApiPaginated, ApiSuccess } from "./types";

const PROXY_BASE = "/api/proxy";

export class ClientApiError extends Error {
  constructor(
    public status: number,
    public payload: ApiError | unknown,
  ) {
    super(
      payload && typeof payload === "object" && "message" in payload
        ? String((payload as ApiError).message)
        : `API error ${status}`,
    );
  }
}

type FetchOptions = {
  method?: "GET" | "POST" | "PUT" | "PATCH" | "DELETE";
  json?: unknown;
  query?: Record<string, string | number | boolean | undefined | null>;
  signal?: AbortSignal;
};

function buildUrl(path: string, query?: FetchOptions["query"]) {
  const cleanPath = path.startsWith("/") ? path.slice(1) : path;
  const url = new URL(
    `${PROXY_BASE}/${cleanPath}`,
    typeof window === "undefined" ? "http://localhost:3000" : window.location.origin,
  );
  if (query) {
    for (const [k, v] of Object.entries(query)) {
      if (v === undefined || v === null) continue;
      url.searchParams.set(k, String(v));
    }
  }
  return url.toString();
}

async function request<T>(path: string, opts: FetchOptions = {}): Promise<T> {
  const { method = "GET", json, query, signal } = opts;

  const headers: HeadersInit = { Accept: "application/json" };
  if (json !== undefined) headers["Content-Type"] = "application/json";

  const res = await fetch(buildUrl(path, query), {
    method,
    headers,
    body: json !== undefined ? JSON.stringify(json) : undefined,
    signal,
    credentials: "same-origin",
  });

  const ct = res.headers.get("content-type") ?? "";
  const isJson = ct.includes("application/json");
  const payload = isJson ? await res.json().catch(() => null) : await res.text();

  if (!res.ok) {
    throw new ClientApiError(res.status, payload);
  }

  return payload as T;
}

export const api = {
  get: <T>(path: string, opts?: Omit<FetchOptions, "method" | "json">) =>
    request<T>(path, { ...opts, method: "GET" }),
  post: <T>(path: string, body?: unknown, opts?: Omit<FetchOptions, "method" | "json">) =>
    request<T>(path, { ...opts, method: "POST", json: body }),
  put: <T>(path: string, body?: unknown, opts?: Omit<FetchOptions, "method" | "json">) =>
    request<T>(path, { ...opts, method: "PUT", json: body }),
  patch: <T>(path: string, body?: unknown, opts?: Omit<FetchOptions, "method" | "json">) =>
    request<T>(path, { ...opts, method: "PATCH", json: body }),
  delete: <T>(path: string, opts?: Omit<FetchOptions, "method" | "json">) =>
    request<T>(path, { ...opts, method: "DELETE" }),
};

export type { ApiSuccess, ApiPaginated, ApiError };
