<?php

namespace App\Jobs;

use App\Models\Tenant\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ResetMonthlyDocumentCountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        Log::info('Resetting monthly document counts for all tenants');

        $updated = Tenant::query()
            ->where('documents_month_reset_at', '<', now()->startOfMonth())
            ->update([
                'documents_this_month' => 0,
                'documents_month_reset_at' => now()->startOfMonth(),
            ]);

        Log::info("Reset document counts for {$updated} tenants");
    }
}
