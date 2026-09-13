<?php

namespace App\Http\Controllers\Api\V1\Ext;

use App\Http\Controllers\Api\V1\CustomerController as PanelCustomerController;
use App\Http\Resources\CustomerResource;
use App\Models\Tenant\Customer;
use App\Support\ApiError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** Clientes para integraciones externas (/api/v1/ext/customers). */
class CustomerController extends PanelCustomerController
{
    protected function authorizeCustomer(Request $request, Customer $customer): void
    {
        if ((int) $customer->tenant_id !== (int) $request->user()->tenant_id) {
            throw new NotFoundHttpException('Cliente no encontrado.');
        }
    }

    public function index(Request $request): JsonResponse
    {
        $request->merge(['per_page' => DocumentController::clampPerPage($request->input('per_page'))]);

        return parent::index($request);
    }

    /**
     * GET /customers/lookup?identification=1712345678001 — busca por
     * cédula/RUC/pasaporte exacto. 404 si no existe (útil para "crear si no
     * existe" desde el sistema del integrador).
     */
    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identification' => ['required', 'string', 'max:20'],
        ]);

        $customer = Customer::where('tenant_id', $request->user()->tenant_id)
            ->where('identification', trim($validated['identification']))
            ->first();

        if (! $customer) {
            return ApiError::json('not_found', 'No existe un cliente con esa identificación.', 404);
        }

        return $this->success(['customer' => new CustomerResource($customer)]);
    }
}
