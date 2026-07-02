<?php

namespace App\Console\Commands;

use App\Models\Tenant\Company;
use App\Notifications\SignatureExpiringNotification;
use Illuminate\Console\Command;

class SendSignatureExpiryReminders extends Command
{
    protected $signature = 'sri:send-signature-expiry-reminders';
    protected $description = 'Notifica a los tenants cuya firma electrónica vence en 30, 15 o 7 días';

    /** Días de anticipación en los que se envía recordatorio. */
    private const REMINDER_DAYS = [30, 15, 7];

    public function handle(): int
    {
        $sent = 0;

        foreach (self::REMINDER_DAYS as $days) {
            $companies = Company::query()
                ->withoutGlobalScopes()
                ->where('is_active', true)
                ->whereNotNull('signature_expires_at')
                ->whereDate('signature_expires_at', now()->addDays($days)->toDateString())
                ->with('tenant.owner')
                ->get();

            foreach ($companies as $company) {
                $owner = $company->tenant?->owner;

                if (! $owner) {
                    $this->warn("Empresa {$company->id}: tenant u owner no encontrado, omitiendo.");
                    continue;
                }

                $owner->notify(new SignatureExpiringNotification($company, $days));
                $this->line("Recordatorio ({$days} días) enviado a {$owner->email} ({$company->business_name})");
                $sent++;
            }
        }

        $this->info("Se enviaron {$sent} recordatorios de caducidad de firma.");

        return self::SUCCESS;
    }
}
