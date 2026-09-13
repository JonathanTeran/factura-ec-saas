"use client";

import { useActionState } from "react";
import { CheckboxField, FormAlert, PasswordField, SubmitButton, TextField } from "@/components/auth/fields";
import { registerAction, type AuthState } from "../actions";

const ACCOUNT_TYPES = [
  { value: "generic", label: "Empresa o negocio", hint: "Facturas, notas, retenciones y guías para cualquier actividad." },
  { value: "referee", label: "Árbitro de fútbol", hint: "Control de partidos designados y facturación a la FEF." },
] as const;

export function RegisterForm({
  defaultType = "generic",
  plan = "",
}: {
  defaultType?: "generic" | "referee";
  /** Slug del plan elegido en la landing: se recuerda para preseleccionar la compra. */
  plan?: string;
}) {
  const [state, action] = useActionState<AuthState, FormData>(registerAction, null);
  const values: Record<string, string> = state?.values ?? {};

  return (
    <form action={action} className="space-y-5">
      {plan && <input type="hidden" name="plan" value={plan} />}
      <TextField id="name" name="name" label="Tu nombre" autoComplete="name" required defaultValue={values.name ?? ""} errors={state?.fieldErrors?.name} />
      <TextField id="company_name" name="company_name" label="Nombre de empresa" autoComplete="organization" required defaultValue={values.company_name ?? ""} errors={state?.fieldErrors?.company_name} />
      <TextField id="email" name="email" type="email" label="Correo electrónico" autoComplete="email" required defaultValue={values.email ?? ""} errors={state?.fieldErrors?.email} />
      <fieldset className="space-y-2">
        <legend className="text-sm font-medium text-navy">Tipo de cuenta</legend>
        <div className="grid gap-3 sm:grid-cols-2">
          {ACCOUNT_TYPES.map((t) => (
            <label
              key={t.value}
              className="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-3.5 text-left transition has-[:checked]:border-brand has-[:checked]:bg-brand/5 has-[:checked]:ring-1 has-[:checked]:ring-brand/30"
            >
              <input
                type="radio"
                name="business_type"
                value={t.value}
                defaultChecked={(values.business_type ?? defaultType) === t.value}
                className="mt-1 size-4 accent-brand"
              />
              <span>
                <span className="block text-sm font-semibold text-navy">{t.label}</span>
                <span className="mt-0.5 block text-xs text-slate-500">{t.hint}</span>
              </span>
            </label>
          ))}
        </div>
        {state?.fieldErrors?.business_type && (
          <p className="text-xs text-red-600">{state.fieldErrors.business_type[0]}</p>
        )}
      </fieldset>
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
