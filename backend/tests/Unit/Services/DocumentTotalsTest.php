<?php

namespace Tests\Unit\Services;

use App\Services\Document\DocumentTotals;
use PHPUnit\Framework\TestCase;

class DocumentTotalsTest extends TestCase
{
    public function test_groups_subtotals_by_sri_code_and_computes_totals(): void
    {
        $result = DocumentTotals::fromItems([
            ['description' => 'Gravado 15', 'quantity' => 2, 'unit_price' => 100, 'discount' => 10, 'tax_rate' => 15],
            ['description' => 'Tarifa 0', 'quantity' => 1, 'unit_price' => 50, 'tax_percentage_code' => '0'],
            ['description' => 'No objeto', 'quantity' => 1, 'unit_price' => 20, 'tax_percentage_code' => '6'],
            ['description' => 'Gravado 5', 'quantity' => 1, 'unit_price' => 40, 'tax_percentage_code' => '5'],
        ]);

        $totals = $result['totals'];

        $this->assertSame(190.0, $totals['subtotal_15']);
        $this->assertSame(50.0, $totals['subtotal_0']);
        $this->assertSame(20.0, $totals['subtotal_no_tax']);
        $this->assertSame(40.0, $totals['subtotal_5']);
        $this->assertSame(10.0, $totals['total_discount']);
        $this->assertSame(30.5, $totals['total_tax']); // 28.50 + 2.00
        $this->assertSame(330.5, $totals['total']);

        $first = $result['items'][0];
        $this->assertSame('4', $first['tax_percentage_code']);
        $this->assertSame(15.0, $first['tax_rate']);
        $this->assertSame(190.0, $first['subtotal']);
        $this->assertSame(28.5, $first['tax_value']);
        $this->assertSame('ITEM-1', $first['main_code']);
    }

    public function test_derives_rate_from_code_and_code_from_rate(): void
    {
        $this->assertSame(['4', 15.0], DocumentTotals::resolveTax(['tax_rate' => 15]));
        $this->assertSame(['2', 12.0], DocumentTotals::resolveTax(['tax_percentage_code' => '2']));
        $this->assertSame(['0', 0.0], DocumentTotals::resolveTax(['tax_rate' => 0]));
        $this->assertSame(['6', 0.0], DocumentTotals::resolveTax(['tax_percentage_code' => '6', 'tax_rate' => 0]));
        // Sin datos: IVA vigente (15%), no el 12% histórico.
        $this->assertSame(['4', 15.0], DocumentTotals::resolveTax([]));
    }

    public function test_keeps_precomputed_line_values_when_provided(): void
    {
        $line = DocumentTotals::normalizeItem([
            'main_code' => 'ABC',
            'description' => 'x',
            'quantity' => 1,
            'unit_price' => 100,
            'subtotal' => 100,
            'tax_base' => 100,
            'tax_value' => 12,
            'tax_rate' => 12,
            'tax_percentage_code' => '2',
        ]);

        $this->assertSame('ABC', $line['main_code']);
        $this->assertSame(12.0, $line['tax_value']);
        $this->assertSame('2', $line['tax_percentage_code']);
    }
}
