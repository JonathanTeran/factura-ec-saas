<?php

namespace App\Listeners;

use App\Events\DocumentAuthorized;
use App\Events\DocumentCreated;
use App\Events\DocumentRejected;
use App\Events\DocumentSigned;
use App\Events\DocumentVoided;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;

class DispatchWebhookListener implements ShouldQueue
{
    public string $queue = 'webhooks';

    public function __construct(
        protected WebhookService $webhookService,
    ) {}

    public function handleDocumentAuthorized(DocumentAuthorized $event): void
    {
        if (!$this->tenantHasWebhooks($event->document->tenant_id)) {
            return;
        }

        $this->webhookService->dispatchForDocument($event->document, 'document.authorized');
    }

    public function handleDocumentRejected(DocumentRejected $event): void
    {
        if (!$this->tenantHasWebhooks($event->document->tenant_id)) {
            return;
        }

        $this->webhookService->dispatchForDocument($event->document, 'document.rejected');
    }

    public function handleDocumentCreated(DocumentCreated $event): void
    {
        if (!$this->tenantHasWebhooks($event->document->tenant_id)) {
            return;
        }

        $this->webhookService->dispatchForDocument($event->document, 'document.created');
    }

    public function handleDocumentSigned(DocumentSigned $event): void
    {
        if (!$this->tenantHasWebhooks($event->document->tenant_id)) {
            return;
        }

        $this->webhookService->dispatchForDocument($event->document, 'document.signed');
    }

    public function handleDocumentVoided(DocumentVoided $event): void
    {
        if (!$this->tenantHasWebhooks($event->document->tenant_id)) {
            return;
        }

        $this->webhookService->dispatchForDocument($event->document, 'document.voided');
    }

    protected function tenantHasWebhooks(int $tenantId): bool
    {
        $tenant = \App\Models\Tenant\Tenant::find($tenantId);

        return $tenant?->has_webhooks ?? false;
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            DocumentAuthorized::class => 'handleDocumentAuthorized',
            DocumentRejected::class => 'handleDocumentRejected',
            DocumentCreated::class => 'handleDocumentCreated',
            DocumentSigned::class => 'handleDocumentSigned',
            DocumentVoided::class => 'handleDocumentVoided',
        ];
    }
}
