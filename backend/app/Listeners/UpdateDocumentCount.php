<?php

namespace App\Listeners;

use App\Events\DocumentCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateDocumentCount implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(DocumentCreated $event): void
    {
        $document = $event->document;
        $tenant = $document->tenant;

        if (!$tenant) {
            return;
        }

        // Increment document count for the month
        $tenant->incrementDocumentCount();
    }

    public function shouldQueue(DocumentCreated $event): bool
    {
        return true;
    }
}
