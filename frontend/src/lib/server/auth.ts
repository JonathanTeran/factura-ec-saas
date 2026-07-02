import { redirect } from "next/navigation";
import { apiFetch, ApiError } from "./api";
import type { ApiSuccess, User } from "@/lib/api/types";

export async function getCurrentUser(): Promise<User | null> {
  try {
    const res = await apiFetch<ApiSuccess<{ user: User }>>("/api/v1/auth/me");
    return res.data.user;
  } catch (err) {
    if (err instanceof ApiError) return null;
    throw err;
  }
}

export async function requireUser(): Promise<User> {
  let user: User | null = null;
  let reason = "expired";
  try {
    user = await getCurrentUser();
  } catch {
    reason = "unreachable";
  }
  if (!user) {
    redirect(`/api/auth/clear?next=/login&reason=${reason}`);
  }
  return user;
}
