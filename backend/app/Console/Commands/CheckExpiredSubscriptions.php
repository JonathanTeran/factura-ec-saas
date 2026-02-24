<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\Billing\Subscription;
use App\Notifications\SubscriptionCancelledNotification;
use Illuminate\Console\Command;

class CheckExpiredSubscriptions extends Command
{
    protected $signature = 'billing:check-expired';
    protected $description = 'Marca como expiradas las suscripciones activas cuya fecha de fin ya paso y notifica al owner';

    public function handle(): int
    {
        $this->info('Buscando suscripciones activas expiradas...');

        $subscriptions = Subscription::query()
            ->where('status', SubscriptionStatus::ACTIVE)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->with(['tenant.owner', 'plan'])
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No se encontraron suscripciones expiradas.');
            return self::SUCCESS;
        }

        $processed = 0;

        foreach ($subscriptions as $subscription) {
            $subscription->markAsExpired();

            $tenant = $subscription->tenant;

            if ($tenant && $tenant->owner) {
                $tenant->owner->notify(new SubscriptionCancelledNotification($subscription));
                $this->line("Suscripcion {$subscription->id} expirada - Notificado a {$tenant->owner->email}");
            } else {
                $this->warn("Suscripcion {$subscription->id} expirada - Sin owner para notificar");
            }

            $processed++;
        }

        $this->info("Se procesaron {$processed} suscripciones expiradas.");

        return self::SUCCESS;
    }
}
