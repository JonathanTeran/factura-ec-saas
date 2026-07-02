"use client";

import { useActionState } from "react";
import { useFormStatus } from "react-dom";
import { Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { forgotPasswordAction, type AuthState } from "../actions";

function SubmitButton() {
  const { pending } = useFormStatus();
  return (
    <Button type="submit" className="w-full" disabled={pending}>
      {pending && <Loader2 className="size-4 animate-spin" />}
      Enviar enlace
    </Button>
  );
}

export function ForgotForm() {
  const [state, action] = useActionState<AuthState, FormData>(
    forgotPasswordAction,
    null,
  );

  return (
    <form action={action} className="space-y-4">
      <div className="space-y-2">
        <Label htmlFor="email">Correo electrónico</Label>
        <Input id="email" name="email" type="email" required />
        {state?.fieldErrors?.email && (
          <p className="text-xs text-destructive">{state.fieldErrors.email[0]}</p>
        )}
      </div>
      {state?.message && (
        <p
          className={`text-sm ${
            state.ok ? "text-green-600 dark:text-green-400" : "text-destructive"
          }`}
        >
          {state.message}
        </p>
      )}
      <SubmitButton />
    </form>
  );
}
