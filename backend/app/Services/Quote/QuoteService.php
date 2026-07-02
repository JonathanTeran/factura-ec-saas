<?php

namespace App\Services\Quote;

use App\Enums\QuoteStatus;
use App\Models\Tenant\Quote;
use App\Models\Tenant\QuoteItem;
use Illuminate\Support\Facades\DB;

class QuoteService
{
    public function generateQuoteNumber(int $tenantId): string
    {
        $count = Quote::withTrashed()->where('tenant_id', $tenantId)->count() + 1;
        return 'COT-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }

    public function create(array $data, array $items): Quote
    {
        return DB::transaction(function () use ($data, $items) {
            $quote = Quote::create($data);
            $this->syncItems($quote, $items);
            $this->recalculateTotals($quote);
            return $quote->fresh(['items']);
        });
    }

    public function update(Quote $quote, array $data, array $items): Quote
    {
        return DB::transaction(function () use ($quote, $data, $items) {
            $quote->update($data);
            $this->syncItems($quote, $items);
            $this->recalculateTotals($quote);
            return $quote->fresh(['items']);
        });
    }

    public function syncItems(Quote $quote, array $items): void
    {
        $quote->items()->delete();
        foreach ($items as $i => $item) {
            $subtotal = round(($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0) - ($item['discount'] ?? 0), 2);
            $taxValue = round($subtotal * (($item['tax_rate'] ?? 0) / 100), 2);
            QuoteItem::create([
                'quote_id'    => $quote->id,
                'product_id'  => $item['product_id'] ?? null,
                'description' => $item['description'],
                'quantity'    => $item['quantity'] ?? 1,
                'unit_price'  => $item['unit_price'] ?? 0,
                'discount'    => $item['discount'] ?? 0,
                'tax_rate'    => $item['tax_rate'] ?? 15,
                'subtotal'    => $subtotal,
                'tax_value'   => $taxValue,
                'total'       => $subtotal + $taxValue,
                'sort_order'  => $i,
            ]);
        }
    }

    public function recalculateTotals(Quote $quote): void
    {
        $quote->load('items');
        $quote->update([
            'subtotal'       => $quote->items->sum('subtotal'),
            'total_discount' => $quote->items->sum('discount'),
            'total_tax'      => $quote->items->sum('tax_value'),
            'total'          => $quote->items->sum('total'),
        ]);
    }

    public function markExpired(): int
    {
        return Quote::whereIn('status', [QuoteStatus::DRAFT->value, QuoteStatus::SENT->value])
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now()->toDateString())
            ->update(['status' => QuoteStatus::EXPIRED->value]);
    }
}
