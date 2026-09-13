"use client";

import { useState } from "react";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Field } from "@/components/panel/form";
import { errorMessage, useCreateApiKey, type CreateApiKeyInput, type CreatedApiKey } from "@/lib/api/queries/api-keys";

const SCOPE_GROUPS: Array<{ title: string; scopes: string[] }> = [
  { title: "Documentos", scopes: ["documents:read", "documents:write"] },
  { title: "Clientes", scopes: ["customers:read", "customers:write"] },
  { title: "Productos", scopes: ["products:read", "products:write"] },
];

const EXPIRY_OPTIONS: Array<{ value: "" | "30" | "90" | "365"; label: string }> = [
  { value: "", label: "Nunca" },
  { value: "30", label: "30 días" },
  { value: "90", label: "90 días" },
  { value: "365", label: "1 año" },
];

export function CreateKeyDialog({
  open,
  onOpenChange,
  scopeLabels,
  planRateLimit,
  onCreated,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  scopeLabels: Record<string, string>;
  planRateLimit: number;
  onCreated: (created: CreatedApiKey) => void;
}) {
  const create = useCreateApiKey();
  const [name, setName] = useState("");
  const [fullAccess, setFullAccess] = useState(false);
  const [scopes, setScopes] = useState<Set<string>>(new Set(["documents:read", "documents:write", "customers:read", "customers:write"]));
  const [expiry, setExpiry] = useState<"" | "30" | "90" | "365">("");
  const [rateLimit, setRateLimit] = useState<string>(String(planRateLimit));

  function toggle(scope: string) {
    setScopes((prev) => {
      const next = new Set(prev);
      if (next.has(scope)) next.delete(scope);
      else next.add(scope);
      return next;
    });
  }

  function reset() {
    setName("");
    setFullAccess(false);
    setScopes(new Set(["documents:read", "documents:write", "customers:read", "customers:write"]));
    setExpiry("");
    setRateLimit(String(planRateLimit));
  }

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    const selected = fullAccess ? ["*"] : Array.from(scopes);
    if (selected.length === 0) {
      toast.error("Elige al menos un alcance.");
      return;
    }
    const payload: CreateApiKeyInput = {
      name: name.trim(),
      scopes: selected,
      expires_in_days: expiry === "" ? null : (Number(expiry) as 30 | 90 | 365),
      rate_limit_per_minute: rateLimit === "" ? null : Number(rateLimit),
    };
    try {
      const created = await create.mutateAsync(payload);
      reset();
      onOpenChange(false);
      onCreated(created);
    } catch (err) {
      toast.error(errorMessage(err, "No se pudo crear la llave."));
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg">
        <form onSubmit={submit} className="space-y-5">
          <DialogHeader>
            <DialogTitle>Nueva llave de API</DialogTitle>
            <DialogDescription>Una llave por sistema, con los alcances mínimos que necesite.</DialogDescription>
          </DialogHeader>

          <Field label="Nombre" required htmlFor="key-name" hint="Para reconocerla: «Tienda en línea», «ERP contable»…">
            <Input id="key-name" value={name} onChange={(e) => setName(e.target.value)} maxLength={100} required autoFocus />
          </Field>

          <fieldset className="space-y-3">
            <legend className="text-sm font-medium">Alcances</legend>
            <label className="flex items-center justify-between gap-3 rounded-lg border border-border bg-card p-3">
              <span>
                <span className="block text-sm font-medium">Acceso total</span>
                <span className="block text-xs text-muted-foreground">Todos los alcances actuales y futuros.</span>
              </span>
              <input
                type="checkbox"
                className="size-4 accent-primary"
                checked={fullAccess}
                onChange={(e) => setFullAccess(e.target.checked)}
                aria-label="Acceso total"
              />
            </label>
            <div className={`grid gap-2 sm:grid-cols-3 ${fullAccess ? "pointer-events-none opacity-50" : ""}`}>
              {SCOPE_GROUPS.map((group) => (
                <div key={group.title} className="rounded-lg border border-border p-3">
                  <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">{group.title}</p>
                  <div className="mt-2 space-y-2">
                    {group.scopes.map((scope) => (
                      <label key={scope} className="flex items-start gap-2 text-sm">
                        <input
                          type="checkbox"
                          className="mt-0.5 size-4 accent-primary"
                          checked={fullAccess || scopes.has(scope)}
                          onChange={() => toggle(scope)}
                          disabled={fullAccess}
                          aria-label={scope}
                        />
                        <span>
                          <code className="font-mono text-[12px]">{scope.split(":")[1]}</code>
                          <span className="block text-[11px] leading-snug text-muted-foreground">{scopeLabels[scope] ?? ""}</span>
                        </span>
                      </label>
                    ))}
                  </div>
                </div>
              ))}
            </div>
            <p className="text-xs text-muted-foreground">Los catálogos del SRI están incluidos en toda llave.</p>
          </fieldset>

          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Caducidad" htmlFor="key-expiry">
              <select
                id="key-expiry"
                value={expiry}
                onChange={(e) => setExpiry(e.target.value as "" | "30" | "90" | "365")}
                className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
              >
                {EXPIRY_OPTIONS.map((o) => (
                  <option key={o.value} value={o.value}>
                    {o.label}
                  </option>
                ))}
              </select>
            </Field>
            <Field label="Límite por minuto" htmlFor="key-rate" hint={`Máximo de tu plan: ${planRateLimit}`}>
              <Input
                id="key-rate"
                type="number"
                min={1}
                max={planRateLimit}
                value={rateLimit}
                onChange={(e) => setRateLimit(e.target.value)}
              />
            </Field>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
              Cancelar
            </Button>
            <Button type="submit" disabled={create.isPending || name.trim() === ""}>
              {create.isPending && <Loader2 className="size-4 animate-spin" />}
              Crear llave
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
