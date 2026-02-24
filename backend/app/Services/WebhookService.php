<?php

namespace App\Services;

use App\Jobs\SendWebhookJob;
use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\WebhookEndpoint;

class WebhookService
{
    public function dispatch(int $tenantId, string $event, array $payload): int
    {
        $endpoints = WebhookEndpoint::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->active()
            ->forEvent($event)
            ->get();

        foreach ($endpoints as $endpoint) {
            SendWebhookJob::dispatch($endpoint, $event, $payload);
        }

        return $endpoints->count();
    }

    public function dispatchForDocument(ElectronicDocument $document, string $event): int
    {
        $payload = $this->buildDocumentPayload($document);

        return $this->dispatch($document->tenant_id, $event, $payload);
    }

    protected function buildDocumentPayload(ElectronicDocument $document): array
    {
        $document->loadMissing(['customer', 'company', 'branch', 'emissionPoint']);

        return [
            'document_id' => $document->id,
            'document_type' => $document->document_type->value,
            'document_type_label' => $document->document_type->label(),
            'document_number' => $document->getDocumentNumber(),
            'access_key' => $document->access_key,
            'status' => $document->status->value,
            'authorization_number' => $document->authorization_number,
            'authorization_date' => $document->authorization_date?->toIso8601String(),
            'issue_date' => $document->issue_date->toDateString(),
            'subtotal' => (float) $document->getSubtotal(),
            'total_tax' => (float) $document->total_tax,
            'total' => (float) $document->total,
            'currency' => $document->currency,
            'customer' => $document->customer ? [
                'identification' => $document->customer->identification,
                'name' => $document->customer->name,
                'email' => $document->customer->email,
            ] : null,
            'company' => [
                'ruc' => $document->company->ruc,
                'business_name' => $document->company->business_name,
            ],
            'sri_errors' => $document->sri_errors,
        ];
    }
}
