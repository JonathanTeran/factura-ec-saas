<?php

namespace App\Jobs;

use App\Models\Tenant\Certificate;
use App\Notifications\CertificateExpiringNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        Log::info('Checking certificates nearing expiry');

        // Notification days: 30, 15, 7, 3, 1
        $notificationDays = [30, 15, 7, 3, 1];

        foreach ($notificationDays as $days) {
            $date = now()->addDays($days)->startOfDay();

            $certificates = Certificate::query()
                ->where('is_active', true)
                ->whereDate('expires_at', $date)
                ->with(['company.tenant.owner'])
                ->get();

            foreach ($certificates as $certificate) {
                $owner = $certificate->company->tenant->owner ?? null;

                if ($owner) {
                    // Check if we haven't already sent this notification
                    $alreadySent = $owner->notifications()
                        ->where('type', CertificateExpiringNotification::class)
                        ->whereJsonContains('data->certificate_id', $certificate->id)
                        ->whereJsonContains('data->days_until_expiry', $days)
                        ->whereDate('created_at', today())
                        ->exists();

                    if (!$alreadySent) {
                        $owner->notify(new CertificateExpiringNotification($certificate, $days));
                        Log::info("Sent certificate expiry notification for certificate {$certificate->id} ({$days} days)");
                    }
                }
            }
        }

        // Deactivate expired certificates
        $expiredCertificates = Certificate::query()
            ->where('is_active', true)
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expiredCertificates as $certificate) {
            $certificate->update(['is_active' => false]);
            Log::info("Certificate {$certificate->id} expired, deactivated");
        }

        Log::info('Finished checking certificate expiry');
    }
}
