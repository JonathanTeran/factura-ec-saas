<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DocumentStatus;
use App\Http\Requests\Api\DocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Jobs\SRI\ProcessDocumentJob;
use App\Jobs\SRI\SendDocumentToClientJob;
use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\Company;
use App\Models\Tenant\EmissionPoint;
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
                        $q->where('name', 'like', "%{$search}%");
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
        $tenant = $user->tenant;

        if (!$tenant->activeSubscription) {
            return $this->error(
                'Necesitas una suscripción activa para crear documentos.',
                403
            );
        }

        // Check plan limits
        if (!$tenant->canIssueDocuments()) {
            return $this->error(
                'Has alcanzado el límite de documentos de tu plan. Actualiza tu suscripción para continuar.',
                403
            );
        }

        // Validate company belongs to tenant
        $company = Company::where('id', $request->company_id)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $checklist = $company->emissionReadinessChecklist();

        if (!$checklist['basic_data']) {
            return $this->error(
                'La empresa no tiene completos los datos fiscales del emisor.',
                422
            );
        }

        if (!$checklist['establishments']) {
            return $this->error(
                'Configura al menos un establecimiento con punto de emisión antes de crear documentos.',
                422
            );
        }

        // Get emission point and generate sequential number
        $emissionPoint = EmissionPoint::where('id', $request->emission_point_id)
            ->where('tenant_id', $tenant->id)
            ->whereHas('branch', fn ($query) => $query->where('company_id', $company->id))
            ->firstOrFail();
        $sequential = $emissionPoint->getNextSequential($request->document_type);
        $formattedSequential = str_pad((string) $sequential, 9, '0', STR_PAD_LEFT);
        $series = $emissionPoint->branch->code . '-' . $emissionPoint->code;
        $totalTax = $request->total_tax ?? (($request->tax_12 ?? 0) + ($request->tax_15 ?? 0));
        $totalDiscount = $request->total_discount ?? ($request->discount ?? 0);

        $paymentMethods = $request->payment_methods;
        if (!$paymentMethods && $request->filled('payment_method')) {
            $paymentMethods = [[
                'code' => (string) $request->payment_method,
                'amount' => (float) $request->total,
                'term' => (int) ($request->payment_term ?? 0),
                'time_unit' => 'dias',
            ]];
        }

        // Create document
        $document = ElectronicDocument::create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'branch_id' => $emissionPoint->branch_id,
            'emission_point_id' => $emissionPoint->id,
            'customer_id' => $request->customer_id,
            'document_type' => $request->document_type,
            'environment' => $company->sri_environment,
            'series' => $series,
            'sequential' => $formattedSequential,
            'issue_date' => $request->issue_date ?? now(),
            'currency' => 'DOLAR',
            'subtotal_no_tax' => $request->subtotal_no_tax ?? 0,
            'subtotal_0' => $request->subtotal_0 ?? 0,
            'subtotal_5' => $request->subtotal_5 ?? 0,
            'subtotal_12' => $request->subtotal_12 ?? 0,
            'subtotal_15' => $request->subtotal_15 ?? 0,
            'total_discount' => $totalDiscount,
            'total_tax' => $totalTax,
            'tip' => $request->tip ?? 0,
            'total' => $request->total,
            'payment_methods' => $paymentMethods,
            'status' => DocumentStatus::DRAFT,
            'additional_info' => $request->additional_info ?? [],
            'created_by' => $user->id,
        ]);

        // Create document items
        foreach ($request->items as $item) {
            $document->items()->create([
                'product_id' => $item['product_id'] ?? null,
                'main_code' => $item['main_code'],
                'aux_code' => $item['aux_code'] ?? null,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount' => $item['discount'] ?? 0,
                'subtotal' => $item['subtotal'],
                'tax_code' => $item['tax_code'] ?? '2',
                'tax_percentage_code' => $item['tax_percentage_code'] ?? '2',
                'tax_rate' => $item['tax_rate'] ?? 12,
                'tax_base' => $item['tax_base'],
                'tax_value' => $item['tax_value'],
            ]);
        }

        // Increment tenant document counter
        $tenant->incrementDocumentCount();

        return $this->created([
            'document' => new DocumentResource($document->load(['customer', 'company', 'items'])),
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

        $document->load(['customer', 'company', 'items.product']);

        return $this->success([
            'document' => new DocumentResource($document),
        ]);
    }

    public function update(DocumentRequest $request, ElectronicDocument $document): JsonResponse
    {
        $this->authorizeDocument($request, $document);

        if (!$document->status->isEditable()) {
            return $this->error(
                'Este documento no puede ser editado porque ya fue procesado.',
                400
            );
        }

        $document->update($request->validated());

        // Update items
        if ($request->has('items')) {
            $document->items()->delete();
            foreach ($request->items as $item) {
                $document->items()->create($item);
            }
        }

        return $this->success([
            'document' => new DocumentResource($document->fresh(['customer', 'company', 'items'])),
        ], 'Documento actualizado exitosamente');
    }

    public function destroy(Request $request, ElectronicDocument $document): JsonResponse
    {
        $this->authorizeDocument($request, $document);

        if (!$document->status->isEditable()) {
            return $this->error(
                'Este documento no puede ser eliminado porque ya fue procesado.',
                400
            );
        }

        $document->items()->delete();
        $document->delete();

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

        if ($document->status !== DocumentStatus::DRAFT) {
            return $this->error(
                'Solo se pueden enviar documentos en estado borrador.',
                400
            );
        }

        $checklist = $document->company->emissionReadinessChecklist();

        if (!$checklist['basic_data']) {
            return $this->error(
                'La empresa no tiene completos los datos fiscales del emisor.',
                400
            );
        }

        if (!$checklist['establishments']) {
            return $this->error(
                'La empresa no tiene establecimientos/puntos de emisión configurados.',
                400
            );
        }

        // Validate company has signature
        if (!$checklist['digital_signature']) {
            return $this->error(
                'La empresa no tiene una firma electrónica válida configurada.',
                400
            );
        }

        if (!$checklist['sri_password']) {
            return $this->error(
                'La empresa no tiene configurada la clave del SRI.',
                400
            );
        }

        // Dispatch job to process document
        $document->update(['status' => DocumentStatus::PROCESSING]);
        ProcessDocumentJob::dispatch($document);

        return $this->success([
            'document' => new DocumentResource($document->fresh()),
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
     * Genera y retorna la URL temporal del RIDE (PDF) del documento.
     */
    public function downloadRide(Request $request, ElectronicDocument $document): JsonResponse
    {
        $this->authorizeDocument($request, $document);

        if (!in_array($document->status, [DocumentStatus::AUTHORIZED, DocumentStatus::REJECTED])) {
            return $this->error('El RIDE solo está disponible para documentos autorizados.', 400);
        }

        // Generate RIDE if not exists
        if (!$document->ride_path || !Storage::disk('local')->exists($document->ride_path)) {
            $rideGenerator = app(RIDEGenerator::class);
            $ridePath = $rideGenerator->generate($document);
            $document->update(['ride_path' => $ridePath]);
        }

        $url = Storage::disk('local')->temporaryUrl(
            $document->ride_path,
            now()->addMinutes(30)
        );

        return $this->success([
            'url' => $url,
            'filename' => $document->document_number . '.pdf',
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

        if (!$document->xml_signed_path) {
            return $this->error('El XML firmado no está disponible.', 400);
        }

        $url = Storage::disk('local')->temporaryUrl(
            $document->xml_signed_path,
            now()->addMinutes(30)
        );

        return $this->success([
            'url' => $url,
            'filename' => $document->access_key . '.xml',
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

        $email = $validated['email'] ?? $document->customer->email;

        if (!$email) {
            return $this->error('No se especificó un correo electrónico.', 400);
        }

        SendDocumentToClientJob::dispatch($document, $email);

        return $this->success(null, 'El documento será enviado a ' . $email);
    }

    /**
     * Consultar estado del documento
     *
     * Retorna el estado actual y datos de autorización del SRI.
     */
    public function checkStatus(Request $request, ElectronicDocument $document): JsonResponse
    {
        $this->authorizeDocument($request, $document);

        return $this->success([
            'status' => $document->status->value,
            'status_label' => $document->status->label(),
            'authorization_number' => $document->authorization_number,
            'authorization_date' => $document->authorization_date,
            'sri_messages' => $document->sri_response['messages'] ?? [],
        ]);
    }

    protected function authorizeDocument(Request $request, ElectronicDocument $document): void
    {
        if ($document->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'No tienes acceso a este documento.');
        }
    }
}
