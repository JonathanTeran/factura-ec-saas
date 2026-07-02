import { cookies } from "next/headers";

const SESSION_COOKIE = "factura_session";

export type Session = {
  token: string;
  expiresAt: string | null;
};

export async function getSession(): Promise<Session | null> {
  const store = await cookies();
  const raw = store.get(SESSION_COOKIE)?.value;
  if (!raw) return null;
  try {
    return JSON.parse(raw) as Session;
  } catch {
    return null;
  }
}

export async function setSession(session: Session) {
  const store = await cookies();
  const expires = session.expiresAt ? new Date(session.expiresAt) : undefined;
  store.set(SESSION_COOKIE, JSON.stringify(session), {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    path: "/",
    expires,
  });
}

export async function clearSession() {
  const store = await cookies();
  store.delete(SESSION_COOKIE);
}
