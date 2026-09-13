<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DocumentStatus;
use App\Exceptions\DocumentCreationException;
use App\Exceptions\DocumentSendException;
use App\Http\Requests\Api\DocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Jobs\SRI\SendDocumentToClientJob;
use App\Models\SRI\ElectronicDocument;
use App\Services\Cache\TenantCacheService;
use App\Services\Document\DocumentCreator;
use App\Services\Document\DocumentSender;
use App\Services\SRI\AccessKeyService;
use App\Services\SRI\RIDEGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * @tags Documentos Electrónicos
 */
class DocumentController extends ApiController
{
    /**
     * Listar documentos
     *
     * Retorna documentos electrónicos del tenant con filtros y paginación.
     *
     * @queryParam status string Estado del documento (draft, processing, sent, authorized, rejected, voided).
     * @queryParam document_type string Tipo de documento SRI (01, 04, 05, 06, 07).
     * @queryParam company_id int ID de la empresa.
     * @queryParam customer_id int ID del cliente.
     * @queryParam date_from string Fecha inicio (YYYY-MM-DD).
     * @queryParam date_to string Fecha fin (YYYY-MM-DD).
     * @queryParam search string Búsqueda por clave de acceso, número o nombre de cliente.
     * @queryParam per_page int Resultados por página. Default: 15.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ElectronicDocument::where('tenant_id', $request->user()->tenant_id)
            ->with(['customer', 'company']);

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('document_type')) {
            $query->where('document_type', $request->input('document_type'));
        }

        if ($request->has('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        if ($request->filled('access_key')) {
            $query->where('access_key', $request->input('access_key'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('issue_date', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('issue_date', '<=', $request->input('date_to'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('access_key', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('identification', 'like', "%{$search}%");
                    });
            });
        }

        $documents = $query->orderByDesc('issue_date')
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 15));

        return $this->paginated($documents, DocumentResource::class);
    }

    /**
     * Crear documento electrónico
     *
     * Crea un nuevo documento (factura, nota de crédito, etc.) en estado borrador.
     * Requiere suscripción activa y no haber excedido el límite del plan.
     */
    public function store(DocumentRequest $request): JsonResponse
    {
        $user = $request->user();

        // Toda la lógica de creación (suscripción, límite del plan, checklist
        // de la empresa, secuencial atómico, clave de acceso, ítems) vive en
        // DocumentCreator: el mismo camino que usan las proformas convertidas
        // y las facturas recurrentes.
        try {
            $document = app(DocumentCreator::class)->createDraft(
                $user->tenant,
                $request->validated(),
                $user
            );
        } catch (DocumentCreationException $e) {
            return $this->error($e->getMessage(), $e->status, $e->errors);
        }

        return $this->created([
            'document' => new DocumentResource($document->load(['customer', 'company', 'items', 'withholdingDetails'])),
        ], 'Documento creado exitosamente');
    }

    /**
     * Ver documento
     *
     * Retorna el detalle completo de un documento con sus ítems.
     */
    public function show(Request $request, ElectronicDocument $document): JsonResponse
    {
        $this->authorizeDocument($request, $document);

        $document->load(['customer', 'company', 'items.product', 'withholdingDetails']);

        return $this->success([
            'document' => new DocumentResource($document),
        ]);
    }

    public function update(DocumentRequest $request, ElectronicDocument $document): JsonResponse
    {
        $this->authorizeDocument($request, $document);

        if (! $document->status->isEditable()) {
            return $this->error(
                'Este documento no puede ser editado porque ya fue procesado.',
                400
            );
        }

        $document->update($request->validated());

        // La fecha de emisión (u otros datos) pudieron cambiar: la clave de
        // acceso depende de ellos, así que se regenera para el borrador.
        $document->update(['access_key' => app(AccessKeyService::class)->generate($document->fresh())]);

        // Update items
        if ($request->has('items')) {
            $document->items()->delete();
            foreach ($request->items as $item) {
                $document->items()->create($item);
            }
        }

        // El inicio (recientes/estadísticas) está cacheado: invalidar para que
        // los cambios del borrador se reflejen de inmediato.
        TenantCacheService::invalidateDashboard($document->tenant_id);

        return $this->success([
            'document' => new DocumentResource($document->fresh(['customer', 'company', 'items'])),
        ], 'Documento actualizado exitosamente');
    }

    public function destroy(Request $request, ElectronicDocument $document): JsonResponse
    {
        $this->authorizeDocument($request, $document);

        if (! $document->status->isEditable()) {
            return $this->error(
                'Este documento no puede ser eliminado porque ya fue procesado.',
                400
            );
        }

        $tenantId = $document->tenant_id;
        $document->items()->delete();
        $document->delete();

        TenantCacheService::invalidateDashboard($tenantId);

        return $this->success(null, 'Documento eliminado exitosamente');
    }

    /**
     * Enviar documento al SRI
     *
     * Inicia el proceso de firma, envío y autorización ante el SRI.
     * Solo documentos en estado borrador pueden ser enviados.
     */
    public function send(Request $request, ElectronicDocument $document): JsonResponse
    {
        $this->authorizeDocument($request, $document);

        // Estado enviable, checklist fiscal/firma y pre-validación SRI viven
        // en DocumentSender (compartido con proformas y recurrentes).
        try {
            $document = app(DocumentSender::class)->send($document);
        } catch (DocumentSendException $e) {
            return $this->error($e->getMessage(), $e->status, $e->errors);
        }

        return $this->success([
            'document' => new DocumentResource($document),
        ], 'Documento enviado a procesar. Recibirás una notificación cuando esté listo.');
    }

    public function void(Request $request, ElectronicDocument $document): JsonResponse
    {
        $this->authorizeDocument($request, $document);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:300'],
        ]);

        if ($document->status !== DocumentStatus::AUTHORIZED) {
            return $this->error(
                'Solo se pueden anular documentos autorizados.',
                400
            );
        }

        // In Ecuador, electronic documents cannot be voided directly
        // You need to issue a Credit Note to void an invoice
        // This is just for internal tracking
        $document->update([
            'status' => DocumentStatus::VOIDED,
            'voided_at' => now(),
            'void_reason' => $validated['reason'],
        ]);

        return $this->success([
            'document' => new DocumentResource($document->fresh()),
        ], 'Documento marcado como anulado.');
    }

    /**
     * Descargar RIDE
     *
     * Devuelve una URL FIRMADA del propio dominio que transmite el PDF (ver
     * streamRidePublic). Así el navegador puede abrirlo sin depender de la URL
     * interna del almacenamiento (MinIO no está expuesto públicamente).
     */
    public function downloadRide(Request $request, ElectronicDocument $document): JsonResponse
    {
        $this->authorizeDocument($request, $document);

        $isFinal = in_array($document->status, [DocumentStatus::AUTHORIZED, DocumentStatus::REJECTED]);
        $filename = ($isFinal ? '' : 'borrador-').$document->document_number.'.pdf';

        return $this->success([
            'url' => $this->publicFileUrl('ride', $document->id),
            'filename' => $filename,
        ]);
    }

    /**
     * Genera una URL pública con token HMAC de expiración corta (30 min) para
     * servir el RIDE/XML por el dominio público. El token es independiente de
     * la reconstrucción de URL detrás del proxy (a diferencia de las URL
     * firmadas nativas de Laravel).
     */
    protected function publicFileUrl(string $kind, int $id): string
    {
        $expires = now()->addMinutes(30)->timestamp;
        $token = $this->fileToken($kind, $id, $expires);

        return rtrim(config('app.url'), '/')
            ."/api/v1/public/documents/{$id}/{$kind}?e={$expires}&t={$token}";
    }

    protected function fileToken(string $kind, int $id, int $expires): string
    {
        return hash_hmac('sha256', "{$kind}:{$id}:{$expires}", (string) config('app.key'));
    }

    protected function verifyFileToken(string $kind, int $id, Request $request): void
    {
        $expires = (int) $request->query('e');
        $token = (string) $request->query('t');

        if ($expires < now()->timestamp) {
            abort(403, 'El enlace expiró. Generá uno nuevo.');
        }
        if (! hash_equals($this->fileToken($kind, $id, $expires), $token)) {
            abort(403, 'Enlace inválido.');
        }
    }

    /**
     * Transmite el RIDE (PDF) por una URL pública con token (sin auth): el
     * token garantiza que la generó una petición autorizada. Se genera en
     * memoria.
     */
    public function streamRidePublic(Request $request, string $document)
    {
        $this->verifyFileToken('ride', (int) $document, $request);

        $doc = ElectronicDocument::withoutGlobalScopes()->findOrFail($document);
        $doc->load(['company', 'branch', 'emissionPoint', 'customer', 'items', 'withholdingDetails']);

        if (! $doc->access_key && $doc->status === DocumentStatus::DRAFT) {
            $doc->update(['access_key' => app(AccessKeyService::class)->generate($doc)]);
        }

        $isFinal = in_array($doc->status, [DocumentStatus::AUTHORIZED, DocumentStatus::REJECTED]);
        $preview = ! $isFinal;
        $filename = ($preview ? 'borrador-' : '').$doc->document_number.'.pdf';

        $pdf = app(RIDEGenerator::class)->render($doc, [], $preview);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Transmite el XML firmado por una URL pública con token.
     */
    public function streamXmlPublic(Request $request, string $document)
    {
        $this->verifyFileToken('xml', (int) $document, $request);

        $doc = ElectronicDocument::withoutGlobalScopes()->findOrFail($document);

        if (! $doc->xml_signed_path) {
            abort(404);
        }

        try {
            $contents = Storage::disk(config('filesystems.default'))->get($doc->xml_signed_path);
        } catch (\Throwable $e) {
            $contents = null;
        }

        if (empty($contents)) {
            abort(404);
        }

        return response($contents, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'inline; filename="'.$doc->access_key.'.xml"',
        ]);
    }

    /**
     * Descargar XML firmado
     *
     * Retorna la URL temporal del XML firmado del documento.
     */
    public function downloadXml(Request $request, ElectronicDocument $document): JsonResponse
    {
        $this->authorizeDocument($request, $document);

        if (! $document->xml_signed_path) {
            return $this->error('El XML firmado no está disponible.', 400);
        }

        return $this->success([
            'url' => $this->publicFileUrl('xml', $document->id),
            'filename' => $document->access_key.'.xml',
        ]);
    }

    /**
     * Transmite el RIDE (PDF) directamente desde el servidor.
     *
     * A diferencia de downloadRide (URL temporal del storage, que apunta a un
     * host interno no accesible desde el móvil), el PDF se genera en memoria y
     * se devuelve en la respuesta, servido por el dominio público de la app.
     * No depende de leer del almacenamiento. Sirve para borradores (vista
     * previa con marca de agua) y para autorizados/rechazados.
     */
    public function streamRide(Request $request, ElectronicDocument $document)
    {
        $this->authorizeDocument($request, $document);

        $document->load(['company', 'branch', 'emissionPoint', 'customer', 'items', 'withholdingDetails']);

        if (! $document->access_key && $document->status === DocumentStatus::DRAFT) {
            $document->update(['access_key' => app(AccessKeyService::class)->generate($document)]);
        }

        $isFinal = in_array($document->status, [DocumentStatus::AUTHORIZED, DocumentStatus::REJECTED]);
        $preview = ! $isFinal;
        $filename = ($preview ? 'borrador-' : '').$document->document_number.'.pdf';

        $pdf = app(RIDEGenerator::class)->render($document, [], $preview);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Transmite el XML firmado directamente desde el servidor.
     */
    public function streamXml(Request $request, ElectronicDocument $document)
    {
        $this->authorizeDocument($request, $document);

        if (! $document->xml_signed_path) {
            return $this->error('El XML firmado no está disponible.', 400);
        }

        try {
            $contents = Storage::disk(config('filesystems.default'))->get($document->xml_signed_path);
        } catch (\Throwable $e) {
            $contents = null;
        }

        if (empty($contents)) {
            return $this->error('No se pudo leer el XML firmado.', 404);
        }

        return response($contents, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'inline; filename="'.$document->access_key.'.xml"',
        ]);
    }

    public function resendEmail(Request $request, ElectronicDocument $document): JsonResponse
    {
        $this->authorizeDocument($request, $document);

        $validated = $request->validate([
            'email' => ['nullable', 'email'],
        ]);

        if ($document->status !== DocumentStatus::AUTHORIZED) {
            return $this->error('Solo se pueden reenviar documentos autorizados.', 400);
        }

        $email = $validated['email'] ?? $document->customer?->email;

        if (! $email) {
            return $this->error('No se especificó un correo electrónico.', 400);
        }

        SendDocumentToClientJob::dispatch($document, $email);

        return $this->success(null, 'El documento será enviado a '.$email);
    }

    /**
     * Consultar estado del documento
     *
     * Retorna el estado actual y datos de autorización del SRI.
     */
    public function checkStatus(Request $request, ElectronicDocument $document): JsonResponse
    {
        $this->authorizeDocument($request, $document);

        // La autorización del SRI es asíncrona: si el documento sigue en
        // proceso, re-consultamos al SRI en vivo (lo dispara el polling de la
        // app/web). Nunca rompe la respuesta si el SRI está caído.
        try {
            app(\App\Services\SRI\SRIService::class)->refreshStatus($document);
            $document->refresh();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::info('checkStatus refresh skipped', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success([
            'status' => $document->status->value,
            'status_label' => $document->status->label(),
            'authorization_number' => $document->authorization_number,
            'authorization_date' => $document->authorization_date,
            'sri_messages' => $document->sri_response['messages'] ?? [],
            'contingency_active' => (bool) data_get($document->sri_errors, 'contingency_active', false),
            'contingency_message' => data_get($document->sri_errors, 'contingency_message'),
        ]);
    }

    protected function authorizeDocument(Request $request, ElectronicDocument $document): void
    {
        if ($document->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'No tienes acceso a este documento.');
        }
    }
}
