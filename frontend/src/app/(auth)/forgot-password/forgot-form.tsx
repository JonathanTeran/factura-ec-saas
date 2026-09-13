"use client";

import { useActionState } from "react";
import { FormAlert, SubmitButton, TextField } from "@/components/auth/fields";
import { forgotPasswordAction, type AuthState } from "../actions";

export function ForgotForm() {
  const [state, action] = useActionState<AuthState, FormData>(forgotPasswordAction, null);

  return (
    <form action={action} className="space-y-5">
      <TextField id="email" name="email" type="email" label="Correo electrónico" placeholder="tu@empresa.com" autoComplete="email" required errors={state?.fieldErrors?.email} />
      {state?.message && <FormAlert tone={state.ok ? "success" : "error"}>{state.message}</FormAlert>}
      <SubmitButton>Enviar enlace</SubmitButton>
    </form>
  );
}
