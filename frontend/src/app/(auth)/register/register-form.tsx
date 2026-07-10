"use client";

import { useActionState } from "react";
import { useFormStatus } from "react-dom";
import { Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { registerAction, type AuthState } from "../actions";

function FieldError({ errors }: { errors?: string[] }) {
  if (!errors || errors.length === 0) return null;
  return <p className="text-xs text-destructive">{errors[0]}</p>;
}

function SubmitButton() {
  const { pending } = useFormStatus();
  return (
    <Button type="submit" className="w-full" disabled={pending}>
      {pending && <Loader2 className="size-4 animate-spin" />}
      Crear cuenta
    </Button>
  );
}

export function RegisterForm() {
  const [state, action] = useActionState<AuthState, FormData>(
    registerAction,
    null,
  );

  return (
    <form action={action} className="space-y-4">
      <div className="space-y-2">
        <Label htmlFor="name">Tu nombre</Label>
        <Input id="name" name="name" required autoComplete="name" />
        <FieldError errors={state?.fieldErrors?.name} />
      </div>
      <div className="space-y-2">
        <Label htmlFor="company_name">Nombre de empresa</Label>
        <Input id="company_name" name="company_name" required />
        <FieldError errors={state?.fieldErrors?.company_name} />
      </div>
      <div className="space-y-2">
        <Label htmlFor="email">Correo electrónico</Label>
        <Input id="email" name="email" type="email" required autoComplete="email" />
        <FieldError errors={state?.fieldErrors?.email} />
      </div>
      <div className="grid grid-cols-2 gap-3">
        <div className="space-y-2">
          <Label htmlFor="password">Contraseña</Label>
          <Input
            id="password"
            name="password"
            type="password"
            required
            autoComplete="new-password"
          />
          <p className="text-xs text-muted-foreground">
            Mínimo 8 caracteres, con mayúscula, minúscula y un carácter
            especial.
          </p>
          <FieldError errors={state?.fieldErrors?.password} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="password_confirmation">Confirmar</Label>
          <Input
            id="password_confirmation"
            name="password_confirmation"
            type="password"
            required
            autoComplete="new-password"
          />
          <FieldError errors={state?.fieldErrors?.password_confirmation} />
        </div>
      </div>
      {state?.message && !state.ok && (
        <p className="text-sm text-destructive">{state.message}</p>
      )}
      <SubmitButton />
    </form>
  );
}
