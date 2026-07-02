"use client";

import { useState } from "react";
import { HandCoins, Loader2, Plus } from "lucide-react";
import { toast } from "sonner";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  useDocumentPayments,
  useRegisterPayment,
  type PaymentInput,
} from "@/lib/api/queries/document-payments";
import { ClientApiError } from "@/lib/api/client";
import { formatDate, formatMoney } from "@/lib/format";

const METHOD_LABELS: Record<string, string> = {
  cash: "Efectivo",
  transfer: "Transferencia",
  card: "Tarjeta",
  other: "Otro",
};

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

export function PaymentsCard({ documentId }: { documentId: number }) {
  const paymentsQ = useDocumentPayments(documentId);
  const register = useRegisterPayment(documentId);

  const [open, setOpen] = useState(false);
  const [amount, setAmount] = useState("");
  const [method, setMethod] =
    useState<PaymentInput["payment_method"]>("cash");
  const [notes, setNotes] = useState("");

  if (paymentsQ.isLoading) {
    return (
      <Card>
        <CardContent className="flex justify-center py-8">
          <Loader2 className="size-5 animate-spin text-muted-foreground" />
        </CardContent>
      </Card>
    );
  }

  if (!paymentsQ.data) return null;

  const { payments, document: totals } = paymentsQ.data;
  const balance = Number(totals.balance);
  const isPaid = balance <= 0;

  const onRegister = () => {
    register.mutate(
      {
        amount: Number(amount),
        payment_method: method,
        notes: notes || undefined,
      },
      {
        onSuccess: (res) => {
          const newBalance = Number(res.data.document.balance);
          toast.success(
            newBalance <= 0
              ? "Cobro registrado — comprobante pagado por completo."
              : `Cobro registrado. Saldo pendiente: ${formatMoney(newBalance)}`,
          );
          setOpen(false);
          setAmount("");
          setNotes("");
        },
        onError: (e) => toast.error(errMessage(e)),
      },
    );
  };

  return (
    <Card>
      <CardHeader className="flex-row items-center justify-between">
        <CardTitle className="flex items-center gap-2 text-sm">
          <HandCoins className="size-4 text-primary" />
          Cobros
        </CardTitle>
        <Badge
          variant="outline"
          className={
            isPaid
              ? "border-transparent bg-success/10 text-success"
              : "border-transparent bg-warning/10 text-warning"
          }
        >
          {isPaid ? "Pagado" : `Pendiente ${formatMoney(balance)}`}
        </Badge>
      </CardHeader>
      <CardContent className="space-y-3">
        {payments.length > 0 && (
          <ul className="space-y-2">
            {payments.map((p) => (
              <li
                key={p.id}
                className="flex items-baseline justify-between gap-2 text-sm"
              >
                <span className="text-muted-foreground">
                  {formatDate(p.payment_date)} ·{" "}
                  {METHOD_LABELS[p.payment_method] ?? p.payment_method}
                </span>
                <span className="font-medium tabular-nums">
                  {formatMoney(Number(p.amount))}
                </span>
              </li>
            ))}
          </ul>
        )}

        <div className="flex items-baseline justify-between border-t border-border pt-2 text-sm">
          <span className="text-muted-foreground">Cobrado</span>
          <span className="font-medium tabular-nums">
            {formatMoney(Number(totals.paid_amount))} /{" "}
            {formatMoney(Number(totals.total))}
          </span>
        </div>

        {!isPaid && (
          <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
              <Button variant="outline" size="sm" className="w-full">
                <Plus className="size-4" /> Registrar cobro
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Registrar cobro</DialogTitle>
              </DialogHeader>
              <div className="space-y-3">
                <div className="space-y-2">
                  <Label htmlFor="pay-amount">
                    Monto (pendiente: {formatMoney(balance)})
                  </Label>
                  <Input
                    id="pay-amount"
                    type="number"
                    min="0.01"
                    step="0.01"
                    max={balance}
                    value={amount}
                    onChange={(e) => setAmount(e.target.value)}
                    placeholder={totals.balance}
                  />
                  <button
                    type="button"
                    className="text-xs text-primary hover:underline"
                    onClick={() => setAmount(totals.balance)}
                  >
                    Cobrar todo el saldo
                  </button>
                </div>
                <div className="space-y-2">
                  <Label>Forma de pago</Label>
                  <Select
                    value={method}
                    onValueChange={(v) =>
                      setMethod(v as PaymentInput["payment_method"])
                    }
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="cash">Efectivo</SelectItem>
                      <SelectItem value="transfer">Transferencia</SelectItem>
                      <SelectItem value="card">Tarjeta</SelectItem>
                      <SelectItem value="other">Otro</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="pay-notes">Notas (opcional)</Label>
                  <Input
                    id="pay-notes"
                    value={notes}
                    onChange={(e) => setNotes(e.target.value)}
                    placeholder="Ref. de transferencia, etc."
                  />
                </div>
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={() => setOpen(false)}>
                  Cancelar
                </Button>
                <Button
                  onClick={onRegister}
                  disabled={
                    register.isPending ||
                    !amount ||
                    Number(amount) <= 0 ||
                    Number(amount) > balance + 0.001
                  }
                >
                  {register.isPending && (
                    <Loader2 className="size-4 animate-spin" />
                  )}
                  Registrar
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        )}
      </CardContent>
    </Card>
  );
}
