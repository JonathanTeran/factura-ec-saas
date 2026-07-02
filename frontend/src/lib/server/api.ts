import { getSession } from "@/lib/auth/session";

const BASE = process.env.LARAVEL_API_URL ?? "http://localhost:8000";

export class ApiError extends Error {
  constructor(
    public status: number,
    public payload: unknown,
    message?: string,
  ) {
    super(message ?? `API error ${status}`);
  }
}

type FetchOptions = RequestInit & {
  json?: unknown;
  query?: Record<string, string | number | boolean | undefined | null>;
  withAuth?: boolean;
};

function buildUrl(path: string, query?: FetchOptions["query"]) {
  const url = new URL(path.startsWith("/") ? path : `/${path}`, BASE);
  if (query) {
    for (const [k, v] of Object.entries(query)) {
      if (v === undefined || v === null) continue;
      url.searchParams.set(k, String(v));
    }
  }
  return url.toString();
}

export async function apiFetch<T = unknown>(
  path: string,
  opts: FetchOptions = {},
): Promise<T> {
  const { json, query, withAuth = true, headers, ...rest } = opts;

  const finalHeaders: HeadersInit = {
    Accept: "application/json",
    ...headers,
  };

  if (json !== undefined) {
    (finalHeaders as Record<string, string>)["Content-Type"] = "application/json";
  }

  if (withAuth) {
    const session = await getSession();
    if (session?.token) {
      (finalHeaders as Record<string, string>)["Authorization"] =
        `Bearer ${session.token}`;
    }
  }

  const res = await fetch(buildUrl(path, query), {
    ...rest,
    headers: finalHeaders,
    body: json !== undefined ? JSON.stringify(json) : rest.body,
    cache: rest.cache ?? "no-store",
  });

  const contentType = res.headers.get("content-type") ?? "";
  const isJson = contentType.includes("application/json");
  const payload = isJson ? await res.json().catch(() => null) : await res.text();

  if (!res.ok) {
    throw new ApiError(res.status, payload, `API error ${res.status} on ${path}`);
  }

  return payload as T;
}
