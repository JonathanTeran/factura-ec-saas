<?php

namespace App\Http\Controllers\Api\V1\Ext;

use App\Http\Controllers\Api\V1\DocumentController as PanelDocumentController;
use App\Http\Requests\Api\DocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\Tenant;
use App\Services\Api\IdempotencyStore;
use App\Support\ApiError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Documentos electrónicos para integraciones externas (/api/v1/ext/documents).
 *
 * Reutiliza el controlador del panel (mismo flujo probado de creación,
 * envío al SRI, anulación y archivos) y añade: idempotencia, crear+emitir en
 * una sola llamada, 404 para documentos de otros tenants, tope de per_page y
 * códigos de error estables.
 */
class DocumentController extends PanelDocumentController
{
    public const MAX_PER_PAGE = 100;

    /** Los documentos de otros tenants no existen para el integrador (404, no 403). */
    protected function authorizeDocument(Request $request, ElectronicDocument $document): void
    {
        if ((int) $document->tenant_id !== (int) $request->user()->tenant_id) {
            throw new NotFoundHttpException('Documento no encontrado.');
        }
    }

    public function index(Request $request): JsonResponse
    {
        $request->merge(['per_page' => self::clampPerPage($request->input('per_page'))]);

        return parent::index($request);
    }

    /**
     * Crea el documento y, salvo `send=false`, lo envía al SRI en la misma
     * llamada. Idempotente con la cabecera `Idempotency-Key`.
     */
    public function store(DocumentRequest $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('api_tenant');

        $response = app(IdempotencyStore::class)->run(
            $tenant,
            $request->header('Idempotency-Key'),
            $request->except(['send']) + ['send' => $request->boolean('send', true)],
            fn () => $this->createAndMaybeSend($request)
        );

        return $response instanceof JsonResponse ? $response : response()->json($response->getContent(), $response->getStatusCode());
    }

    protected function createAndMaybeSend(DocumentRequest $request): JsonResponse
    {
        $created = parent::store($request);

        if ($created->getStatusCode() !== 201) {
            return $this->translateError($created);
        }

        if (! $request->boolean('send', true)) {
            return $created;
        }

        $id = (int) data_get($created->getData(true), 'data.document.id');
        $document = ElectronicDocument::withoutGlobalScopes()->findOrFail($id);

        $sent = parent::send($request, $document);

        if ($sent->getStatusCode() >= 400) {
            $body = $sent->getData(true);
            $errors = array_values(array_filter((array) ($body['errors'] ?? []), 'is_string'));
            $document = $document->fresh()->load(['customer', 'company', 'items', 'withholdingDetails']);

            return response()->json([
                'success' => false,
                'error' => $sent->getStatusCode() === 422 ? 'validation_error' : 'send_failed',
                'message' => $body['message'] ?? 'El documento se creó pero no se pudo enviar al SRI.',
                'errors' => $errors !== [] ? ['sri' => $errors] : [],
                'data' => ['document' => new DocumentResource($document)],
            ], $sent->getStatusCode() === 422 ? 422 : 409);
        }

        return response()->json($sent->getData(true) + ['message' => 'Documento creado y enviado al SRI. Consulta su estado en GET /documents/{id}/status.'], 201);
    }

    public function status(Request $request, ElectronicDocument $document): JsonResponse
    {
        return parent::checkStatus($request, $document);
    }

    public function email(Request $request, ElectronicDocument $document): JsonResponse
    {
        return parent::resendEmail($request, $document);
    }

    /** PDF del RIDE (binario). Con `?url=1`, JSON con URL temporal de 30 min. */
    public function ride(Request $request, ElectronicDocument $document): Response
    {
        if ($request->boolean('url')) {
            return parent::downloadRide($request, $document);
        }

        return parent::streamRide($request, $document);
    }

    /** XML firmado/autorizado (binario). Con `?url=1`, JSON con URL temporal. */
    public function xml(Request $request, ElectronicDocument $document): Response
    {
        $this->authorizeDocument($request, $document);

        if (! $document->xml_signed_path) {
            return ApiError::json(
                'xml_not_available',
                'El XML aún no está disponible: el documento no ha sido firmado/autorizado.',
                404
            );
        }

        if ($request->boolean('url')) {
            return parent::downloadXml($request, $document);
        }

        return parent::streamXml($request, $document);
    }

    /** Añade un código `error` estable a las respuestas de error del panel. */
    protected function translateError(JsonResponse $response): JsonResponse
    {
        $body = $response->getData(true);
        $status = $response->getStatusCode();
        $message = (string) ($body['message'] ?? '');

        $code = match (true) {
            $status === 403 && str_contains($message, 'límite de documentos') => 'plan_limit_reached',
            $status === 403 && str_contains($message, 'suscripción') => 'subscription_required',
            $status === 422 => 'validation_error',
            $status === 404 => 'not_found',
            default => 'request_failed',
        };

        return response()->json(['error' => $code] + $body, $status);
    }

    public static function clampPerPage(mixed $value): int
    {
        $n = (int) ($value ?: 15);

        return max(1, min($n, self::MAX_PER_PAGE));
    }
}
