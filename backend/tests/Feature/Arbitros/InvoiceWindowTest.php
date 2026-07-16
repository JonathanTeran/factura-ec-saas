<?php

namespace Tests\Feature\Arbitros;

use App\Models\Arbitros\Championship;
use App\Models\Arbitros\OfficiatedMatch;
use App\Services\Arbitros\InvoiceWindow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Ventana de recepción FEF: la FEF recibe SOLO del día start al end de cada mes
 * (default 1–20). Dentro de la ventana se puede facturar cualquier pendiente
 * (de este mes o de meses anteriores); fuera de la ventana, no.
 */
class InvoiceWindowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function matchOn(string $date, ?Championship $ch = null): OfficiatedMatch
    {
        $ch ??= Championship::create(['name' => 'LIGA X', 'season' => '2026']);

        return new OfficiatedMatch([
            'championship_id' => $ch->id,
            'match_date' => $date,
            'role' => 'arbitro',
            'fee' => 30,
            'status' => 'pending',
        ]);
    }

    public function test_open_within_window_for_previous_month_match(): void
    {
        Carbon::setTestNow('2026-08-15'); // día 15, dentro de 1–20
        $this->assertTrue(app(InvoiceWindow::class)->evaluate($this->matchOn('2026-07-20'))['open']);
    }

    public function test_open_within_window_for_current_month_match(): void
    {
        // Antes se bloqueaba por ser "mes en curso"; ahora se permite dentro de ventana.
        Carbon::setTestNow('2026-08-15');
        $this->assertTrue(app(InvoiceWindow::class)->evaluate($this->matchOn('2026-08-03'))['open']);
    }

    public function test_closed_after_end_day(): void
    {
        Carbon::setTestNow('2026-08-25'); // día 25, fuera de 1–20
        $eval = app(InvoiceWindow::class)->evaluate($this->matchOn('2026-07-10'));
        $this->assertFalse($eval['open']);
        $this->assertNotNull($eval['reason']);
    }

    public function test_open_on_exact_start_and_end_days(): void
    {
        Carbon::setTestNow('2026-08-01');
        $this->assertTrue(app(InvoiceWindow::class)->canInvoice($this->matchOn('2026-07-10')));

        Carbon::setTestNow('2026-08-20');
        $this->assertTrue(app(InvoiceWindow::class)->canInvoice($this->matchOn('2026-07-10')));
    }

    public function test_per_championship_window_override(): void
    {
        $ch = Championship::create([
            'name' => 'LIGA ESPECIAL',
            'season' => '2026',
            'invoice_window_start_day' => 1,
            'invoice_window_end_day' => 5,
        ]);

        Carbon::setTestNow('2026-08-10'); // fuera del override (1–5)
        $this->assertFalse(app(InvoiceWindow::class)->canInvoice($this->matchOn('2026-07-10', $ch)));

        Carbon::setTestNow('2026-08-04'); // dentro del override
        $this->assertTrue(app(InvoiceWindow::class)->canInvoice($this->matchOn('2026-07-10', $ch)));
    }
}
