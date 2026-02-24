<?php

namespace App\Listeners;

use App\Events\TenantCreated;
use App\Notifications\NewUserWelcomeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendWelcomeNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TenantCreated $event): void
    {
        $owner = $event->owner;

        // Send welcome notification to the new tenant owner
        $owner->notify(new NewUserWelcomeNotification());
    }

    public function shouldQueue(TenantCreated $event): bool
    {
        return true;
    }
}
