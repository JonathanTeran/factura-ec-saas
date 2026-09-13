<?php

namespace App\Services\Document;

use App\Enums\DocumentStatus;
use App\Exceptions\DocumentSendException;
use App\Jobs\SRI\ProcessDocumentJob;
use App\Models\SRI\ElectronicDocument;
use App\Services\Cache\TenantCacheService;
use App\Services\SRI\SriPreValidator;

/**
 * Único camino para enviar un documento al SRI: verifica el estado, el
 * checklist fiscal y de firma de la empresa, pre-valida las reglas del SRI y
 * encola el procesamiento (firma + recepción + autorización).
 *
 * Lo usan el panel y la API de integración (vía DocumentController), la
 * conversión de proformas ("convertir y enviar") y la emisión automática de
 * las facturas recurrentes.
 */
class DocumentSender
{
    public function __construct(private readonly SriPreValidator $preValidator) {}

    /**
     * @throws DocumentSendException
     */
    public function send(ElectronicDocument $document): ElectronicDocument
    {
        // Borradores nuevos + reintento de los que fallaron o fueron rechazados.
        $sendable = [
            DocumentStatus::DRAFT,
            DocumentStatus::FAILED,
            DocumentStatus::REJECTED,
        ];
        if (! in_array($document->status, $sendable, true)) {
            throw new DocumentSendException(
                'Solo se pueden enviar borradores o reintentar documentos fallidos/rechazados.',
                400
            );
        }

        $company = $document->company;
        $checklist = $company->emissionReadinessChecklist();

        if (! $checklist['basic_data']) {
            throw new DocumentSendException(
                'La empresa no tiene completos los datos fiscales del emisor.',
                400
            );
        }

        if (! $checklist['establishments']) {
            throw new DocumentSendException(
                'La empresa no tiene establecimientos/puntos de emisión configurados.',
                400
            );
        }

        if (! $checklist['digital_signature']) {
            if ($company->hasValidSignature() && ! $company->hasSignatureFile()) {
                throw new DocumentSendException(
                    'El archivo de tu firma electrónica (.p12) no se encuentra. '
                    .'Volvé a subirlo en Firma electrónica y reintentá.',
                    400
                );
            }

            throw new DocumentSendException(
                'La empresa no tiene una firma electrónica válida configurada.',
                400
            );
        }

        // La clave del SRI (portal "SRI en línea") no participa en la emisión:
        // la firma usa el certificado .p12 y el webservice de recepción/
        // autorización no la requiere. No debe bloquear el envío.

        // Validación local de reglas del SRI ANTES de enviar: evita gastar
        // llamadas al webservice y que el comprobante sea devuelto por errores
        // que podemos detectar aquí (ej. factura > $50 a Consumidor Final,
        // identificación inválida, sin detalle).
        $preErrors = $this->preValidator->validate($document);
        if (! empty($preErrors)) {
            $document->update([
                'status' => DocumentStatus::REJECTED,
                'sri_errors' => ['validation' => $preErrors],
            ]);
            TenantCacheService::invalidateDashboard($document->tenant_id);

            throw new DocumentSendException(
                'El documento no cumple las reglas del SRI y no se envió: '
                .implode(' ', $preErrors),
                422,
                $preErrors
            );
        }

        // Se limpia el error anterior para no arrastrar el detalle de un
        // intento fallido previo.
        $document->update([
            'status' => DocumentStatus::PROCESSING,
            'sri_errors' => null,
        ]);
        ProcessDocumentJob::dispatch($document);

        TenantCacheService::invalidateDashboard($document->tenant_id);

        return $document->fresh();
    }
}
