<?php

namespace App\Jobs;

use App\Models\Tenant\Company;
use App\Notifications\CertificateExpiringNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Avisa a los dueños de tenant cuando la firma electrónica (.p12) de su
 * empresa está por vencer. La firma vive en Company::signature_expires_at —
 * no existe un modelo Certificate.
 */
class CheckCertificateExpiryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        Log::info('Checking signatures nearing expiry');

        // Días de aviso: 30, 15, 7, 3, 1
        $notificationDays = [30, 15, 7, 3, 1];

        foreach ($notificationDays as $days) {
            $date = now()->addDays($days)->startOfDay();

            $companies = Company::withoutGlobalScopes()
                ->whereNotNull('signature_path')
                ->whereDate('signature_expires_at', $date)
                ->with('tenant.owner')
                ->get();

            foreach ($companies as $company) {
                $owner = $company->tenant->owner ?? null;

                if (! $owner) {
                    continue;
                }

                // No repetir el mismo aviso el mismo día.
                $alreadySent = $owner->notifications()
                    ->where('type', CertificateExpiringNotification::class)
                    ->whereJsonContains('data->company_id', $company->id)
                    ->whereJsonContains('data->days_until_expiry', $days)
                    ->whereDate('created_at', today())
                    ->exists();

                if (! $alreadySent) {
                    $owner->notify(new CertificateExpiringNotification($company, $days));
                    Log::info("Sent signature expiry notification for company {$company->id} ({$days} days)");
                }
            }
        }

        Log::info('Finished checking signature expiry');
    }
}
