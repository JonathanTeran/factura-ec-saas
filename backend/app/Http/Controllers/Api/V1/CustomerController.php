<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\CustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\DocumentResource;
use App\Models\Tenant\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Clientes
 */
class CustomerController extends ApiController
{
    /**
     * Listar clientes
     *
     * Retorna los clientes del tenant con búsqueda y paginación.
     *
     * @queryParam search string Búsqueda por nombre, identificación o email.
     * @queryParam per_page int Resultados por página. Default: 15.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::where('tenant_id', $request->user()->tenant_id)
            ->orderBy('name');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('identification', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate($request->input('per_page', 15));

        return $this->paginated($customers, CustomerResource::class);
    }

    /**
     * Crear cliente
     *
     * Registra un nuevo cliente asociado al tenant actual.
     */
    public function store(CustomerRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Map API field names to model column names
        if (isset($data['identification_number'])) {
            $data['identification'] = $data['identification_number'];
            unset($data['identification_number']);
        }

        $customer = Customer::create([
            'tenant_id' => $request->user()->tenant_id,
            ...$data,
        ]);

        return $this->created([
            'customer' => new CustomerResource($customer),
        ], 'Cliente creado exitosamente');
    }

    /**
     * Ver cliente
     */
    public function show(Request $request, Customer $customer): JsonResponse
    {
        $this->authorizeCustomer($request, $customer);

        return $this->success([
            'customer' => new CustomerResource($customer),
        ]);
    }

    /**
     * Actualizar cliente
     */
    public function update(CustomerRequest $request, Customer $customer): JsonResponse
    {
        $this->authorizeCustomer($request, $customer);

        $data = $request->validated();

        // Map API field names to model column names
        if (isset($data['identification_number'])) {
            $data['identification'] = $data['identification_number'];
            unset($data['identification_number']);
        }

        $customer->update($data);

        return $this->success([
            'customer' => new CustomerResource($customer),
        ], 'Cliente actualizado exitosamente');
    }

    /**
     * Eliminar cliente
     *
     * No se puede eliminar si tiene documentos asociados.
     */
    public function destroy(Request $request, Customer $customer): JsonResponse
    {
        $this->authorizeCustomer($request, $customer);

        // Check if customer has documents
        if ($customer->documents()->exists()) {
            return $this->error(
                'No se puede eliminar el cliente porque tiene documentos asociados.',
                400
            );
        }

        $customer->delete();

        return $this->success(null, 'Cliente eliminado exitosamente');
    }

    /**
     * Buscar clientes
     *
     * Búsqueda rápida (máximo 10 resultados) por nombre, identificación o email.
     */
    public function search(Request $request, string $query): JsonResponse
    {
        $customers = Customer::where('tenant_id', $request->user()->tenant_id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('identification', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get();

        return $this->success([
            'customers' => CustomerResource::collection($customers),
        ]);
    }

    /**
     * Documentos del cliente
     *
     * Lista los documentos electrónicos asociados a un cliente.
     */
    public function documents(Request $request, Customer $customer): JsonResponse
    {
        $this->authorizeCustomer($request, $customer);

        $documents = $customer->documents()
            ->with('company')
            ->orderByDesc('issue_date')
            ->paginate($request->input('per_page', 15));

        return $this->paginated($documents, DocumentResource::class);
    }

    protected function authorizeCustomer(Request $request, Customer $customer): void
    {
        if ($customer->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'No tienes acceso a este cliente.');
        }
    }
}
