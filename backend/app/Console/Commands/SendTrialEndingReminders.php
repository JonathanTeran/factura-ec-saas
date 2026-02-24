<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\Billing\Subscription;
use App\Notifications\TrialEndingNotification;
use Illuminate\Console\Command;

class SendTrialEndingReminders extends Command
{
    protected $signature = 'billing:send-trial-reminders';
    protected $description = 'Envia recordatorios a tenants cuyo periodo de prueba termina en 3 dias';

    public function handle(): int
    {
        $this->info('Buscando suscripciones con periodo de prueba por vencer...');

        $subscriptions = Subscription::query()
            ->where('status', SubscriptionStatus::TRIALING)
            ->whereNotNull('trial_ends_at')
            ->whereDate('trial_ends_at', now()->addDays(3)->toDateString())
            ->with(['tenant.owner'])
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No se encontraron suscripciones con periodo de prueba por vencer.');
            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($subscriptions as $subscription) {
            $tenant = $subscription->tenant;

            if (!$tenant || !$tenant->owner) {
                $this->warn("Suscripcion {$subscription->id}: tenant u owner no encontrado, omitiendo.");
                continue;
            }

            $daysRemaining = (int) now()->diffInDays($subscription->trial_ends_at, false);

            $tenant->owner->notify(new TrialEndingNotification($tenant, $daysRemaining));

            $this->line("Recordatorio enviado a {$tenant->owner->email} (Tenant: {$tenant->name})");
            $sent++;
        }

        $this->info("Se enviaron {$sent} recordatorios de fin de periodo de prueba.");

        return self::SUCCESS;
    }
}
