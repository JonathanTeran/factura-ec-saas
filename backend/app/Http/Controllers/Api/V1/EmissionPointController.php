<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DocumentType;
use App\Http\Requests\Api\EmissionPointRequest;
use App\Http\Resources\EmissionPointResource;
use App\Models\SRI\ElectronicDocument;
use App\Models\SRI\SequentialNumber;
use App\Models\Tenant\Branch;
use App\Models\Tenant\EmissionPoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmissionPointController extends ApiController
{
    public function index(Request $request, Branch $branch): JsonResponse
    {
        $this->authorizeBranch($request, $branch);

        $emissionPoints = $branch->emissionPoints()
            ->orderBy('code')
            ->get();

        return $this->success([
            'emission_points' => EmissionPointResource::collection($emissionPoints),
        ]);
    }

    public function store(EmissionPointRequest $request, Branch $branch): JsonResponse
    {
        $this->authorizeBranch($request, $branch);

        $emissionPoint = $branch->emissionPoints()->create([
            'tenant_id' => $request->user()->tenant_id,
            ...$request->validated(),
        ]);

        return $this->created([
            'emission_point' => new EmissionPointResource($emissionPoint),
        ], 'Punto de emisión creado exitosamente');
    }

    public function show(Request $request, Branch $branch, EmissionPoint $emissionPoint): JsonResponse
    {
        $this->authorizeBranch($request, $branch);
        $this->authorizeEmissionPoint($branch, $emissionPoint);

        return $this->success([
            'emission_point' => new EmissionPointResource($emissionPoint),
        ]);
    }

    public function update(EmissionPointRequest $request, Branch $branch, EmissionPoint $emissionPoint): JsonResponse
    {
        $this->authorizeBranch($request, $branch);
        $this->authorizeEmissionPoint($branch, $emissionPoint);

        $emissionPoint->update($request->validated());

        return $this->success([
            'emission_point' => new EmissionPointResource($emissionPoint),
        ], 'Punto de emisión actualizado exitosamente');
    }

    public function destroy(Request $request, Branch $branch, EmissionPoint $emissionPoint): JsonResponse
    {
        $this->authorizeBranch($request, $branch);
        $this->authorizeEmissionPoint($branch, $emissionPoint);

        if ($emissionPoint->documents()->exists()) {
            return $this->error(
                'No se puede eliminar el punto de emisión porque tiene documentos emitidos.',
                400
            );
        }

        $emissionPoint->delete();

        return $this->success(null, 'Punto de emisión eliminado exitosamente');
    }

    /**
     * Secuenciales del punto de emisión por tipo de comprobante: último
     * número usado, siguiente que se emitirá y último realmente emitido.
     */
    public function sequentials(Request $request, Branch $branch, EmissionPoint $emissionPoint): JsonResponse
    {
        $this->authorizeBranch($request, $branch);
        $this->authorizeEmissionPoint($branch, $emissionPoint);

        return $this->success($this->sequentialsPayload($emissionPoint));
    }

    /**
     * Ajusta los secuenciales (p. ej. al migrar desde otro sistema). Se
     * guarda el ÚLTIMO número usado; el siguiente comprobante sale con +1.
     * SRI-CRITICAL: nunca se permite retroceder por debajo de lo ya emitido.
     */
    public function updateSequentials(Request $request, Branch $branch, EmissionPoint $emissionPoint): JsonResponse
    {
        $this->authorizeBranch($request, $branch);
        $this->authorizeEmissionPoint($branch, $emissionPoint);

        $validTypes = array_map(fn (DocumentType $t) => $t->value, DocumentType::cases());

        $data = $request->validate([
            'sequentials' => ['required', 'array', 'min:1'],
            'sequentials.*.document_type' => ['required', Rule::in($validTypes)],
            'sequentials.*.last_number' => ['required', 'integer', 'min:0', 'max:999999999'],
        ], [
            'sequentials.*.last_number.max' => 'El secuencial tiene como máximo 9 dígitos.',
        ]);

        foreach ($data['sequentials'] as $item) {
            $issued = $this->lastIssued($emissionPoint, $item['document_type']);
            if ((int) $item['last_number'] < $issued) {
                $label = DocumentType::from($item['document_type'])->label();

                return $this->error(
                    "No se puede retroceder el secuencial de {$label}: ya emitiste hasta el "
                    .str_pad((string) $issued, 9, '0', STR_PAD_LEFT).". El último número usado debe ser al menos {$issued}.",
                    422
                );
            }
        }

        DB::transaction(function () use ($emissionPoint, $data) {
            foreach ($data['sequentials'] as $item) {
                SequentialNumber::updateOrCreate(
                    [
                        'tenant_id' => $emissionPoint->tenant_id,
                        'emission_point_id' => $emissionPoint->id,
                        'document_type' => $item['document_type'],
                    ],
                    ['current_number' => (int) $item['last_number']]
                );
            }
        });

        return $this->success($this->sequentialsPayload($emissionPoint->fresh()), 'Secuenciales actualizados.');
    }

    /** Mayor secuencial ya consumido por un comprobante (incluye eliminados). */
    private function lastIssued(EmissionPoint $emissionPoint, string $documentType): int
    {
        $cast = DB::connection()->getDriverName() === 'mysql' ? 'UNSIGNED' : 'INTEGER';

        return (int) ElectronicDocument::withoutGlobalScopes()
            ->withTrashed()
            ->where('emission_point_id', $emissionPoint->id)
            ->where('document_type', $documentType)
            ->max(DB::raw("CAST(sequential AS {$cast})"));
    }

    /**
     * @return array<string, mixed>
     */
    private function sequentialsPayload(EmissionPoint $emissionPoint): array
    {
        $emissionPoint->loadMissing('branch');
        $series = $emissionPoint->branch->getFormattedCode().'-'.$emissionPoint->getFormattedCode();

        $rows = $emissionPoint->sequentialNumbers()->get()->keyBy(
            fn (SequentialNumber $row) => $row->document_type instanceof DocumentType
                ? $row->document_type->value
                : (string) $row->document_type
        );

        $counts = ElectronicDocument::withoutGlobalScopes()
            ->where('emission_point_id', $emissionPoint->id)
            ->selectRaw('document_type, COUNT(*) as c')
            ->groupBy('document_type')
            ->pluck('c', 'document_type');

        $items = collect(DocumentType::cases())->map(function (DocumentType $type) use ($rows, $counts, $emissionPoint, $series) {
            $current = (int) ($rows->get($type->value)?->current_number ?? 0);

            return [
                'document_type' => $type->value,
                'document_type_label' => $type->label(),
                'current_number' => $current,
                'next_number' => $current + 1,
                'next_formatted' => $series.'-'.str_pad((string) ($current + 1), 9, '0', STR_PAD_LEFT),
                'last_issued' => $this->lastIssued($emissionPoint, $type->value),
                'documents_count' => (int) ($counts[$type->value] ?? 0),
            ];
        })->values()->all();

        return [
            'emission_point' => [
                'id' => $emissionPoint->id,
                'code' => $emissionPoint->code,
                'series' => $series,
            ],
            'sequentials' => $items,
        ];
    }

    protected function authorizeBranch(Request $request, Branch $branch): void
    {
        if ($branch->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'No tienes acceso a este establecimiento.');
        }
    }

    protected function authorizeEmissionPoint(Branch $branch, EmissionPoint $emissionPoint): void
    {
        if ($emissionPoint->branch_id !== $branch->id) {
            abort(404, 'Punto de emisión no encontrado.');
        }
    }
}
