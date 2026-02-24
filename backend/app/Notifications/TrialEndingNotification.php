<?php

namespace App\Notifications;

use App\Models\Tenant\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialEndingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public int $daysRemaining
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $urgency = $this->daysRemaining <= 3 ? ' - URGENTE' : '';

        return (new MailMessage)
            ->subject("Tu periodo de prueba termina en {$this->daysRemaining} dias{$urgency}")
            ->view('emails.trial-ending', [
                'tenant' => $this->tenant,
                'daysRemaining' => $this->daysRemaining,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'trial_ending',
            'tenant_id' => $this->tenant->id,
            'days_remaining' => $this->daysRemaining,
            'trial_ends_at' => $this->tenant->trial_ends_at->toIso8601String(),
            'message' => "Tu período de prueba termina en {$this->daysRemaining} días",
        ];
    }
}
