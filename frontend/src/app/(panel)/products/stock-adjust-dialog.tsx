"use client";

import { useState } from "react";
import { ArrowDown, ArrowUp, Loader2, Settings2 } from "lucide-react";
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
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from "@/components/ui/tabs";
import {
  useAdjustStock,
  usePurchaseStock,
} from "@/lib/api/queries/inventory";
import { ClientApiError } from "@/lib/api/client";

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

export function StockAdjustDialog({
  productId,
  currentStock,
  trigger,
}: {
  productId: number;
  currentStock: number;
  trigger?: React.ReactNode;
}) {
  const [open, setOpen] = useState(false);
  const [tab, setTab] = useState("adjust");

  const adjust = useAdjustStock();
  const purchase = usePurchaseStock();

  const [newStock, setNewStock] = useState(String(currentStock));
  const [reason, setReason] = useState("");

  const [purchaseQty, setPurchaseQty] = useState("0");
  const [unitCost, setUnitCost] = useState("0");
  const [batch, setBatch] = useState("");
  const [expiry, setExpiry] = useState("");
  const [purchaseNotes, setPurchaseNotes] = useState("");

  const reset = () => {
    setNewStock(String(currentStock));
    setReason("");
    setPurchaseQty("0");
    setUnitCost("0");
    setBatch("");
    setExpiry("");
    setPurchaseNotes("");
  };

  const onAdjust = () => {
    adjust.mutate(
      {
        productId,
        new_stock: Number(newStock) || 0,
        reason,
      },
      {
        onSuccess: () => {
          toast.success("Inventario ajustado");
          setOpen(false);
          reset();
        },
        onError: (e) => toast.error(errMessage(e)),
      },
    );
  };

  const onPurchase = () => {
    purchase.mutate(
      {
        productId,
        quantity: Number(purchaseQty) || 0,
        unit_cost: Number(unitCost) || 0,
        batch_number: batch || undefined,
        expiry_date: expiry || undefined,
        notes: purchaseNotes || undefined,
      },
      {
        onSuccess: () => {
          toast.success("Compra registrada");
          setOpen(false);
          reset();
        },
        onError: (e) => toast.error(errMessage(e)),
      },
    );
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        {trigger ?? (
          <Button variant="outline">
            <Settings2 className="size-4" /> Ajustar stock
          </Button>
        )}
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Movimiento de inventario</DialogTitle>
          <DialogDescription>
            Stock actual: <strong>{currentStock}</strong> unidades.
          </DialogDescription>
        </DialogHeader>

        <Tabs value={tab} onValueChange={setTab}>
          <TabsList className="grid grid-cols-2">
            <TabsTrigger value="adjust">
              <ArrowDown className="size-4" /> Ajuste
            </TabsTrigger>
            <TabsTrigger value="purchase">
              <ArrowUp className="size-4" /> Compra
            </TabsTrigger>
          </TabsList>

          <TabsContent value="adjust" className="space-y-3 mt-3">
            <div className="space-y-2">
              <Label htmlFor="new-stock">Nuevo stock</Label>
              <Input
                id="new-stock"
                type="number"
                min="0"
                step="0.01"
                value={newStock}
                onChange={(e) => setNewStock(e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="reason">Motivo</Label>
              <Input
                id="reason"
                value={reason}
                onChange={(e) => setReason(e.target.value)}
                placeholder="Recuento físico, merma, etc."
                maxLength={500}
              />
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setOpen(false)}>
                Cancelar
              </Button>
              <Button
                disabled={adjust.isPending || !reason}
                onClick={onAdjust}
              >
                {adjust.isPending && <Loader2 className="size-4 animate-spin" />}
                Aplicar ajuste
              </Button>
            </DialogFooter>
          </TabsContent>

          <TabsContent value="purchase" className="space-y-3 mt-3">
            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-2">
                <Label htmlFor="qty">Cantidad</Label>
                <Input
                  id="qty"
                  type="number"
                  min="0"
                  step="0.01"
                  value={purchaseQty}
                  onChange={(e) => setPurchaseQty(e.target.value)}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="cost">Costo unitario</Label>
                <Input
                  id="cost"
                  type="number"
                  min="0"
                  step="0.01"
                  value={unitCost}
                  onChange={(e) => setUnitCost(e.target.value)}
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="batch">Lote (opcional)</Label>
              <Input
                id="batch"
                value={batch}
                onChange={(e) => setBatch(e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="expiry">Vencimiento (opcional)</Label>
              <Input
                id="expiry"
                type="date"
                value={expiry}
                onChange={(e) => setExpiry(e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="p-notes">Notas</Label>
              <Input
                id="p-notes"
                value={purchaseNotes}
                onChange={(e) => setPurchaseNotes(e.target.value)}
                maxLength={500}
              />
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setOpen(false)}>
                Cancelar
              </Button>
              <Button
                disabled={
                  purchase.isPending ||
                  Number(purchaseQty) <= 0 ||
                  Number(unitCost) < 0
                }
                onClick={onPurchase}
              >
                {purchase.isPending && (
                  <Loader2 className="size-4 animate-spin" />
                )}
                Registrar compra
              </Button>
            </DialogFooter>
          </TabsContent>
        </Tabs>
      </DialogContent>
    </Dialog>
  );
}
