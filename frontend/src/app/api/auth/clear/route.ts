import { NextRequest, NextResponse } from "next/server";
import { clearSession } from "@/lib/auth/session";

export async function GET(request: NextRequest) {
  await clearSession();
  const next = request.nextUrl.searchParams.get("next") ?? "/login";
  const reason = request.nextUrl.searchParams.get("reason");
  const url = request.nextUrl.clone();
  url.pathname = next;
  url.search = reason ? `?reason=${encodeURIComponent(reason)}` : "";
  return NextResponse.redirect(url);
}
