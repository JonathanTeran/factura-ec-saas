<?php

namespace App\Listeners;

use App\Events\DocumentCreated;
use App\Notifications\PlanLimitReachedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CheckPlanLimitsAfterDocument implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(DocumentCreated $event): void
    {
        $document = $event->document;
        $tenant = $document->tenant;

        if (!$tenant) {
            return;
        }

        // Check if tenant is approaching document limit
        $currentUsage = $tenant->documents_this_month;
        $maxAllowed = $tenant->max_documents_per_month;

        // Warn at 80% usage
        $warningThreshold = (int) ($maxAllowed * 0.8);

        if ($currentUsage >= $warningThreshold && $currentUsage < $maxAllowed) {
            // Notify owner about approaching limit
            $owner = $tenant->owner;
            if ($owner) {
                // Check if we haven't already sent this notification today
                $alreadyNotified = $owner->notifications()
                    ->where('type', PlanLimitReachedNotification::class)
                    ->whereDate('created_at', today())
                    ->exists();

                if (!$alreadyNotified) {
                    $owner->notify(new PlanLimitReachedNotification(
                        $tenant,
                        'documents',
                        $currentUsage,
                        $maxAllowed
                    ));
                }
            }
        }

        // Warn when limit is reached
        if ($currentUsage >= $maxAllowed) {
            $owner = $tenant->owner;
            if ($owner) {
                $owner->notify(new PlanLimitReachedNotification(
                    $tenant,
                    'documents',
                    $currentUsage,
                    $maxAllowed
                ));
            }
        }
    }

    public function shouldQueue(DocumentCreated $event): bool
    {
        return true;
    }
}
