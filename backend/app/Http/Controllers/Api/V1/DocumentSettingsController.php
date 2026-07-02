<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Tenant\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentSettingsController extends ApiController
{
    /**
     * Sensible defaults for document/email settings.
     *
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return [
            'auto_send_email' => true,
            'email_subject' => 'Su comprobante electrónico',
            'email_message' => 'Adjunto encontrará su comprobante electrónico autorizado por el SRI.',
            'ride_footer' => '',
        ];
    }

    /**
     * Resolve the single company for the authenticated tenant.
     */
    private function resolveCompany(Request $request): ?Company
    {
        return Company::where('tenant_id', $request->user()->tenant_id)->first();
    }

    /**
     * GET document-settings
     * Returns stored document settings merged over defaults.
     */
    public function show(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);

        $stored = $company?->settings['documents'] ?? [];

        return $this->success(array_merge($this->defaults(), $stored));
    }

    /**
     * PUT document-settings
     * Validates and persists document settings under settings['documents']
     * without clobbering other settings keys.
     */
    public function update(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company) {
            return $this->validationError([
                'company' => ['Primero configura los datos de tu empresa.'],
            ], 'Primero configura los datos de tu empresa.');
        }

        $validated = $request->validate([
            'auto_send_email' => ['boolean'],
            'email_subject' => ['required', 'string', 'max:150'],
            'email_message' => ['required', 'string', 'max:1000'],
            'ride_footer' => ['nullable', 'string', 'max:300'],
        ]);

        $documents = array_merge($this->defaults(), [
            'auto_send_email' => (bool) ($validated['auto_send_email'] ?? $this->defaults()['auto_send_email']),
            'email_subject' => $validated['email_subject'],
            'email_message' => $validated['email_message'],
            'ride_footer' => $validated['ride_footer'] ?? '',
        ]);

        $settings = $company->settings ?? [];
        $settings['documents'] = $documents;
        $company->settings = $settings;
        $company->save();

        return $this->success($documents, 'Configuración de documentos actualizada exitosamente');
    }
}
