<?php

namespace App\Console\Commands;

use App\Services\Portal\CustomerPortalService;
use Illuminate\Console\Command;

class CleanExpiredPortalSessions extends Command
{
    protected $signature = 'portal:cleanup';
    protected $description = 'Limpia tokens y sesiones expiradas del portal de clientes';

    public function handle(CustomerPortalService $service): int
    {
        $count = $service->cleanupExpired();

        $this->info("Limpiados {$count} registros expirados del portal.");

        return self::SUCCESS;
    }
}
