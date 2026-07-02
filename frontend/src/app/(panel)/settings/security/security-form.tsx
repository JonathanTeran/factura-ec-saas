"use client";

import { useState } from "react";
import { Loader2, KeyRound } from "lucide-react";
import { toast } from "sonner";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Field } from "@/components/panel/form";
import { Input } from "@/components/ui/input";
import { useUpdatePassword } from "@/lib/api/queries/profile";
import { ClientApiError } from "@/lib/api/client";

export function SecurityForm() {
  const update = useUpdatePassword();
  const [form, setForm] = useState({
    current_password: "",
    password: "",
    password_confirmation: "",
  });
  const set = (k: keyof typeof form, v: string) =>
    setForm((f) => ({ ...f, [k]: v }));
  const [error, setError] = useState<string | null>(null);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    if (form.password.length < 8) {
      setError("La nueva contraseña debe tener al menos 8 caracteres.");
      return;
    }
    if (form.password !== form.password_confirmation) {
      setError("Las contraseñas no coinciden.");
      return;
    }
    try {
      await update.mutateAsync(form);
      toast.success("Contraseña actualizada.");
      setForm({ current_password: "", password: "", password_confirmation: "" });
    } catch (err) {
      const msg =
        err instanceof ClientApiError ? err.message : "No se pudo actualizar.";
      setError(msg);
      toast.error(msg);
    }
  }

  return (
    <div className="mx-auto max-w-2xl">
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <KeyRound className="size-4 text-primary" />
            Cambiar contraseña
          </CardTitle>
        </CardHeader>
        <form onSubmit={submit}>
          <CardContent className="space-y-4">
            <Field label="Contraseña actual" required htmlFor="cur">
              <Input
                id="cur"
                type="password"
                autoComplete="current-password"
                value={form.current_password}
                onChange={(e) => set("current_password", e.target.value)}
              />
            </Field>
            <Field label="Nueva contraseña" required htmlFor="new" hint="Mínimo 8 caracteres.">
              <Input
                id="new"
                type="password"
                autoComplete="new-password"
                value={form.password}
                onChange={(e) => set("password", e.target.value)}
              />
            </Field>
            <Field label="Confirmar nueva contraseña" required htmlFor="conf">
              <Input
                id="conf"
                type="password"
                autoComplete="new-password"
                value={form.password_confirmation}
                onChange={(e) => set("password_confirmation", e.target.value)}
              />
            </Field>
            {error && (
              <div className="rounded-lg border border-destructive/30 bg-destructive/5 px-3.5 py-2.5 text-sm text-destructive">
                {error}
              </div>
            )}
            <div className="flex justify-end">
              <Button type="submit" disabled={update.isPending}>
                {update.isPending && <Loader2 className="size-4 animate-spin" />}
                Actualizar contraseña
              </Button>
            </div>
          </CardContent>
        </form>
      </Card>
    </div>
  );
}
