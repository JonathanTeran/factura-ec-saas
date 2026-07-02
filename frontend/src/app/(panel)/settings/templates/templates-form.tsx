"use client";

import { useState } from "react";
import { Loader2, Mail } from "lucide-react";
import { toast } from "sonner";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Field } from "@/components/panel/form";
import {
  useDocumentSettings,
  useUpdateDocumentSettings,
  type DocumentSettings,
} from "@/lib/api/queries/document-settings";

export function TemplatesForm() {
  const { data, isLoading } = useDocumentSettings();
  const update = useUpdateDocumentSettings();
  const [form, setForm] = useState<DocumentSettings | null>(null);

  // Inicializa el formulario con los datos cargados. Ajustar estado durante el
  // render (patrón recomendado por React) evita el efecto con setState.
  if (data && !form) {
    setForm(data);
  }

  if (isLoading || !form) {
    return (
      <div className="flex justify-center py-24">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  const set = <K extends keyof DocumentSettings>(
    k: K,
    v: DocumentSettings[K],
  ) => setForm((f) => (f ? { ...f, [k]: v } : f));

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    if (!form) return;
    try {
      await update.mutateAsync(form);
      toast.success("Plantillas actualizadas.");
    } catch (err) {
      toast.error(err instanceof Error ? err.message : "No se pudo guardar.");
    }
  }

  return (
    <form onSubmit={submit} className="mx-auto max-w-2xl space-y-4">
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Mail className="size-4 text-primary" />
            Email al cliente
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <label className="flex items-center justify-between gap-3 rounded-lg border border-border bg-card p-3.5">
            <div>
              <p className="text-sm font-medium">Enviar comprobante por correo</p>
              <p className="text-xs text-muted-foreground">
                Al autorizarse un documento, se envía automáticamente al cliente.
              </p>
            </div>
            <button
              type="button"
              role="switch"
              aria-checked={form.auto_send_email}
              onClick={() => set("auto_send_email", !form.auto_send_email)}
              className={`relative h-6 w-11 shrink-0 rounded-full transition-colors ${
                form.auto_send_email ? "bg-primary" : "bg-muted"
              }`}
            >
              <span
                className={`absolute top-0.5 size-5 rounded-full bg-white shadow transition-transform ${
                  form.auto_send_email ? "translate-x-5" : "translate-x-0.5"
                }`}
              />
            </button>
          </label>

          <Field label="Asunto del correo" required htmlFor="subj">
            <Input
              id="subj"
              maxLength={150}
              value={form.email_subject}
              onChange={(e) => set("email_subject", e.target.value)}
            />
          </Field>

          <Field label="Mensaje del correo" required htmlFor="msg">
            <textarea
              id="msg"
              rows={4}
              maxLength={1000}
              value={form.email_message}
              onChange={(e) => set("email_message", e.target.value)}
              className="w-full rounded-lg border border-input bg-card px-3 py-2 text-sm shadow-xs outline-none transition-[color,box-shadow] placeholder:text-muted-foreground/70 hover:border-ring/40 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/40"
            />
          </Field>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>RIDE (representación impresa)</CardTitle>
        </CardHeader>
        <CardContent>
          <Field
            label="Texto de pie de página"
            htmlFor="footer"
            hint="Aparece al final del PDF del comprobante (opcional)."
          >
            <Input
              id="footer"
              maxLength={300}
              placeholder="Gracias por su compra."
              value={form.ride_footer}
              onChange={(e) => set("ride_footer", e.target.value)}
            />
          </Field>
        </CardContent>
      </Card>

      <div className="flex justify-end">
        <Button type="submit" disabled={update.isPending}>
          {update.isPending && <Loader2 className="size-4 animate-spin" />}
          Guardar cambios
        </Button>
      </div>
    </form>
  );
}
