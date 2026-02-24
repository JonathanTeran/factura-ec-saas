<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOnboarding
{
    /**
     * Redirect users who haven't completed onboarding to the onboarding wizard.
     *
     * Excludes: the onboarding route itself, logout, and API routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // No user or no tenant -- let other middleware handle auth
        if (!$user || !$user->tenant) {
            return $next($request);
        }

        // Super admins skip onboarding
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        // Don't redirect if already on onboarding page, logout, or API routes
        if ($request->routeIs('panel.onboarding') ||
            $request->routeIs('logout') ||
            $request->is('api/*') ||
            $request->routeIs('livewire.*')) {
            return $next($request);
        }

        $settings = $user->tenant->settings ?? [];

        if (empty($settings['onboarding_completed'])) {
            return redirect()->route('panel.onboarding');
        }

        return $next($request);
    }
}
