<?php

namespace App\Services\Pos;

use App\Enums\PosSessionStatus;
use App\Exceptions\FeatureNotAvailableException;
use App\Models\Tenant\Customer;
use App\Models\Tenant\PosSession;
use App\Models\Tenant\PosTransaction;
use App\Models\Tenant\PosTransactionItem;
use App\Models\Tenant\Product;
use App\Models\Tenant\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosService
{
    private Tenant $tenant;

    public function forTenant(Tenant $tenant): self
    {
        if (!$tenant->hasFeature('pos')) {
            throw new FeatureNotAvailableException('pos');
        }

        $this->tenant = $tenant;
        return $this;
    }

    /**
     * Abrir una nueva sesion de caja.
     */
    public function openSession(array $data): PosSession
    {
        // Verificar que no haya sesion abierta para el mismo punto de emision
        $existingOpen = PosSession::where('tenant_id', $this->tenant->id)
            ->where('emission_point_id', $data['emission_point_id'])
            ->open()
            ->first();

        if ($existingOpen) {
            throw new \RuntimeException('Ya existe una sesion de caja abierta para este punto de emision.');
        }

        return PosSession::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $data['company_id'],
            'branch_id' => $data['branch_id'],
            'emission_point_id' => $data['emission_point_id'],
            'opened_by' => auth()->id(),
            'opening_amount' => $data['opening_amount'] ?? 0,
            'status' => PosSessionStatus::OPEN,
            'opened_at' => now(),
        ]);
    }

    /**
     * Cerrar sesion de caja.
     */
    public function closeSession(PosSession $session, float $closingAmount, ?string $notes = null): PosSession
    {
        if (!$session->isOpen()) {
            throw new \RuntimeException('La sesion de caja ya esta cerrada.');
        }

        $session->close($closingAmount, $notes);

        return $session->fresh();
    }

    /**
     * Crear una transaccion POS (venta rapida).
     */
    public function createTransaction(PosSession $session, array $data, array $items): PosTransaction
    {
        if (!$session->isOpen()) {
            throw new \RuntimeException('La sesion de caja no esta abierta.');
        }

        return DB::transaction(function () use ($session, $data, $items) {
            $subtotal = 0;
            $totalTax = 0;
            $totalDiscount = 0;

            // Calcular totales
            $processedItems = [];
            foreach ($items as $item) {
                $product = isset($item['product_id'])
                    ? Product::where('tenant_id', $this->tenant->id)->find($item['product_id'])
                    : null;

                $qty = $item['quantity'];
                $price = $item['unit_price'] ?? ($product?->unit_price ?? 0);
                $disc = $item['discount'] ?? 0;
                $taxRate = $item['tax_rate'] ?? 15;

                $itemSubtotal = ($qty * $price) - $disc;
                $itemTax = $itemSubtotal * $taxRate / 100;

                $subtotal += $itemSubtotal;
                $totalTax += $itemTax;
                $totalDiscount += $disc;

                $processedItems[] = [
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'] ?? $product?->name ?? 'Producto',
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'discount' => $disc,
                    'tax_rate' => $taxRate,
                    'tax_value' => $itemTax,
                    'total' => $itemSubtotal + $itemTax,
                ];

                // Descontar inventario si aplica
                if ($product && $product->track_inventory) {
                    $product->decrement('current_stock', $qty);
                }
            }

            $total = $subtotal + $totalTax;
            $amountReceived = $data['amount_received'] ?? $total;
            $change = max(0, $amountReceived - $total);

            $transaction = PosTransaction::create([
                'tenant_id' => $this->tenant->id,
                'pos_session_id' => $session->id,
                'customer_id' => $data['customer_id'] ?? null,
                'transaction_number' => $this->generateTransactionNumber(),
                'payment_method' => $data['payment_method'] ?? 'cash',
                'subtotal' => $subtotal,
                'tax' => $totalTax,
                'discount' => $totalDiscount,
                'total' => $total,
                'amount_received' => $amountReceived,
                'change_amount' => $change,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
            ]);

            // Crear items de la transaccion
            foreach ($processedItems as $item) {
                PosTransactionItem::create([
                    'pos_transaction_id' => $transaction->id,
                    ...$item,
                ]);
            }

            // Actualizar totales de la sesion
            $session->addTransaction($data['payment_method'] ?? 'cash', $total);

            return $transaction->load('items');
        });
    }

    /**
     * Anular una transaccion POS.
     */
    public function voidTransaction(PosTransaction $transaction): void
    {
        if ($transaction->isVoided()) {
            throw new \RuntimeException('La transaccion ya esta anulada.');
        }

        DB::transaction(function () use ($transaction) {
            // Revertir inventario
            foreach ($transaction->items as $item) {
                if ($item->product && $item->product->track_inventory) {
                    $item->product->increment('current_stock', $item->quantity);
                }
            }

            // Actualizar totales de la sesion
            $session = $transaction->session;
            $session->decrement('total_transactions');
            $session->decrement('total_sales', $transaction->total);

            match ($transaction->payment_method) {
                'cash' => $session->decrement('total_cash', $transaction->total),
                'card' => $session->decrement('total_card', $transaction->total),
                'transfer' => $session->decrement('total_transfer', $transaction->total),
                default => $session->decrement('total_other', $transaction->total),
            };

            $transaction->update(['status' => 'voided']);
        });
    }

    /**
     * Obtener sesion activa para el usuario actual.
     */
    public function getActiveSession(): ?PosSession
    {
        return PosSession::where('tenant_id', $this->tenant->id)
            ->where('opened_by', auth()->id())
            ->open()
            ->with(['branch', 'emissionPoint'])
            ->first();
    }

    private function generateTransactionNumber(): string
    {
        return 'POS-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }
}
