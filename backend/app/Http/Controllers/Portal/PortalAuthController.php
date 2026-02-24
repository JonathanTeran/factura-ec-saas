<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Portal\CustomerPortalSession;
use App\Models\Portal\CustomerPortalToken;
use App\Services\Portal\CustomerPortalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

class PortalAuthController extends Controller
{
    public function __construct(
        protected CustomerPortalService $portalService,
    ) {}

    public function showLogin()
    {
        return view('portal.login');
    }

    public function sendMagicLink(Request $request)
    {
        $request->validate([
            'input' => 'required|string|max:100',
            'tenant_id' => 'nullable|integer',
        ]);

        $input = trim($request->input('input'));

        // Rate limiting: max requests por email/identificacion por hora
        $rateLimitKey = 'portal-magic-link:' . $input;
        $maxAttempts = config('portal.max_magic_link_requests_per_hour', 3);

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            return back()
                ->withInput()
                ->withErrors(['input' => 'Has alcanzado el límite de solicitudes. Intenta de nuevo más tarde.']);
        }

        RateLimiter::hit($rateLimitKey, 3600);

        // Buscar clientes
        $customers = $this->portalService->findCustomerByEmailOrIdentification($input);

        if ($customers->isEmpty()) {
            // No revelar que el cliente no existe
            return redirect()->route('portal.link-sent');
        }

        // Si el cliente existe en multiples tenants y no se especifico uno
        if ($customers->count() > 1 && !$request->filled('tenant_id')) {
            return view('portal.select-tenant', [
                'customers' => $customers,
                'input' => $input,
            ]);
        }

        // Determinar el tenant
        if ($request->filled('tenant_id')) {
            $customer = $customers->firstWhere('tenant_id', (int) $request->input('tenant_id'));
        } else {
            $customer = $customers->first();
        }

        if (!$customer || !$customer->email) {
            return redirect()->route('portal.link-sent');
        }

        // Enviar magic link
        $this->portalService->sendMagicLink(
            $customer->tenant_id,
            $customer->email,
            $customer->identification,
        );

        return redirect()->route('portal.link-sent');
    }

    public function linkSent()
    {
        return view('portal.link-sent');
    }

    public function authenticate(string $token, Request $request)
    {
        $session = $this->portalService->authenticateWithToken(
            $token,
            $request->ip(),
            $request->userAgent(),
        );

        if (!$session) {
            return redirect()->route('portal.login')
                ->withErrors(['token' => 'El enlace no es válido o ha expirado. Solicita uno nuevo.']);
        }

        $cookieName = config('portal.cookie_name', 'customer_portal_session');
        $cookieMinutes = config('portal.session_expiry_days', 7) * 24 * 60;

        return redirect()->route('portal.dashboard')
            ->withCookie(cookie(
                $cookieName,
                $session->id,
                $cookieMinutes,
                '/',
                null,
                $request->secure(),
                true, // HttpOnly
                false,
                'Lax',
            ));
    }

    public function downloadRide(int $document, Request $request)
    {
        $session = $request->attributes->get('portal_session');
        $doc = $this->portalService->getDocument($session, $document);

        if (!$doc || !$doc->ride_pdf_path) {
            abort(404);
        }

        return Storage::download(
            $doc->ride_pdf_path,
            $doc->getDocumentNumber() . '.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function downloadXml(int $document, Request $request)
    {
        $session = $request->attributes->get('portal_session');
        $doc = $this->portalService->getDocument($session, $document);

        if (!$doc || !$doc->xml_authorized_path) {
            abort(404);
        }

        return Storage::download(
            $doc->xml_authorized_path,
            $doc->getDocumentNumber() . '.xml',
            ['Content-Type' => 'application/xml'],
        );
    }

    public function logout(Request $request)
    {
        $session = $request->attributes->get('portal_session');

        if ($session) {
            $session->delete();
        }

        $cookieName = config('portal.cookie_name', 'customer_portal_session');

        return redirect()->route('portal.login')
            ->withCookie(cookie()->forget($cookieName));
    }
}
