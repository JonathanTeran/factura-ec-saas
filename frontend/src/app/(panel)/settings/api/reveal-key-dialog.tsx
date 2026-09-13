"use client";

import { useState } from "react";
import { Check, Copy, KeyRound, TriangleAlert } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";

export function RevealKeyDialog({
  plainKey,
  keyName,
  baseUrl,
  onClose,
}: {
  plainKey: string | null;
  keyName: string;
  baseUrl: string;
  onClose: () => void;
}) {
  const [copied, setCopied] = useState(false);
  const curl = `curl ${baseUrl}/me \\\n  -H "Authorization: Bearer ${plainKey ?? "fec_…"}"`;

  async function copy(text: string, what: string) {
    try {
      await navigator.clipboard.writeText(text);
      setCopied(true);
      toast.success(`${what} copiada.`);
      setTimeout(() => setCopied(false), 2000);
    } catch {
      toast.error("No se pudo copiar. Selecciona el texto y cópialo manualmente.");
    }
  }

  return (
    <Dialog open={plainKey !== null} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <KeyRound className="size-4 text-primary" />
            Tu llave «{keyName}»
          </DialogTitle>
          <DialogDescription>
            Cópiala ahora y guárdala como un secreto en tu sistema.
          </DialogDescription>
        </DialogHeader>

        <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
          <p className="flex items-start gap-2">
            <TriangleAlert className="mt-0.5 size-4 shrink-0" />
            <span>
              <strong>No volverá a mostrarse.</strong> Si la pierdes, rótala desde esta misma pantalla.
            </span>
          </p>
        </div>

        <div className="space-y-1.5">
          <p className="text-xs font-medium text-muted-foreground">Llave de API</p>
          <div className="flex items-stretch gap-2">
            <code
              data-testid="plain-key"
              className="flex-1 overflow-x-auto rounded-md border border-border bg-muted px-3 py-2 font-mono text-[13px]"
            >
              {plainKey}
            </code>
            <Button type="button" variant="outline" onClick={() => plainKey && copy(plainKey, "Llave")}>
              {copied ? <Check className="size-4" /> : <Copy className="size-4" />}
              Copiar
            </Button>
          </div>
        </div>

        <div className="space-y-1.5">
          <p className="text-xs font-medium text-muted-foreground">Pruébala</p>
          <pre className="overflow-x-auto rounded-md bg-[#0b1220] p-3 font-mono text-[12px] leading-relaxed text-slate-100">{curl}</pre>
        </div>

        <DialogFooter>
          <Button type="button" onClick={onClose}>
            Ya la guardé
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
