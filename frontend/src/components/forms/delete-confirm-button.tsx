"use client";

import { useState } from "react";
import { Loader2, Trash2, type LucideIcon } from "lucide-react";
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

export function DeleteConfirmButton({
  onConfirm,
  isPending,
  title = "Eliminar",
  description = "Esta acción no se puede deshacer.",
  successMessage = "Eliminado",
  triggerLabel,
  triggerVariant = "ghost",
  triggerSize = "icon",
  iconOnly = false,
  icon: Icon = Trash2,
  confirmLabel = "Eliminar",
}: {
  onConfirm: () => Promise<unknown>;
  isPending: boolean;
  title?: string;
  description?: string;
  successMessage?: string;
  triggerLabel?: string;
  triggerVariant?:
    | "default"
    | "outline"
    | "ghost"
    | "destructive"
    | "secondary"
    | "link";
  triggerSize?: "default" | "sm" | "lg" | "icon";
  iconOnly?: boolean;
  icon?: LucideIcon;
  confirmLabel?: string;
}) {
  const [open, setOpen] = useState(false);

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button
          type="button"
          variant={triggerVariant}
          size={triggerSize}
          aria-label={confirmLabel}
        >
          <Icon className="size-4" />
          {!iconOnly && triggerLabel ? <span>{triggerLabel}</span> : null}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{title}</DialogTitle>
          <DialogDescription>{description}</DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="outline" onClick={() => setOpen(false)}>
            Cancelar
          </Button>
          <Button
            variant="destructive"
            disabled={isPending}
            onClick={async () => {
              try {
                await onConfirm();
                toast.success(successMessage);
                setOpen(false);
              } catch (e) {
                toast.error(
                  e instanceof Error ? e.message : "Error al eliminar",
                );
              }
            }}
          >
            {isPending && <Loader2 className="size-4 animate-spin" />}
            {confirmLabel}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
