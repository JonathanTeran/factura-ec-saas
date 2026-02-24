<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->tenant_id) {
            // Set current tenant in config for global access
            config(['app.tenant_id' => $user->tenant_id]);

            // Share tenant with all views
            view()->share('currentTenant', $user->tenant);
        }

        return $next($request);
    }
}
