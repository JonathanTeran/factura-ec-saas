<?php

namespace App\Services\Purchase;

use App\Enums\PurchaseStatus;
use App\Models\Tenant\Purchase;
use App\Models\Tenant\PurchaseItem;
use App\Models\Tenant\Supplier;
use App\Models\Tenant\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    private Tenant $tenant;

    public function forTenant(Tenant $tenant): self
    {
        $this->tenant = $tenant;
        return $this;
    }

    public function createPurchase(array $data, array $items): Purchase
    {
        return DB::transaction(function () use ($data, $items) {
            $purchase = Purchase::create([
                'tenant_id' => $this->tenant->id,
                ...$data,
            ]);

            foreach ($items as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'] - ($item['discount'] ?? 0);
                $taxValue = $subtotal * ($item['tax_rate'] ?? 15) / 100;

                PurchaseItem::create([
                    'tenant_id' => $this->tenant->id,
                    'purchase_id' => $purchase->id,
                    'main_code' => $item['main_code'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'subtotal' => $subtotal,
                    'tax_code' => $item['tax_code'] ?? '2',
                    'tax_percentage_code' => $item['tax_percentage_code'] ?? '4',
                    'tax_rate' => $item['tax_rate'] ?? 15,
                    'tax_value' => $taxValue,
                    'total' => $subtotal + $taxValue,
                    'product_id' => $item['product_id'] ?? null,
                ]);
            }

            $purchase->calculateTotals();

            // Actualizar stats del proveedor
            $purchase->supplier->updatePurchaseStats($purchase->fresh()->total);

            return $purchase->fresh()->load('items', 'supplier');
        });
    }

    public function updatePurchase(Purchase $purchase, array $data, array $items): Purchase
    {
        return DB::transaction(function () use ($purchase, $data, $items) {
            $oldTotal = $purchase->total;
            $purchase->update($data);

            // Recrear items
            $purchase->items()->delete();

            foreach ($items as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'] - ($item['discount'] ?? 0);
                $taxValue = $subtotal * ($item['tax_rate'] ?? 15) / 100;

                PurchaseItem::create([
                    'tenant_id' => $this->tenant->id,
                    'purchase_id' => $purchase->id,
                    'main_code' => $item['main_code'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'subtotal' => $subtotal,
                    'tax_code' => $item['tax_code'] ?? '2',
                    'tax_percentage_code' => $item['tax_percentage_code'] ?? '4',
                    'tax_rate' => $item['tax_rate'] ?? 15,
                    'tax_value' => $taxValue,
                    'total' => $subtotal + $taxValue,
                    'product_id' => $item['product_id'] ?? null,
                ]);
            }

            $purchase->calculateTotals();

            // Actualizar stats del proveedor
            $diff = $purchase->fresh()->total - $oldTotal;
            if ($diff != 0) {
                $purchase->supplier->increment('total_purchased', $diff);
            }

            return $purchase->fresh()->load('items', 'supplier');
        });
    }

    public function voidPurchase(Purchase $purchase): void
    {
        $purchase->update(['status' => PurchaseStatus::VOIDED]);
        $purchase->supplier->decrement('total_purchased', $purchase->total);
    }

    /**
     * Datos de compras para ATS.
     */
    public function getATSCompras(Carbon $from, Carbon $to): array
    {
        return Purchase::where('tenant_id', $this->tenant->id)
            ->where('status', '!=', PurchaseStatus::VOIDED)
            ->whereBetween('issue_date', [$from, $to])
            ->with('supplier')
            ->get()
            ->map(fn(Purchase $purchase) => [
                'codSustento' => '01', // Credito Tributario para IVA
                'tpIdProv' => $purchase->supplier->identification_type->value,
                'idProv' => $purchase->supplier->identification,
                'tipoComprobante' => $purchase->document_type,
                'parteRel' => 'NO',
                'fechaRegistro' => $purchase->issue_date->format('d/m/Y'),
                'establecimiento' => substr($purchase->supplier_document_number, 0, 3),
                'puntoEmision' => substr($purchase->supplier_document_number, 4, 3),
                'secuencial' => substr($purchase->supplier_document_number, 8),
                'fechaEmision' => $purchase->issue_date->format('d/m/Y'),
                'autorizacion' => $purchase->supplier_authorization,
                'baseNoGraIva' => $purchase->subtotal_no_tax,
                'baseImponible' => $purchase->subtotal_0,
                'baseImpGrav' => $purchase->subtotal_12 + $purchase->subtotal_15 + $purchase->subtotal_5,
                'baseImpExe' => 0,
                'montoIce' => 0,
                'montoIva' => $purchase->total_tax,
                'valRetBien10' => 0,
                'valRetServ20' => 0,
                'valorRetBienes' => 0,
                'valRetServ50' => 0,
                'valorRetServicios' => 0,
                'valRetServ100' => 0,
            ])
            ->toArray();
    }

    /**
     * Resumen de compras por periodo.
     */
    public function getPurchasesSummary(Carbon $from, Carbon $to): array
    {
        $purchases = Purchase::where('tenant_id', $this->tenant->id)
            ->where('status', '!=', PurchaseStatus::VOIDED)
            ->whereBetween('issue_date', [$from, $to]);

        return [
            'count' => $purchases->count(),
            'total' => (float) $purchases->sum('total'),
            'total_tax' => (float) $purchases->sum('total_tax'),
            'subtotal_0' => (float) $purchases->sum('subtotal_0'),
            'subtotal_12' => (float) $purchases->sum('subtotal_12'),
            'subtotal_15' => (float) $purchases->sum('subtotal_15'),
        ];
    }

    /**
     * Top proveedores por monto de compra.
     */
    public function getTopSuppliers(Carbon $from, Carbon $to, int $limit = 10): array
    {
        return Supplier::where('suppliers.tenant_id', $this->tenant->id)
            ->join('purchases', 'suppliers.id', '=', 'purchases.supplier_id')
            ->where('purchases.status', '!=', PurchaseStatus::VOIDED)
            ->whereBetween('purchases.issue_date', [$from, $to])
            ->select(
                'suppliers.id',
                'suppliers.business_name',
                'suppliers.identification',
                DB::raw('COUNT(purchases.id) as purchase_count'),
                DB::raw('SUM(purchases.total) as total_amount')
            )
            ->groupBy('suppliers.id', 'suppliers.business_name', 'suppliers.identification')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
