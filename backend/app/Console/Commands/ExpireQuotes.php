<?php

namespace App\Console\Commands;

use App\Services\Quote\QuoteService;
use Illuminate\Console\Command;

class ExpireQuotes extends Command
{
    protected $signature = 'quotes:expire';

    protected $description = 'Marca como vencidas las cotizaciones en borrador/enviadas cuya fecha de validez ya pasó';

    public function handle(QuoteService $quotes): int
    {
        $count = $quotes->markExpired();

        $this->info("Cotizaciones vencidas: {$count}");

        return self::SUCCESS;
    }
}
