<?php

namespace App\Http\Middleware;

use App\Exceptions\PlanLimitExceededException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimits
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->tenant) {
            return $next($request);
        }

        $tenant = $user->tenant;

        // Check if tenant can issue documents (for document creation routes)
        if ($request->routeIs('tenant.invoices.create', 'api.v1.documents.store', 'panel.documents.create')) {
            if (!$tenant->canIssueDocuments()) {
                throw new PlanLimitExceededException(
                    'documents',
                    $tenant->max_documents_per_month,
                    $tenant->documents_this_month,
                );
            }
        }

        return $next($request);
    }
}
