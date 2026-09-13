import { NextRequest, NextResponse } from "next/server";
import { PANEL_PATHS } from "@/lib/landing/seo";

/** Páginas de autenticación: sin sesión se ven; con sesión mandan al panel. */
const AUTH_PATHS = ["/login", "/register", "/forgot-password", "/reset-password"];

/** Páginas públicas para todos (landing y docs). Con sesión también se muestran. */
const MARKETING_PATHS = ["/"];
const MARKETING_PREFIXES = ["/docs"];

/**
 * Prefijos privados del panel: sin sesión van al login conservando el destino.
 * Los layouts del panel verifican la sesión de todos modos (requireUser); este
 * redirect solo la anticipa. Cualquier otra ruta desconocida sigue hasta Next,
 * que responde con la página 404 de marca en lugar de mandar al login.
 */
const PROTECTED_PREFIXES = PANEL_PATHS.filter((p) => p !== "/api/");

const matchesPrefix = (pathname: string, prefixes: readonly string[]) =>
  prefixes.some((p) => pathname === p || pathname.startsWith(`${p}/`));

export function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl;

  if (
    pathname.startsWith("/_next") ||
    pathname.startsWith("/api/") ||
    pathname.includes(".") ||
    MARKETING_PATHS.includes(pathname) ||
    matchesPrefix(pathname, MARKETING_PREFIXES)
  ) {
    return NextResponse.next();
  }

  const sessionCookie = request.cookies.get("factura_session")?.value;
  const isAuthPage = matchesPrefix(pathname, AUTH_PATHS);

  if (!sessionCookie && matchesPrefix(pathname, PROTECTED_PREFIXES)) {
    const url = request.nextUrl.clone();
    url.pathname = "/login";
    url.searchParams.set("next", pathname);
    return NextResponse.redirect(url);
  }

  if (sessionCookie && isAuthPage) {
    const url = request.nextUrl.clone();
    url.pathname = "/dashboard";
    url.search = "";
    return NextResponse.redirect(url);
  }

  return NextResponse.next();
}

export const config = {
  matcher: ["/((?!_next/static|_next/image|favicon.ico).*)"],
};
