<?php

namespace App\Observers\Arbitros;

use App\Enums\DocumentStatus;
use App\Models\Arbitros\OfficiatedMatch;
use App\Models\SRI\ElectronicDocument;

/**
 * Sincroniza el estado del partido pitado con el ciclo de vida de su factura
 * (§4.4 y §4.5 del spec): autorizada → facturado; rechazada/fallida/anulada →
 * vuelve a pendiente (y se desvincula si fue anulada, para poder re-facturar).
 */
class DocumentStatusObserver
{
    public function updated(ElectronicDocument $document): void
    {
        if (! $document->wasChanged('status')) {
            return;
        }

        $match = OfficiatedMatch::withoutTenantScope()
            ->where('electronic_document_id', $document->id)
            ->first();

        if (! $match) {
            return;
        }

        match ($document->status) {
            DocumentStatus::AUTHORIZED => $match->update([
                'status' => OfficiatedMatch::STATUS_INVOICED,
                'invoiced_at' => now(),
            ]),
            DocumentStatus::VOIDED => $match->update([
                'status' => OfficiatedMatch::STATUS_PENDING,
                'electronic_document_id' => null,
                'invoiced_at' => null,
            ]),
            DocumentStatus::REJECTED, DocumentStatus::FAILED => $match->update([
                'status' => OfficiatedMatch::STATUS_PENDING,
                'invoiced_at' => null,
            ]),
            default => null,
        };
    }
}
