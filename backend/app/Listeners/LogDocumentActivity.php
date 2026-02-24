<?php

namespace App\Listeners;

use App\Events\DocumentAuthorized;
use App\Events\DocumentCreated;
use App\Events\DocumentRejected;
use App\Events\DocumentSigned;
use App\Events\DocumentVoided;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogDocumentActivity implements ShouldQueue
{
    use InteractsWithQueue;

    public function handleDocumentCreated(DocumentCreated $event): void
    {
        $document = $event->document;

        Log::channel('documents')->info('Document created', [
            'document_id' => $document->id,
            'document_number' => $document->document_number,
            'document_type' => $document->document_type,
            'tenant_id' => $document->tenant_id,
            'company_id' => $document->company_id,
            'customer_id' => $document->customer_id,
            'total' => $document->total,
            'created_by' => $document->created_by,
        ]);
    }

    public function handleDocumentSigned(DocumentSigned $event): void
    {
        $document = $event->document;

        Log::channel('documents')->info('Document signed', [
            'document_id' => $document->id,
            'document_number' => $document->document_number,
            'access_key' => $document->access_key,
            'tenant_id' => $document->tenant_id,
        ]);
    }

    public function handleDocumentAuthorized(DocumentAuthorized $event): void
    {
        $electronicDocument = $event->document;

        Log::channel('documents')->info('Document authorized', [
            'document_id' => $electronicDocument->id,
            'access_key' => $electronicDocument->access_key,
            'authorization_number' => $electronicDocument->authorization_number,
            'authorization_date' => $electronicDocument->authorization_date,
            'tenant_id' => $electronicDocument->tenant_id,
        ]);
    }

    public function handleDocumentRejected(DocumentRejected $event): void
    {
        $electronicDocument = $event->document;

        Log::channel('documents')->warning('Document rejected', [
            'document_id' => $electronicDocument->id,
            'access_key' => $electronicDocument->access_key,
            'errors' => $electronicDocument->sri_errors,
            'tenant_id' => $electronicDocument->tenant_id,
        ]);
    }

    public function handleDocumentVoided(DocumentVoided $event): void
    {
        $document = $event->document;

        Log::channel('documents')->info('Document voided', [
            'document_id' => $document->id,
            'document_number' => $document->document_number,
            'reason' => $event->reason,
            'tenant_id' => $document->tenant_id,
        ]);
    }

    public function subscribe($events): array
    {
        return [
            DocumentCreated::class => 'handleDocumentCreated',
            DocumentSigned::class => 'handleDocumentSigned',
            DocumentAuthorized::class => 'handleDocumentAuthorized',
            DocumentRejected::class => 'handleDocumentRejected',
            DocumentVoided::class => 'handleDocumentVoided',
        ];
    }
}
