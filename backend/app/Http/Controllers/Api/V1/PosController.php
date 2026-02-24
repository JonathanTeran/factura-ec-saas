<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Tenant\PosSession;
use App\Models\Tenant\PosTransaction;
use App\Services\Pos\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends ApiController
{
    public function __construct(
        private readonly PosService $posService,
    ) {}

    /**
     * Obtener sesion activa.
     */
    public function activeSession(Request $request): JsonResponse
    {
        $session = $this->posService
            ->forTenant($request->user()->tenant)
            ->getActiveSession();

        return $this->success(['session' => $session]);
    }

    /**
     * Abrir sesion de caja.
     */
    public function openSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'emission_point_id' => ['required', 'exists:emission_points,id'],
            'opening_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $session = $this->posService
                ->forTenant($request->user()->tenant)
                ->openSession($validated);

            return $this->created(['session' => $session->load(['branch', 'emissionPoint'])], 'Caja abierta exitosamente');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Cerrar sesion de caja.
     */
    public function closeSession(Request $request, PosSession $session): JsonResponse
    {
        $this->authorizeSession($request, $session);

        $validated = $request->validate([
            'closing_amount' => ['required', 'numeric', 'min:0'],
            'closing_notes' => ['nullable', 'string'],
        ]);

        try {
            $session = $this->posService
                ->forTenant($request->user()->tenant)
                ->closeSession($session, $validated['closing_amount'], $validated['closing_notes'] ?? null);

            return $this->success(['session' => $session], 'Caja cerrada exitosamente');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Crear transaccion (venta rapida).
     */
    public function createTransaction(Request $request, PosSession $session): JsonResponse
    {
        $this->authorizeSession($request, $session);

        $validated = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'payment_method' => ['required', 'in:cash,card,transfer,other'],
            'amount_received' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.000001'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric'],
        ]);

        try {
            $transaction = $this->posService
                ->forTenant($request->user()->tenant)
                ->createTransaction($session, $validated, $validated['items']);

            return $this->created(['transaction' => $transaction], 'Venta registrada exitosamente');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Listar transacciones de una sesion.
     */
    public function transactions(Request $request, PosSession $session): JsonResponse
    {
        $this->authorizeSession($request, $session);

        $transactions = $session->transactions()
            ->with(['items.product', 'customer'])
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $transactions->items(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Anular transaccion.
     */
    public function voidTransaction(Request $request, PosTransaction $transaction): JsonResponse
    {
        if ($transaction->session->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        try {
            $this->posService
                ->forTenant($request->user()->tenant)
                ->voidTransaction($transaction);

            return $this->success(null, 'Transaccion anulada exitosamente');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Historial de sesiones.
     */
    public function sessions(Request $request): JsonResponse
    {
        $sessions = PosSession::where('tenant_id', $request->user()->tenant_id)
            ->with(['branch', 'emissionPoint', 'openedByUser', 'closedByUser'])
            ->orderByDesc('opened_at')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $sessions->items(),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
            ],
        ]);
    }

    protected function authorizeSession(Request $request, PosSession $session): void
    {
        if ($session->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'No tienes acceso a esta sesion.');
        }
    }
}
