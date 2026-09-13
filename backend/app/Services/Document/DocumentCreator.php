<?php

namespace App\Services\Document;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Exceptions\DocumentCreationException;
use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\Company;
use App\Models\Tenant\EmissionPoint;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\Cache\TenantCacheService;
use App\Services\SRI\AccessKeyService;
use Illuminate\Support\Facades\DB;

/**
 * Único camino para crear un documento electrónico en borrador.
 *
 * Lo usan el panel y la API de integración (vía DocumentController), la
 * conversión de proformas y las facturas recurrentes. Concentra las reglas
 * que antes vivían inline en el controlador: suscripción y límite del plan,
 * checklist fiscal de la empresa, punto de emisión válido y activo,
 * secuencial atómico, clave de acceso, ítems, retenciones y contadores.
 *
 * Los errores de negocio se lanzan como DocumentCreationException con el
 * mismo mensaje y código HTTP que devolvía el controlador.
 */
class DocumentCreator
{
    public function __construct(private readonly AccessKeyService $accessKeys) {}

    /**
     * @param  array<string, mixed>  $data  Misma forma que DocumentRequest
     *                                      (company_id, customer_id, emission_point_id, document_type,
     *                                      issue_date, subtotal_*, total_tax, total_discount, tip, total,
     *                                      payment_methods | payment_method + payment_term, additional_info,
     *                                      items[], withholding_details[], reference_document_id,
     *                                      modification_reason) más opcionales notes, due_date, currency,
     *                                      recurring_invoice_id.
     *
     * @throws DocumentCreationException
     */
    public function createDraft(Tenant $tenant, array $data, ?User $createdBy = null): ElectronicDocument
    {
        $tenant = $this->tenantWithFreshSubscription($tenant);

        if (! $tenant->activeSubscription) {
            throw new DocumentCreationException(
                'Necesitas una suscripción activa para crear documentos.',
                403
            );
        }

        if (! $tenant->canIssueDocuments()) {
            throw new DocumentCreationException(
                'Has alcanzado el límite de documentos de tu plan. Actualiza tu suscripción para continuar.',
                403
            );
        }

        $company = Company::where('id', $data['company_id'] ?? null)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $checklist = $company->emissionReadinessChecklist();

        if (! $checklist['basic_data']) {
            throw new DocumentCreationException(
                'La empresa no tiene completos los datos fiscales del emisor.',
                422
            );
        }

        if (! $checklist['establishments']) {
            throw new DocumentCreationException(
                'Configura al menos un establecimiento con punto de emisión antes de crear documentos.',
                422
            );
        }

        $emissionPoint = EmissionPoint::where('id', $data['emission_point_id'] ?? null)
            ->where('tenant_id', $tenant->id)
            ->whereHas('branch', fn ($query) => $query->where('company_id', $company->id))
            ->firstOrFail();

        // No se puede emitir sobre un punto de emisión o establecimiento
        // inactivo (candado extra; la app ya los oculta).
        if (! $emissionPoint->is_active || ! optional($emissionPoint->branch)->is_active) {
            throw new DocumentCreationException(
                'El punto de emisión o el establecimiento está inactivo. Actívalo para poder emitir.',
                422
            );
        }

        $documentType = (string) $data['document_type'];

        // Nota de crédito / débito: persistir el documento modificado y el
        // motivo donde el generador XML los lee (related_document_* y
        // additional_info['motivo'|'motivos']).
        $relatedDocumentData = [];
        $additionalInfo = is_array($data['additional_info'] ?? null) ? $data['additional_info'] : [];
        if (! empty($data['reference_document_id'])) {
            $reference = ElectronicDocument::where('id', $data['reference_document_id'])
                ->where('tenant_id', $tenant->id)
                ->firstOrFail();
            $relatedDocumentData = [
                'related_document_id' => $reference->id,
                'related_document_type' => $reference->document_type->value,
                'related_document_number' => $reference->getDocumentNumber(),
                'related_document_date' => $reference->issue_date,
            ];
            $reason = trim((string) ($data['modification_reason'] ?? ''));
            if ($reason !== '') {
                if ($documentType === DocumentType::NOTA_DEBITO->value) {
                    $additionalInfo['motivos'] = [[
                        'razon' => $reason,
                        'valor' => (float) ($data['total'] ?? 0),
                    ]];
                } else {
                    $additionalInfo['motivo'] = $reason;
                }
            }
        }

        $totalTax = $data['total_tax'] ?? (($data['tax_12'] ?? 0) + ($data['tax_15'] ?? 0));
        $totalDiscount = $data['total_discount'] ?? ($data['discount'] ?? 0);

        $paymentMethods = $data['payment_methods'] ?? null;
        if (! $paymentMethods && ! empty($data['payment_method'])) {
            $paymentMethods = [[
                'code' => (string) $data['payment_method'],
                'amount' => (float) ($data['total'] ?? 0),
                'term' => (int) ($data['payment_term'] ?? 0),
                'time_unit' => 'dias',
            ]];
        }

        // Transacción: el bloqueo del secuencial (lockForUpdate) solo es
        // efectivo dentro de una transacción, y así un fallo al crear los
        // ítems no deja un documento a medias con el secuencial consumido.
        $document = DB::transaction(function () use (
            $tenant, $company, $emissionPoint, $documentType, $data, $createdBy,
            $totalTax, $totalDiscount, $paymentMethods, $additionalInfo, $relatedDocumentData
        ) {
            $sequential = $emissionPoint->getNextSequential($documentType);
            $formattedSequential = str_pad((string) $sequential, 9, '0', STR_PAD_LEFT);
            $series = $emissionPoint->branch->code.'-'.$emissionPoint->code;

            $document = ElectronicDocument::create([
                'tenant_id' => $tenant->id,
                'company_id' => $company->id,
                'branch_id' => $emissionPoint->branch_id,
                'emission_point_id' => $emissionPoint->id,
                'customer_id' => $data['customer_id'] ?? null,
                'document_type' => $documentType,
                'environment' => $company->sri_environment,
                'series' => $series,
                'sequential' => $formattedSequential,
                'issue_date' => $data['issue_date'] ?? now(),
                'due_date' => $data['due_date'] ?? null,
                'currency' => $data['currency'] ?? 'DOLAR',
                'subtotal_no_tax' => $data['subtotal_no_tax'] ?? 0,
                'subtotal_0' => $data['subtotal_0'] ?? 0,
                'subtotal_5' => $data['subtotal_5'] ?? 0,
                'subtotal_12' => $data['subtotal_12'] ?? 0,
                'subtotal_15' => $data['subtotal_15'] ?? 0,
                'subtotal_8' => $data['subtotal_8'] ?? 0,
                'subtotal_13' => $data['subtotal_13'] ?? 0,
                'total_discount' => $totalDiscount,
                'total_tax' => $totalTax,
                'tip' => $data['tip'] ?? 0,
                'total' => $data['total'] ?? 0,
                'payment_methods' => $paymentMethods,
                'status' => DocumentStatus::DRAFT,
                'additional_info' => $additionalInfo,
                'notes' => $data['notes'] ?? null,
                'recurring_invoice_id' => $data['recurring_invoice_id'] ?? null,
                'created_by' => $createdBy?->id,
                ...$relatedDocumentData,
            ]);

            // La clave de acceso es determinística (Módulo 11): se genera desde
            // el borrador para mostrarla en el detalle y la vista previa del PDF.
            $document->update(['access_key' => $this->accessKeys->generate($document)]);

            foreach (array_values($data['items'] ?? []) as $index => $item) {
                $line = DocumentTotals::normalizeItem($item, $index);
                $document->items()->create([
                    'tenant_id' => $tenant->id,
                    'product_id' => $line['product_id'],
                    'main_code' => $line['main_code'],
                    'aux_code' => $line['aux_code'],
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount' => $line['discount'],
                    'subtotal' => $line['subtotal'],
                    'tax_code' => $line['tax_code'],
                    'tax_percentage_code' => $line['tax_percentage_code'],
                    'tax_rate' => $line['tax_rate'],
                    'tax_base' => $line['tax_base'],
                    'tax_value' => $line['tax_value'],
                    'sort_order' => $index,
                ]);
            }

            // Comprobante de retención: detalle de retenciones.
            if ($documentType === DocumentType::RETENCION->value) {
                foreach ($data['withholding_details'] ?? [] as $detail) {
                    $document->withholdingDetails()->create([
                        'tenant_id' => $tenant->id,
                        'support_doc_code' => $detail['support_doc_code'],
                        'support_doc_number' => $detail['support_doc_number'],
                        'support_doc_date' => $detail['support_doc_date'],
                        'support_doc_total' => $detail['support_doc_total'] ?? 0,
                        'support_reason_code' => $detail['support_reason_code'] ?? '01',
                        'tax_type' => $detail['tax_type'],
                        'retention_code' => $detail['retention_code'],
                        'tax_base' => $detail['tax_base'],
                        'retention_rate' => $detail['retention_rate'],
                        'retained_value' => $detail['retained_value'],
                    ]);
                }
            }

            $tenant->incrementDocumentCount();

            return $document;
        });

        TenantCacheService::invalidateDashboard($tenant->id);

        return $document;
    }

    /**
     * Tenant con su suscripción activa cargada desde el caché (10 min). Si
     * el caché dice que no hay suscripción, se invalida y se consulta en
     * fresco: el super admin pudo acabar de aprobar el pago.
     */
    private function tenantWithFreshSubscription(Tenant $tenant): Tenant
    {
        $cached = TenantCacheService::tenantWithSubscription($tenant->id) ?? $tenant;

        if (! $cached->activeSubscription) {
            TenantCacheService::invalidateTenant($tenant->id);
            $cached = TenantCacheService::tenantWithSubscription($tenant->id) ?? $tenant->fresh() ?? $tenant;
        }

        return $cached;
    }
}
