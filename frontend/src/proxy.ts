import { NextRequest, NextResponse } from "next/server";

/** Páginas de autenticación: sin sesión se ven; con sesión mandan al panel. */
const AUTH_PATHS = ["/login", "/register", "/forgot-password", "/reset-password"];

/** Páginas públicas para todos (landing y docs). Con sesión también se muestran. */
const MARKETING_PATHS = ["/"];
const MARKETING_PREFIXES = ["/docs"];

export function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl;

  if (
    pathname.startsWith("/_next") ||
    pathname.startsWith("/api/") ||
    pathname.includes(".") ||
    MARKETING_PATHS.includes(pathname) ||
    MARKETING_PREFIXES.some((p) => pathname === p || pathname.startsWith(`${p}/`))
  ) {
    return NextResponse.next();
  }

  const sessionCookie = request.cookies.get("factura_session")?.value;
  const isAuthPage = AUTH_PATHS.some(
    (p) => pathname === p || pathname.startsWith(`${p}/`),
  );

  if (!sessionCookie && !isAuthPage) {
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
