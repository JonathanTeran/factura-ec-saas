"use client";

import { useState } from "react";
import { useQueryClient } from "@tanstack/react-query";
import { Download, FileSpreadsheet, Loader2, Upload } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Field } from "@/components/panel/form";
import { customerKeys } from "@/lib/api/queries/customers";

type ImportRowError = {
  row: number;
  attribute: string;
  errors: string[];
  values: Record<string, unknown>;
};

type ImportResult = {
  success: boolean;
  message?: string;
  data?: {
    failed: number;
    errors: ImportRowError[];
  };
};

function downloadTemplate() {
  if (typeof window === "undefined") return;
  const a = window.document.createElement("a");
  a.href = "/api/proxy/imports/templates/customers";
  a.download = "plantilla_customers.xlsx";
  a.target = "_blank";
  window.document.body.appendChild(a);
  a.click();
  a.remove();
}

export function ImportCustomersDialog() {
  const qc = useQueryClient();
  const [open, setOpen] = useState(false);
  const [file, setFile] = useState<File | null>(null);
  const [pending, setPending] = useState(false);
  const [rowErrors, setRowErrors] = useState<ImportRowError[]>([]);

  const reset = () => {
    setFile(null);
    setRowErrors([]);
  };

  const onSubmit = async () => {
    if (!file) return;
    setPending(true);
    setRowErrors([]);
    try {
      const fd = new FormData();
      fd.append("file", file);
      const res = await fetch("/api/proxy/imports/customers", {
        method: "POST",
        body: fd,
        headers: { Accept: "application/json" },
      });
      const payload = (await res.json().catch(() => null)) as
        | ImportResult
        | { message?: string }
        | null;
      if (!res.ok) {
        throw new Error(
          payload?.message ?? "No se pudo importar el archivo.",
        );
      }
      const result = payload as ImportResult;
      const failed = result.data?.failed ?? 0;
      const errors = result.data?.errors ?? [];

      qc.invalidateQueries({ queryKey: customerKeys.all });

      if (failed > 0) {
        setRowErrors(errors);
        toast.warning(
          `Importación completada con ${failed} fila${failed === 1 ? "" : "s"} con errores.`,
        );
      } else {
        toast.success(result.message ?? "Clientes importados correctamente");
        setOpen(false);
        reset();
      }
    } catch (err) {
      toast.error(
        err instanceof Error ? err.message : "No se pudo importar el archivo.",
      );
    } finally {
      setPending(false);
    }
  };

  return (
    <Dialog
      open={open}
      onOpenChange={(v) => {
        setOpen(v);
        if (!v) reset();
      }}
    >
      <DialogTrigger asChild>
        <Button variant="outline">
          <Upload className="size-4" />
          Importar
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Importar clientes</DialogTitle>
          <DialogDescription>
            Sube un archivo CSV o Excel con tus clientes. Usa la plantilla para
            asegurar el formato correcto.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          <button
            type="button"
            onClick={downloadTemplate}
            className="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline"
          >
            <Download className="size-4" />
            Descargar plantilla
          </button>

          <Field label="Archivo (.csv / .xlsx)">
            <label className="flex cursor-pointer items-center gap-3 rounded-lg border border-dashed border-input bg-card px-4 py-4 text-sm transition hover:border-primary/40">
              <FileSpreadsheet className="size-5 text-muted-foreground" />
              <span className={file ? "font-medium" : "text-muted-foreground"}>
                {file ? file.name : "Selecciona tu archivo de clientes"}
              </span>
              <input
                type="file"
                accept=".csv,.xlsx"
                className="hidden"
                onChange={(e) => {
                  setFile(e.target.files?.[0] ?? null);
                  setRowErrors([]);
                }}
              />
            </label>
          </Field>

          {rowErrors.length > 0 && (
            <div className="max-h-40 space-y-1.5 overflow-y-auto rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-xs">
              {rowErrors.map((e, i) => (
                <p key={i} className="text-destructive">
                  <span className="font-medium">Fila {e.row}</span> (
                  {e.attribute}): {e.errors.join(" ")}
                </p>
              ))}
            </div>
          )}
        </div>

        <DialogFooter>
          <Button
            type="button"
            variant="outline"
            onClick={() => setOpen(false)}
          >
            Cancelar
          </Button>
          <Button type="button" disabled={!file || pending} onClick={onSubmit}>
            {pending && <Loader2 className="size-4 animate-spin" />}
            Importar clientes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
