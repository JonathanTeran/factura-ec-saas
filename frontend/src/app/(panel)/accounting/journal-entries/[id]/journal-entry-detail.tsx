"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Ban, CheckCircle2, Loader2 } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import {
  useDeleteJournalEntry,
  useJournalEntry,
  usePostJournalEntry,
  useVoidJournalEntry,
} from "@/lib/api/queries/accounting";
import { DeleteConfirmButton } from "@/components/forms/delete-confirm-button";
import { ClientApiError } from "@/lib/api/client";
import { formatDate, formatMoney } from "@/lib/format";

function errMessage(err: unknown): string {
  if (err instanceof ClientApiError) {
    const p = err.payload as
      | { message?: string; errors?: Record<string, string[]> }
      | null;
    const first = p?.errors ? Object.values(p.errors).flat()[0] : null;
    return first ?? p?.message ?? err.message;
  }
  return err instanceof Error ? err.message : "Error inesperado";
}

function statusVariant(status: string): "default" | "secondary" | "destructive" {
  if (status === "posted") return "default";
  if (status === "void") return "destructive";
  return "secondary";
}

export function JournalEntryDetail({ id }: { id: number }) {
  const router = useRouter();
  const { data, isLoading, error } = useJournalEntry(id);
  const post = usePostJournalEntry();
  const voidEntry = useVoidJournalEntry();
  const del = useDeleteJournalEntry();
  const [voidOpen, setVoidOpen] = useState(false);
  const [voidReason, setVoidReason] = useState("");

  if (isLoading) {
    return (
      <div className="flex justify-center py-24">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="text-sm text-destructive">
        Error: {(error as Error).message}
      </div>
    );
  }

  const j = data;
  if (!j) return null;

  const canPost = j.status === "draft";
  const canVoid = j.status === "posted";
  const canDelete = j.status === "draft";

  return (
    <div className="space-y-6">
      <Card>
        <CardHeader className="flex flex-row items-start justify-between">
          <div>
            <CardTitle className="text-xl">
              Asiento <span className="font-mono">{j.entry_number ?? `#${j.id}`}</span>
            </CardTitle>
            <p className="text-sm text-muted-foreground mt-1">
              {formatDate(j.entry_date)} · {j.description ?? "—"}
            </p>
          </div>
          <Badge variant={statusVariant(j.status)} className="capitalize">
            {j.status}
          </Badge>
        </CardHeader>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Acciones</CardTitle>
        </CardHeader>
        <CardContent className="flex flex-wrap gap-2">
          <Button
            disabled={!canPost || post.isPending}
            onClick={() =>
              post.mutate(j.id, {
                onSuccess: () => toast.success("Asiento posteado"),
                onError: (e) => toast.error(errMessage(e)),
              })
            }
          >
            {post.isPending ? (
              <Loader2 className="size-4 animate-spin" />
            ) : (
              <CheckCircle2 className="size-4" />
            )}
            Postear asiento
          </Button>

          {canDelete && (
            <DeleteConfirmButton
              onConfirm={async () => {
                await del.mutateAsync(j.id);
                router.push("/accounting/journal-entries");
              }}
              isPending={del.isPending}
              title="¿Eliminar borrador?"
              description="Solo se pueden eliminar asientos en borrador."
              successMessage="Asiento eliminado"
              triggerVariant="outline"
              triggerSize="default"
              triggerLabel="Eliminar"
            />
          )}

          <Dialog open={voidOpen} onOpenChange={setVoidOpen}>
            <DialogTrigger asChild>
              <Button variant="destructive" disabled={!canVoid}>
                <Ban className="size-4" /> Anular
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Anular asiento posteado</DialogTitle>
                <DialogDescription>
                  Esto crea un asiento inverso para revertir el efecto.
                </DialogDescription>
              </DialogHeader>
              <div className="space-y-2">
                <Label htmlFor="void-reason">Motivo</Label>
                <Input
                  id="void-reason"
                  value={voidReason}
                  onChange={(e) => setVoidReason(e.target.value)}
                  placeholder="Error de captura, ajuste..."
                />
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={() => setVoidOpen(false)}>
                  Cancelar
                </Button>
                <Button
                  variant="destructive"
                  disabled={!voidReason || voidEntry.isPending}
                  onClick={() =>
                    voidEntry.mutate(
                      { id: j.id, reason: voidReason },
                      {
                        onSuccess: () => {
                          toast.success("Asiento anulado");
                          setVoidOpen(false);
                          setVoidReason("");
                        },
                        onError: (e) => toast.error(errMessage(e)),
                      },
                    )
                  }
                >
                  {voidEntry.isPending && (
                    <Loader2 className="size-4 animate-spin" />
                  )}
                  Confirmar anulación
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Detalle</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Cuenta</TableHead>
                <TableHead>Descripción</TableHead>
                <TableHead className="text-right">Debe</TableHead>
                <TableHead className="text-right">Haber</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {(j.lines ?? []).map((line) => (
                <TableRow key={line.id}>
                  <TableCell className="text-sm">
                    {line.account ? (
                      <span className="font-mono">
                        {line.account.code} · {line.account.name}
                      </span>
                    ) : (
                      `#${line.account_id}`
                    )}
                  </TableCell>
                  <TableCell>{line.description ?? "—"}</TableCell>
                  <TableCell className="text-right font-mono">
                    {Number(line.debit) > 0 ? formatMoney(line.debit) : ""}
                  </TableCell>
                  <TableCell className="text-right font-mono">
                    {Number(line.credit) > 0 ? formatMoney(line.credit) : ""}
                  </TableCell>
                </TableRow>
              ))}
              <TableRow className="border-t-2">
                <TableCell colSpan={2} className="font-medium">
                  Totales
                </TableCell>
                <TableCell className="text-right font-mono font-semibold">
                  {formatMoney(j.total_debit)}
                </TableCell>
                <TableCell className="text-right font-mono font-semibold">
                  {formatMoney(j.total_credit)}
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}
