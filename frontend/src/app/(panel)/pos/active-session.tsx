"use client";

import { useEffect, useMemo, useState } from "react";
import { Loader2, Plus, Trash2, ShoppingCart, Search, Power } from "lucide-react";
import { toast } from "sonner";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useProducts } from "@/lib/api/queries/products";
import { useCustomers } from "@/lib/api/queries/customers";
import {
  useClosePosSession,
  useCreatePosTransaction,
} from "@/lib/api/queries/pos";
import { ClientApiError } from "@/lib/api/client";
import { formatMoney } from "@/lib/format";
import type { PosSession, Product } from "@/lib/api/types";
import { EntityCombobox } from "@/components/forms/entity-combobox";

type CartItem = {
  product_id: number;
  code: string;
  name: string;
  quantity: number;
  unit_price: number;
  tax_rate: number;
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

// Límite del SRI para ventas a consumidor final (config sri.consumidor_final.max_amount)
const CONSUMIDOR_FINAL_MAX = 50;

export function ActiveSession({ session }: { session: PosSession }) {
  const [search, setSearch] = useState("");
  const [cart, setCart] = useState<CartItem[]>([]);
  const [customerId, setCustomerId] = useState<number | null>(null);
  const [customerSearch, setCustomerSearch] = useState("");
  const [paymentMethod, setPaymentMethod] = useState<
    "cash" | "card" | "transfer" | "other"
  >("cash");
  const [amountReceived, setAmountReceived] = useState("");
  const [closeDialogOpen, setCloseDialogOpen] = useState(false);
  const [closingAmount, setClosingAmount] = useState("");
  const [closingNotes, setClosingNotes] = useState("");

  const productsQ = useProducts({ search: search || undefined, per_page: 24 });
  const customersQ = useCustomers({ search: customerSearch || undefined, per_page: 20 });

  // Productos usados recientemente en esta caja: primeros en la grilla
  const RECENTS_KEY = "pos_recent_products";
  const [recentIds, setRecentIds] = useState<number[]>([]);
  useEffect(() => {
    try {
      const raw = localStorage.getItem(RECENTS_KEY);
      if (raw) setRecentIds(JSON.parse(raw) as number[]);
    } catch {
      // localStorage no disponible: la grilla mantiene el orden del servidor
    }
  }, []);

  const rememberProduct = (id: number) => {
    setRecentIds((prev) => {
      const next = [id, ...prev.filter((x) => x !== id)].slice(0, 12);
      try {
        localStorage.setItem(RECENTS_KEY, JSON.stringify(next));
      } catch {
        // ignorar
      }
      return next;
    });
  };

  const products = useMemo(() => {
    const list = productsQ.data?.data ?? [];
    if (search || recentIds.length === 0) return list;
    const rank = new Map(recentIds.map((id, i) => [id, i]));
    return [...list].sort(
      (a, b) => (rank.get(a.id) ?? Infinity) - (rank.get(b.id) ?? Infinity),
    );
  }, [productsQ.data, search, recentIds]);
  const createTx = useCreatePosTransaction(session.id);
  const closeSession = useClosePosSession(session.id);

  const totals = useMemo(() => {
    let subtotal = 0;
    let tax = 0;
    for (const it of cart) {
      const lineSub = it.quantity * it.unit_price;
      subtotal += lineSub;
      tax += lineSub * (it.tax_rate / 100);
    }
    return {
      subtotal: round(subtotal),
      tax: round(tax),
      total: round(subtotal + tax),
    };
  }, [cart]);

  const addProduct = (p: Product) => {
    rememberProduct(p.id);
    setCart((prev) => {
      const existing = prev.find((it) => it.product_id === p.id);
      if (existing) {
        return prev.map((it) =>
          it.product_id === p.id ? { ...it, quantity: it.quantity + 1 } : it,
        );
      }
      return [
        ...prev,
        {
          product_id: p.id,
          code: p.code,
          name: p.name,
          quantity: 1,
          unit_price: p.unit_price,
          tax_rate: p.tax_rate ?? 15,
        },
      ];
    });
  };

  const updateQty = (productId: number, qty: number) => {
    if (qty <= 0) {
      setCart((prev) => prev.filter((it) => it.product_id !== productId));
      return;
    }
    setCart((prev) =>
      prev.map((it) =>
        it.product_id === productId ? { ...it, quantity: qty } : it,
      ),
    );
  };

  const removeItem = (productId: number) => {
    setCart((prev) => prev.filter((it) => it.product_id !== productId));
  };

  const onCharge = () => {
    if (cart.length === 0) {
      toast.error("Agrega productos al carrito.");
      return;
    }
    createTx.mutate(
      {
        customer_id: customerId,
        payment_method: paymentMethod,
        amount_received: amountReceived ? Number(amountReceived) : undefined,
        items: cart.map((it) => ({
          product_id: it.product_id,
          quantity: it.quantity,
          unit_price: it.unit_price,
          tax_rate: it.tax_rate,
        })),
      },
      {
        onSuccess: () => {
          toast.success(`Venta registrada · ${formatMoney(totals.total)}`);
          setCart([]);
          setCustomerId(null);
          setAmountReceived("");
        },
        onError: (e) => toast.error(errMessage(e)),
      },
    );
  };

  const onClose = () => {
    closeSession.mutate(
      {
        closing_amount: Number(closingAmount) || 0,
        closing_notes: closingNotes || undefined,
      },
      {
        onSuccess: () => {
          toast.success("Caja cerrada");
          setCloseDialogOpen(false);
        },
        onError: (e) => toast.error(errMessage(e)),
      },
    );
  };

  return (
    <div className="grid h-full grid-cols-1 lg:grid-cols-[1fr_400px] gap-4 p-4">
      <Card className="flex flex-col overflow-hidden">
        <CardHeader className="flex-row items-center justify-between border-b">
          <div>
            <CardTitle className="text-base">
              Sesión #{session.id} · Caja abierta
            </CardTitle>
            {session.branch && (
              <p className="text-xs text-muted-foreground mt-1">
                {session.branch.code} · {session.branch.name}
                {session.emission_point ? ` · ${session.emission_point.code}` : ""}
              </p>
            )}
          </div>
          <Dialog open={closeDialogOpen} onOpenChange={setCloseDialogOpen}>
            <DialogTrigger asChild>
              <Button variant="destructive" size="sm">
                <Power className="size-4" /> Cerrar caja
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Cerrar caja</DialogTitle>
                <DialogDescription>
                  Cuenta el efectivo final. Si hay diferencia, anótala en las notas.
                </DialogDescription>
              </DialogHeader>
              <div className="space-y-3">
                <div className="space-y-2">
                  <Label htmlFor="closing">Efectivo final</Label>
                  <Input
                    id="closing"
                    type="number"
                    min="0"
                    step="0.01"
                    value={closingAmount}
                    onChange={(e) => setClosingAmount(e.target.value)}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="closing-notes">Notas (opcional)</Label>
                  <Input
                    id="closing-notes"
                    value={closingNotes}
                    onChange={(e) => setClosingNotes(e.target.value)}
                  />
                </div>
              </div>
              <DialogFooter>
                <Button
                  variant="outline"
                  onClick={() => setCloseDialogOpen(false)}
                >
                  Cancelar
                </Button>
                <Button
                  variant="destructive"
                  disabled={closeSession.isPending || !closingAmount}
                  onClick={onClose}
                >
                  {closeSession.isPending && (
                    <Loader2 className="size-4 animate-spin" />
                  )}
                  Cerrar caja
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        </CardHeader>
        <CardContent className="flex-1 overflow-y-auto p-4 space-y-3">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
            <Input
              autoFocus
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Buscar producto..."
              className="pl-9"
            />
          </div>
          {productsQ.isLoading ? (
            <div className="flex justify-center py-12">
              <Loader2 className="size-5 animate-spin text-muted-foreground" />
            </div>
          ) : (
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
              {products.map((p) => (
                <button
                  key={p.id}
                  type="button"
                  onClick={() => addProduct(p)}
                  className="rounded-lg border bg-card p-3 text-left hover:bg-accent transition-colors"
                >
                  <div className="text-xs font-mono text-muted-foreground">
                    {p.code}
                  </div>
                  <div className="font-medium text-sm line-clamp-2 min-h-10">
                    {p.name}
                  </div>
                  <div className="mt-2 flex items-center justify-between">
                    <span className="text-sm font-semibold">
                      {formatMoney(p.unit_price)}
                    </span>
                    {p.track_inventory && (
                      <Badge
                        variant={(p.stock ?? 0) > 0 ? "secondary" : "destructive"}
                        className="text-[10px]"
                      >
                        {p.stock ?? 0}
                      </Badge>
                    )}
                  </div>
                </button>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      <Card className="flex flex-col overflow-hidden">
        <CardHeader className="border-b">
          <CardTitle className="text-base flex items-center gap-2">
            <ShoppingCart className="size-4" />
            Carrito · {cart.length} {cart.length === 1 ? "ítem" : "ítems"}
          </CardTitle>
        </CardHeader>
        <CardContent className="flex-1 overflow-y-auto p-4 space-y-2">
          {cart.length === 0 ? (
            <p className="text-sm text-muted-foreground py-8 text-center">
              Carrito vacío. Toca un producto para agregarlo.
            </p>
          ) : (
            cart.map((it) => (
              <div
                key={it.product_id}
                className="flex items-center gap-2 rounded-md border p-2"
              >
                <div className="flex-1 min-w-0">
                  <div className="text-sm font-medium truncate">{it.name}</div>
                  <div className="text-xs text-muted-foreground">
                    {formatMoney(it.unit_price)} · IVA {it.tax_rate}%
                  </div>
                </div>
                <div className="flex items-center gap-1">
                  <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    className="size-7"
                    onClick={() => updateQty(it.product_id, it.quantity - 1)}
                  >
                    -
                  </Button>
                  <span className="w-8 text-center text-sm">{it.quantity}</span>
                  <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    className="size-7"
                    onClick={() => updateQty(it.product_id, it.quantity + 1)}
                  >
                    <Plus className="size-3" />
                  </Button>
                </div>
                <Button
                  type="button"
                  variant="ghost"
                  size="icon"
                  className="size-7"
                  onClick={() => removeItem(it.product_id)}
                >
                  <Trash2 className="size-3" />
                </Button>
              </div>
            ))
          )}
        </CardContent>
        <div className="border-t p-4 space-y-3">
          <div className="space-y-2">
            <Label>Cliente</Label>
            <EntityCombobox
              value={customerId}
              onChange={(v) => setCustomerId(typeof v === "number" ? v : null)}
              options={
                customersQ.data?.data.map((c) => ({
                  value: c.id,
                  label: c.name,
                  description: c.identification_number,
                })) ?? []
              }
              isLoading={customersQ.isFetching}
              onSearch={setCustomerSearch}
              placeholder="Consumidor final"
              searchPlaceholder="Buscar cliente..."
            />
            <p className="text-xs text-muted-foreground">
              Sin cliente se emite a consumidor final (hasta $
              {CONSUMIDOR_FINAL_MAX} por venta).
            </p>
          </div>
          <div className="space-y-2">
            <Label>Forma de pago</Label>
            <Select
              value={paymentMethod}
              onValueChange={(v) =>
                setPaymentMethod(v as "cash" | "card" | "transfer" | "other")
              }
            >
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="cash">Efectivo</SelectItem>
                <SelectItem value="card">Tarjeta</SelectItem>
                <SelectItem value="transfer">Transferencia</SelectItem>
                <SelectItem value="other">Otro</SelectItem>
              </SelectContent>
            </Select>
          </div>
          {paymentMethod === "cash" && (
            <div className="space-y-2">
              <Label htmlFor="received">Recibido</Label>
              <Input
                id="received"
                type="number"
                min="0"
                step="0.01"
                value={amountReceived}
                onChange={(e) => setAmountReceived(e.target.value)}
                placeholder={String(totals.total)}
              />
            </div>
          )}
          <div className="space-y-1 text-sm border-t pt-3">
            <Row label="Subtotal" value={totals.subtotal} />
            <Row label="IVA" value={totals.tax} />
            <div className="flex items-center justify-between text-base font-semibold">
              <span>Total</span>
              <span>{formatMoney(totals.total)}</span>
            </div>
            {paymentMethod === "cash" && Number(amountReceived) > totals.total && (
              <div className="flex items-center justify-between text-sm text-emerald-600">
                <span>Cambio</span>
                <span>{formatMoney(Number(amountReceived) - totals.total)}</span>
              </div>
            )}
          </div>
          <Button
            className="w-full"
            size="lg"
            disabled={createTx.isPending || cart.length === 0}
            onClick={onCharge}
          >
            {createTx.isPending && <Loader2 className="size-4 animate-spin" />}
            Cobrar {formatMoney(totals.total)}
          </Button>
        </div>
      </Card>
    </div>
  );
}

function Row({ label, value }: { label: string; value: number }) {
  return (
    <div className="flex items-center justify-between text-muted-foreground">
      <span>{label}</span>
      <span>{formatMoney(value)}</span>
    </div>
  );
}

function round(n: number) {
  return Math.round(n * 100) / 100;
}
