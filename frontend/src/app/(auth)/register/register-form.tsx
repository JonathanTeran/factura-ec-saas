"use client";

import { useActionState } from "react";
import { CheckboxField, FormAlert, PasswordField, SubmitButton, TextField } from "@/components/auth/fields";
import { registerAction, type AuthState } from "../actions";

export function RegisterForm() {
  const [state, action] = useActionState<AuthState, FormData>(registerAction, null);
  const values: Record<string, string> = state?.values ?? {};

  return (
    <form action={action} className="space-y-5">
      <TextField id="name" name="name" label="Tu nombre" autoComplete="name" required defaultValue={values.name ?? ""} errors={state?.fieldErrors?.name} />
      <TextField id="company_name" name="company_name" label="Nombre de empresa" autoComplete="organization" required defaultValue={values.company_name ?? ""} errors={state?.fieldErrors?.company_name} />
      <TextField id="email" name="email" type="email" label="Correo electrónico" autoComplete="email" required defaultValue={values.email ?? ""} errors={state?.fieldErrors?.email} />
      <div className="grid gap-5 sm:grid-cols-2">
        <PasswordField id="password" name="password" label="Contraseña" autoComplete="new-password" required showRules errors={state?.fieldErrors?.password} className="sm:col-span-2" />
        <PasswordField id="password_confirmation" name="password_confirmation" label="Confirmar contraseña" autoComplete="new-password" required errors={state?.fieldErrors?.password_confirmation} className="sm:col-span-2" />
      </div>
      <CheckboxField
        name="terms"
        defaultChecked={values.terms === "on"}
        errors={state?.fieldErrors?.terms}
        label={
          <>
            Acepto los{" "}
            <a href="/terms" target="_blank" rel="noopener noreferrer" className="font-semibold text-brand underline-offset-4 hover:underline">
              Términos y Condiciones
            </a>{" "}
            y la{" "}
            <a href="/privacy" target="_blank" rel="noopener noreferrer" className="font-semibold text-brand underline-offset-4 hover:underline">
              Política de Privacidad
            </a>{" "}
            de Facturón.
          </>
        }
      />
      {state?.message && !state.ok && <FormAlert>{state.message}</FormAlert>}
      <SubmitButton>Crear cuenta</SubmitButton>
    </form>
  );
}
