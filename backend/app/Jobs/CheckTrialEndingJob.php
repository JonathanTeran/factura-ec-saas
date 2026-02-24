<?php

namespace App\Jobs;

use App\Models\Tenant\Tenant;
use App\Notifications\TrialEndingNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckTrialEndingJob implements ShouldQueue
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
        Log::info('Checking tenants with ending trials');

        // Get tenants with trials ending in 7, 3, and 1 days
        $notificationDays = [7, 3, 1];

        foreach ($notificationDays as $days) {
            $date = now()->addDays($days)->startOfDay();

            $tenants = Tenant::query()
                ->where('status', 'trial')
                ->whereDate('trial_ends_at', $date)
                ->with('owner')
                ->get();

            foreach ($tenants as $tenant) {
                if ($tenant->owner) {
                    // Check if we haven't already sent this notification
                    $alreadySent = $tenant->owner->notifications()
                        ->where('type', TrialEndingNotification::class)
                        ->whereJsonContains('data->days_remaining', $days)
                        ->whereDate('created_at', today())
                        ->exists();

                    if (!$alreadySent) {
                        $tenant->owner->notify(new TrialEndingNotification($tenant, $days));
                        Log::info("Sent trial ending notification to tenant {$tenant->id} ({$days} days)");
                    }
                }
            }
        }

        // Handle expired trials
        $expiredTenants = Tenant::query()
            ->where('status', 'trial')
            ->where('trial_ends_at', '<', now())
            ->get();

        foreach ($expiredTenants as $tenant) {
            $tenant->update(['status' => 'expired']);
            Log::info("Tenant {$tenant->id} trial expired, status updated to expired");
        }

        Log::info('Finished checking trial endings');
    }
}
