<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\EmissionPointRequest;
use App\Http\Resources\EmissionPointResource;
use App\Models\Tenant\Branch;
use App\Models\Tenant\EmissionPoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
