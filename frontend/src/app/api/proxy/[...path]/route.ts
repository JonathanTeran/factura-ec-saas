import { NextRequest, NextResponse } from "next/server";
import { getSession } from "@/lib/auth/session";

const BASE = process.env.LARAVEL_API_URL ?? "http://localhost:8000";

const HOP_BY_HOP = new Set([
  "connection",
  "keep-alive",
  "proxy-authenticate",
  "proxy-authorization",
  "te",
  "trailer",
  "transfer-encoding",
  "upgrade",
  "host",
  "content-length",
  // fetch() ya descomprime el body del upstream; si reenviamos content-encoding
  // el navegador intenta descomprimir texto plano -> ERR_CONTENT_DECODING_FAILED.
  "content-encoding",
]);

async function forward(
  request: NextRequest,
  ctx: { params: Promise<{ path: string[] }> },
) {
  const { path } = await ctx.params;
  const session = await getSession();

  const upstreamUrl = new URL(`/api/v1/${path.join("/")}`, BASE);
  for (const [k, v] of request.nextUrl.searchParams) {
    upstreamUrl.searchParams.append(k, v);
  }

  const headers = new Headers();
  for (const [k, v] of request.headers) {
    if (!HOP_BY_HOP.has(k.toLowerCase())) headers.set(k, v);
  }
  headers.delete("cookie");
  if (session?.token) {
    headers.set("Authorization", `Bearer ${session.token}`);
  }
  headers.set("Accept", "application/json");

  const init: RequestInit = {
    method: request.method,
    headers,
    redirect: "manual",
  };

  if (!["GET", "HEAD"].includes(request.method)) {
    init.body = await request.arrayBuffer();
  }

  let upstream: Response;
  try {
    upstream = await fetch(upstreamUrl, init);
  } catch (err) {
    return NextResponse.json(
      {
        success: false,
        message: "El servidor backend no responde",
        error: err instanceof Error ? err.message : "unknown",
      },
      { status: 502 },
    );
  }

  const respHeaders = new Headers();
  for (const [k, v] of upstream.headers) {
    if (HOP_BY_HOP.has(k.toLowerCase())) continue;
    if (k.toLowerCase() === "set-cookie") continue;
    respHeaders.set(k, v);
  }
  // Defensa extra: fetch ya decodificó el body, nunca reenviar estos headers.
  respHeaders.delete("content-encoding");
  respHeaders.delete("content-length");

  // Bufferizamos el body ya decodificado y lo reemitimos con headers limpios.
  const body = await upstream.arrayBuffer();

  return new NextResponse(body, {
    status: upstream.status,
    statusText: upstream.statusText,
    headers: respHeaders,
  });
}

export const GET = forward;
export const POST = forward;
export const PUT = forward;
export const PATCH = forward;
export const DELETE = forward;
