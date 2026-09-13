"use client";

import Link from "next/link";
import { useActionState } from "react";
import { FormAlert, PasswordField, SubmitButton, TextField } from "@/components/auth/fields";
import { loginAction, type AuthState } from "../actions";

export function LoginForm() {
  const [state, action] = useActionState<AuthState, FormData>(loginAction, null);
  const values: Record<string, string> = state?.values ?? {};

  return (
    <form action={action} className="space-y-5">
      <TextField
        id="email"
        name="email"
        type="email"
        label="Correo electrónico"
        placeholder="tu@empresa.com"
        autoComplete="email"
        required
        defaultValue={values.email ?? ""}
        errors={state?.fieldErrors?.email}
      />
      <PasswordField
        id="password"
        name="password"
        label="Contraseña"
        placeholder="••••••••"
        autoComplete="current-password"
        required
        errors={state?.fieldErrors?.password}
        aside={
          <Link href="/forgot-password" className="text-xs font-medium text-slate-500 underline-offset-4 hover:text-brand hover:underline">
            ¿Olvidaste tu contraseña?
          </Link>
        }
      />
      {state?.message && !state.ok && <FormAlert>{state.message}</FormAlert>}
      <SubmitButton>Iniciar sesión</SubmitButton>
    </form>
  );
}
