<?php

namespace App\Listeners;

use App\Events\DocumentRejected;
use App\Notifications\DocumentRejectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendDocumentRejectedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(DocumentRejected $event): void
    {
        $document = $event->document;

        // Get the rejection reason from SRI errors
        $reason = null;
        if ($document->sri_errors) {
            $errors = is_array($document->sri_errors)
                ? $document->sri_errors
                : json_decode($document->sri_errors, true);

            $reason = collect($errors)->pluck('mensaje')->implode(', ');
        }

        // Notify the creator of the document
        $creator = $document->createdBy;
        if ($creator) {
            $creator->notify(new DocumentRejectedNotification($document, $reason));
        }

        // Also notify tenant owner if different
        $owner = $document->tenant->owner;
        if ($owner && $owner->id !== $creator?->id) {
            $owner->notify(new DocumentRejectedNotification($document, $reason));
        }
    }

    public function shouldQueue(DocumentRejected $event): bool
    {
        return true;
    }
}
