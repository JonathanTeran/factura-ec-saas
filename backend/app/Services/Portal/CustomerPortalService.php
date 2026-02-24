<?php

namespace App\Services\Portal;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Portal\CustomerPortalSession;
use App\Models\Portal\CustomerPortalToken;
use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\Customer;
use App\Notifications\CustomerPortalMagicLinkNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class CustomerPortalService
{
    /**
     * Busca clientes por email o identificacion a traves de todos los tenants.
     * Retorna coleccion agrupada por tenant_id con info del tenant.
     */
    public function findCustomerByEmailOrIdentification(string $input): Collection
    {
        $input = trim($input);

        return Customer::withoutTenantScope()
            ->where(function ($q) use ($input) {
                $q->where('email', $input)
                  ->orWhere('identification', $input);
            })
            ->where('is_active', true)
            ->with('tenant')
            ->get()
            ->unique('tenant_id');
    }

    /**
     * Genera token y envia magic link por email.
     */
    public function sendMagicLink(int $tenantId, string $email, string $identification): CustomerPortalToken
    {
        $token = CustomerPortalToken::generateFor($tenantId, $email, $identification);

        $tenantName = $token->tenant->name ?? 'Portal de Documentos';

        Notification::route('mail', $email)
            ->notify(new CustomerPortalMagicLinkNotification($token, $tenantName));

        return $token;
    }

    /**
     * Valida un token y crea una sesion del portal.
     */
    public function authenticateWithToken(
        string $token,
        string $ipAddress,
        string $userAgent,
    ): ?CustomerPortalSession {
        $portalToken = CustomerPortalToken::where('token', $token)
            ->valid()
            ->first();

        if (!$portalToken) {
            return null;
        }

        // Obtener nombre del cliente
        $customer = Customer::withoutTenantScope()
            ->where('tenant_id', $portalToken->tenant_id)
            ->where('identification', $portalToken->identification)
            ->first();

        $customerName = $customer->name ?? $portalToken->email;

        // Marcar token como usado
        $portalToken->markUsed($ipAddress);

        // Crear sesion
        return CustomerPortalSession::createFromToken(
            $portalToken,
            $customerName,
            $ipAddress,
            $userAgent,
        );
    }

    /**
     * Obtiene documentos paginados para la sesion del portal.
     */
    public function getDocumentsForSession(
        CustomerPortalSession $session,
        array $filters = [],
        int $perPage = 15,
    ): LengthAwarePaginator {
        $query = ElectronicDocument::withoutTenantScope()
            ->where('electronic_documents.tenant_id', $session->tenant_id)
            ->whereHas('customer', function ($q) use ($session) {
                $q->withoutTenantScope()
                  ->where('identification', $session->identification);
            })
            ->with(['company', 'customer', 'branch', 'emissionPoint']);

        // Solo documentos autorizados
        if (config('portal.show_only_authorized_documents', true)) {
            $query->where('status', DocumentStatus::AUTHORIZED);
        }

        // Filtros
        if (!empty($filters['type'])) {
            $query->where('document_type', $filters['type']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('issue_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('issue_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('access_key', 'like', "%{$search}%")
                  ->orWhere('sequential', 'like', "%{$search}%")
                  ->orWhere('authorization_number', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortField = $filters['sort_field'] ?? 'issue_date';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $allowedSorts = ['issue_date', 'total', 'document_type', 'sequential'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        return $query->paginate($perPage);
    }

    /**
     * Obtiene un documento individual verificando acceso del portal.
     */
    public function getDocument(CustomerPortalSession $session, int $documentId): ?ElectronicDocument
    {
        return ElectronicDocument::withoutTenantScope()
            ->where('id', $documentId)
            ->where('tenant_id', $session->tenant_id)
            ->where('status', DocumentStatus::AUTHORIZED)
            ->whereHas('customer', function ($q) use ($session) {
                $q->withoutTenantScope()
                  ->where('identification', $session->identification);
            })
            ->with(['company', 'customer', 'branch', 'emissionPoint', 'items'])
            ->first();
    }

    /**
     * Estadisticas para el dashboard del portal.
     */
    public function getDashboardStats(CustomerPortalSession $session): array
    {
        $baseQuery = ElectronicDocument::withoutTenantScope()
            ->where('electronic_documents.tenant_id', $session->tenant_id)
            ->where('status', DocumentStatus::AUTHORIZED)
            ->whereHas('customer', function ($q) use ($session) {
                $q->withoutTenantScope()
                  ->where('identification', $session->identification);
            });

        $totalDocuments = (clone $baseQuery)->count();
        $totalAmount = (clone $baseQuery)->sum('total');
        $documentsThisYear = (clone $baseQuery)
            ->whereYear('issue_date', now()->year)
            ->count();
        $amountThisYear = (clone $baseQuery)
            ->whereYear('issue_date', now()->year)
            ->sum('total');

        $recentDocuments = (clone $baseQuery)
            ->with(['company', 'customer', 'branch', 'emissionPoint'])
            ->orderBy('issue_date', 'desc')
            ->limit(5)
            ->get();

        $byType = (clone $baseQuery)
            ->selectRaw('document_type, COUNT(*) as count, SUM(total) as total')
            ->groupBy('document_type')
            ->get()
            ->map(fn ($row) => [
                'type' => $row->document_type,
                'label' => $row->document_type->label(),
                'count' => $row->count,
                'total' => $row->total,
            ]);

        return [
            'total_documents' => $totalDocuments,
            'total_amount' => $totalAmount,
            'documents_this_year' => $documentsThisYear,
            'amount_this_year' => $amountThisYear,
            'recent_documents' => $recentDocuments,
            'by_type' => $byType,
        ];
    }

    /**
     * Limpia tokens y sesiones expiradas.
     */
    public function cleanupExpired(): int
    {
        $tokensDeleted = CustomerPortalToken::expired()
            ->where('created_at', '<', now()->subDays(7))
            ->delete();

        $sessionsDeleted = CustomerPortalSession::expired()->delete();

        return $tokensDeleted + $sessionsDeleted;
    }
}
