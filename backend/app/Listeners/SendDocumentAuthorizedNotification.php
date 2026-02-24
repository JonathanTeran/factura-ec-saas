<?php

namespace App\Listeners;

use App\Events\DocumentAuthorized;
use App\Notifications\DocumentAuthorizedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendDocumentAuthorizedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(DocumentAuthorized $event): void
    {
        $document = $event->document;

        // Notify the creator of the document
        $creator = $document->createdBy;
        if ($creator) {
            $creator->notify(new DocumentAuthorizedNotification($document));
        }

        // Also notify tenant owner if different
        $owner = $document->tenant->owner;
        if ($owner && $owner->id !== $creator?->id) {
            $owner->notify(new DocumentAuthorizedNotification($document));
        }
    }

    public function shouldQueue(DocumentAuthorized $event): bool
    {
        return true;
    }
}
