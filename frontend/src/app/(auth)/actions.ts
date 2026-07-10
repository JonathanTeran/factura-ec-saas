"use server";

import { redirect } from "next/navigation";
import { z } from "zod";
import { apiFetch, ApiError } from "@/lib/server/api";
import { clearSession, setSession } from "@/lib/auth/session";
import type { ApiSuccess, LoginPayload } from "@/lib/api/types";

const LoginSchema = z.object({
  email: z.email("Correo inválido"),
  password: z.string().min(1, "Contraseña requerida"),
});

const RegisterSchema = z.object({
  name: z.string().min(2, "Nombre muy corto"),
  company_name: z.string().min(2, "Nombre de empresa requerido"),
  email: z.email("Correo inválido"),
  password: z.string().min(8, "Mínimo 8 caracteres"),
  password_confirmation: z.string(),
  terms: z.literal("on", {
    error: "Debes aceptar los Términos y Condiciones y la Política de Privacidad",
  }),
}).refine((d) => d.password === d.password_confirmation, {
  message: "Las contraseñas no coinciden",
  path: ["password_confirmation"],
});

const ForgotSchema = z.object({
  email: z.email("Correo inválido"),
});

export type AuthState = {
  ok: boolean;
  message?: string;
  fieldErrors?: Record<string, string[]>;
} | null;

export async function loginAction(
  _prev: AuthState,
  formData: FormData,
): Promise<AuthState> {
  const parsed = LoginSchema.safeParse({
    email: formData.get("email"),
    password: formData.get("password"),
  });

  if (!parsed.success) {
    return {
      ok: false,
      fieldErrors: z.flattenError(parsed.error).fieldErrors as Record<string, string[]>,
    };
  }

  try {
    const res = await apiFetch<ApiSuccess<LoginPayload>>("/api/v1/auth/login", {
      method: "POST",
      json: { ...parsed.data, device_name: "web-spa" },
      withAuth: false,
    });

    await setSession({
      token: res.data.token,
      expiresAt: res.data.expires_at,
    });
  } catch (err) {
    if (err instanceof ApiError) {
      const payload = err.payload as { message?: string; errors?: Record<string, string[]> };
      return {
        ok: false,
        message: payload?.message ?? "Credenciales inválidas",
        fieldErrors: payload?.errors,
      };
    }
    return { ok: false, message: "Error de conexión con el servidor" };
  }

  redirect("/");
}

export async function registerAction(
  _prev: AuthState,
  formData: FormData,
): Promise<AuthState> {
  const parsed = RegisterSchema.safeParse({
    name: formData.get("name"),
    company_name: formData.get("company_name"),
    email: formData.get("email"),
    password: formData.get("password"),
    password_confirmation: formData.get("password_confirmation"),
    terms: formData.get("terms"),
  });

  if (!parsed.success) {
    return {
      ok: false,
      fieldErrors: z.flattenError(parsed.error).fieldErrors as Record<string, string[]>,
    };
  }

  try {
    const res = await apiFetch<ApiSuccess<LoginPayload>>("/api/v1/auth/register", {
      method: "POST",
      json: { ...parsed.data, terms: true, device_name: "web-spa" },
      withAuth: false,
    });

    await setSession({
      token: res.data.token,
      expiresAt: res.data.expires_at,
    });
  } catch (err) {
    if (err instanceof ApiError) {
      const payload = err.payload as { message?: string; errors?: Record<string, string[]> };
      return {
        ok: false,
        message: payload?.message ?? "No se pudo crear la cuenta",
        fieldErrors: payload?.errors,
      };
    }
    return { ok: false, message: "Error de conexión con el servidor" };
  }

  redirect("/");
}

export async function forgotPasswordAction(
  _prev: AuthState,
  formData: FormData,
): Promise<AuthState> {
  const parsed = ForgotSchema.safeParse({ email: formData.get("email") });
  if (!parsed.success) {
    return {
      ok: false,
      fieldErrors: z.flattenError(parsed.error).fieldErrors as Record<string, string[]>,
    };
  }

  try {
    await apiFetch("/api/v1/auth/forgot-password", {
      method: "POST",
      json: parsed.data,
      withAuth: false,
    });
    return { ok: true, message: "Te enviamos un correo con instrucciones" };
  } catch (err) {
    if (err instanceof ApiError) {
      const payload = err.payload as { message?: string };
      return { ok: false, message: payload?.message ?? "Error al solicitar recuperación" };
    }
    return { ok: false, message: "Error de conexión" };
  }
}

export async function logoutAction() {
  try {
    await apiFetch("/api/v1/auth/logout", { method: "POST" });
  } catch {
    // ignore — we still clear local session
  }
  await clearSession();
  redirect("/login");
}
