<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DocumentType;
use App\Models\SRI\SequentialNumber;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Company;
use App\Models\Tenant\EmissionPoint;
use App\Services\SRI\SignatureManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Onboarding REST API.
 *
 * Guides a freshly registered tenant through the minimum setup required to
 * issue SRI electronic documents: company fiscal data, digital signature,
 * establishment/emission point and starting sequential numbers.
 *
 * Logic is ported from the Livewire wizard
 * (App\Livewire\Panel\Onboarding\OnboardingWizard) so the Next.js frontend
 * mirrors the exact same behaviour/validation/cert handling.
 */
class OnboardingController extends ApiController
{
    /**
     * GET onboarding/status
     *
     * Returns the completion state of every onboarding milestone so the
     * frontend can resume the wizard where the user left off.
     */
    public function status(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $company = $this->resolveCompany($request);

        $hasCompany = (bool) $company;

        $hasCertificate = $hasCompany
            && filled($company->signature_path)
            && filled($company->signature_expires_at);

        $hasEstablishment = false;
        $hasSequentials = false;

        if ($hasCompany) {
            $branch = $company->branches()
                ->whereHas('emissionPoints')
                ->first();

            $hasEstablishment = (bool) $branch;

            $hasSequentials = SequentialNumber::where('tenant_id', $tenant->id)->exists();
        }

        return $this->success([
            'completed' => (bool) data_get($tenant->settings, 'onboarding_completed', false),
            'has_company' => $hasCompany,
            'has_certificate' => $hasCertificate,
            'has_establishment' => $hasEstablishment,
            'has_sequentials' => $hasSequentials,
        ]);
    }

    /**
     * GET signature-status
     *
     * Estado de la firma electrónica del tenant (para avisos en el panel).
     */
    public function signatureStatus(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company) {
            return $this->success([
                'status' => 'missing',
                'days_remaining' => null,
                'expires_at' => null,
                'subject' => null,
            ]);
        }

        $info = app(SignatureManager::class)->checkStatus($company);
        $expiresAt = $info['expires_at'] ?? $info['expired_at'] ?? null;

        return $this->success([
            'status' => $info['status'],          // missing|unknown|expired|expiring_soon|valid
            'message' => $info['message'] ?? null,
            'days_remaining' => $info['days_remaining'] ?? null,
            'expires_at' => $expiresAt ? $expiresAt->toIso8601String() : null,
            'subject' => $company->signature_subject,
        ]);
    }

    /**
     * POST onboarding/company
     *
     * Create-or-update the single Company owned by the tenant.
     */
    public function company(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ruc' => ['required', 'string', 'size:13', 'regex:/^[0-9]{13}$/'],
            'business_name' => ['required', 'string', 'max:300'],
            'trade_name' => ['nullable', 'string', 'max:300'],
            'address' => ['required', 'string', 'max:300'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            // Frontend contract uses natural|sociedad; DB stores natural|juridical|rise.
            // "sociedad" is mapped to "juridical" below.
            'taxpayer_type' => ['required', Rule::in(['natural', 'sociedad', 'juridical', 'rise'])],
            'obligated_accounting' => ['boolean'],
            'rimpe_type' => ['nullable', 'string', 'max:50'],
            'sri_environment' => ['required', Rule::in(['1', '2'])],
        ]);

        $tenant = $request->user()->tenant;

        // Map frontend "sociedad" to the DB enum value "juridical".
        if (($data['taxpayer_type'] ?? null) === 'sociedad') {
            $data['taxpayer_type'] = 'juridical';
        }

        $company = $this->resolveCompany($request) ?? new Company(['tenant_id' => $tenant->id]);

        $company->fill([
            'tenant_id' => $tenant->id,
            'ruc' => $data['ruc'],
            'business_name' => $data['business_name'],
            'trade_name' => $data['trade_name'] ?? $data['business_name'],
            'address' => $data['address'],
            'city' => $data['city'] ?? null,
            'province' => $data['province'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'],
            'taxpayer_type' => $data['taxpayer_type'],
            'obligated_accounting' => (bool) ($data['obligated_accounting'] ?? false),
            'rimpe_type' => $data['rimpe_type'] ?? ($company->rimpe_type ?? 'none'),
            'sri_environment' => $data['sri_environment'],
            'is_active' => true,
        ]);

        $wasCreated = ! $company->exists;
        $company->save();

        return $this->success(
            $this->companyPayload($company),
            $wasCreated ? 'Empresa creada correctamente.' : 'Empresa actualizada correctamente.',
            $wasCreated ? 201 : 200
        );
    }

    /**
     * POST onboarding/certificate  (multipart)
     *
     * Validates the .p12/.pfx certificate + password (openssl_pkcs12_read),
     * stores it and persists signature metadata on the company.
     */
    public function certificate(Request $request): JsonResponse
    {
        $request->validate([
            'certificate' => ['required', 'file', 'max:5120'],
            'password' => ['required', 'string'],
        ]);

        $company = $this->resolveCompany($request);

        if (! $company) {
            return $this->error('Primero debe registrar los datos de la empresa.', 422);
        }

        try {
            $info = app(SignatureManager::class)->store(
                $company,
                $request->file('certificate'),
                $request->input('password')
            );
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success([
            'signature_subject' => $info['subject'],
            'signature_issuer' => $info['issuer'],
            'signature_identification' => $info['identification'] ?? null,
            'signature_serial' => $info['serial_number'] ?? null,
            'signature_valid_from' => isset($info['valid_from']) && $info['valid_from']
                ? $info['valid_from']->toIso8601String()
                : null,
            'signature_expires_at' => $info['expires_at']->toIso8601String(),
            'days_until_expiry' => (int) round(now()->diffInDays($info['expires_at'], false)),
        ], 'Firma electrónica cargada correctamente.');
    }

    /**
     * POST onboarding/establishment
     *
     * Create (or update) the main Branch + EmissionPoint for the tenant's
     * company. Idempotent by branch/emission-point code.
     */
    public function establishment(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $company = $this->resolveCompany($request);

        if (! $company) {
            return $this->error('Primero debe registrar los datos de la empresa.', 422);
        }

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'size:3', 'regex:/^[0-9]{3}$/'],
            'address' => ['required', 'string', 'max:300'],
            'ep_code' => ['nullable', 'string', 'size:3', 'regex:/^[0-9]{3}$/'],
            'ep_name' => ['nullable', 'string', 'max:100'],
            'import_sri_establishments' => ['nullable', 'boolean'],
        ]);

        $branchCode = $data['code'] ?? '001';
        $epCode = $data['ep_code'] ?? '001';

        $result = DB::transaction(function () use ($tenant, $company, $data, $branchCode, $epCode) {
            $branch = Branch::firstOrNew([
                'tenant_id' => $tenant->id,
                'company_id' => $company->id,
                'code' => $branchCode,
            ]);

            $branch->fill([
                'name' => $data['name'] ?? 'Matriz',
                'address' => $data['address'],
                'is_main' => $branch->exists ? $branch->is_main : true,
                'is_active' => true,
            ]);
            $branch->save();

            $emissionPoint = EmissionPoint::updateOrCreate([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'code' => $epCode,
            ], [
                'name' => $data['ep_name'] ?? 'Punto de Emisión 1',
                'is_active' => true,
            ]);

            return [$branch, $emissionPoint];
        });

        [$branch, $emissionPoint] = $result;

        $importedBranches = ($data['import_sri_establishments'] ?? false)
            ? (app(\App\Services\SRI\EstablishmentImporter::class)->import($company) ?? [])
            : [];

        return $this->success([
            'branch' => [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
                'address' => $branch->address,
                'is_main' => (bool) $branch->is_main,
                'is_active' => (bool) $branch->is_active,
            ],
            'emission_point' => [
                'id' => $emissionPoint->id,
                'branch_id' => $emissionPoint->branch_id,
                'code' => $emissionPoint->code,
                'name' => $emissionPoint->name,
                'is_active' => (bool) $emissionPoint->is_active,
            ],
            'imported_branches' => $importedBranches,
        ], 'Establecimiento configurado correctamente.');
    }

    /**
     * GET onboarding/sequentials?emission_point_id=..
     *
     * Returns the current sequential numbers for an emission point so the
     * frontend can preload/edit them.
     */
    public function sequentials(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $request->validate([
            'emission_point_id' => [
                'required',
                Rule::exists('emission_points', 'id')->where('tenant_id', $tenant->id),
            ],
        ]);

        $rows = SequentialNumber::where('tenant_id', $tenant->id)
            ->where('emission_point_id', $request->integer('emission_point_id'))
            ->get();

        return $this->success([
            'emission_point_id' => $request->integer('emission_point_id'),
            'sequentials' => $rows->map(fn (SequentialNumber $row) => $this->sequentialPayload($row))->values(),
        ]);
    }

    /**
     * POST onboarding/sequentials
     *
     * SRI-CRITICAL. Upserts starting sequential numbers for a migrating user.
     *
     * `last_number` is the LAST document number the user already issued.
     * We store it as `current_number` because EmissionPoint::getNextSequential()
     * increments `current_number` first and returns it, so the next issued
     * document = current_number + 1. This preserves SRI numbering continuity.
     */
    public function storeSequentials(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $validTypes = array_map(fn (DocumentType $t) => $t->value, DocumentType::cases());

        $data = $request->validate([
            'emission_point_id' => [
                'required',
                Rule::exists('emission_points', 'id')->where('tenant_id', $tenant->id),
            ],
            'sequentials' => ['required', 'array', 'min:1'],
            'sequentials.*.document_type' => ['required', Rule::in($validTypes)],
            'sequentials.*.last_number' => ['required', 'integer', 'min:0'],
        ]);

        $emissionPointId = (int) $data['emission_point_id'];

        $rows = DB::transaction(function () use ($tenant, $emissionPointId, $data) {
            $result = [];

            foreach ($data['sequentials'] as $item) {
                $result[] = SequentialNumber::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'emission_point_id' => $emissionPointId,
                        'document_type' => $item['document_type'],
                    ],
                    [
                        'current_number' => (int) $item['last_number'],
                    ]
                );
            }

            return $result;
        });

        return $this->success([
            'emission_point_id' => $emissionPointId,
            'sequentials' => collect($rows)
                ->map(fn (SequentialNumber $row) => $this->sequentialPayload($row))
                ->values(),
        ], 'Secuenciales configurados correctamente.');
    }

    /**
     * POST onboarding/complete
     *
     * Marks onboarding as finished by merging the flag into tenant settings.
     */
    public function complete(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $settings = $tenant->settings ?? [];
        $settings['onboarding_completed'] = true;
        $settings['onboarding_completed_at'] = now()->toIso8601String();
        $tenant->update(['settings' => $settings]);

        return $this->success([
            'completed' => true,
        ], 'Onboarding completado correctamente.');
    }

    // ==================== HELPERS ====================

    private function resolveCompany(Request $request): ?Company
    {
        return Company::where('tenant_id', $request->user()->tenant_id)->first();
    }

    private function companyPayload(Company $company): array
    {
        return [
            'id' => $company->id,
            'ruc' => $company->ruc,
            'business_name' => $company->business_name,
            'trade_name' => $company->trade_name,
            'address' => $company->address,
            'city' => $company->city,
            'province' => $company->province,
            'phone' => $company->phone,
            'email' => $company->email,
            // Return frontend-facing value: DB "juridical" -> "sociedad".
            'taxpayer_type' => $company->taxpayer_type === 'juridical' ? 'sociedad' : $company->taxpayer_type,
            'obligated_accounting' => (bool) $company->obligated_accounting,
            'rimpe_type' => $company->rimpe_type,
            'sri_environment' => $company->sri_environment,
            'is_active' => (bool) $company->is_active,
        ];
    }

    private function sequentialPayload(SequentialNumber $row): array
    {
        $type = $row->document_type instanceof DocumentType
            ? $row->document_type
            : DocumentType::from((string) $row->document_type);

        return [
            'id' => $row->id,
            'document_type' => $type->value,
            'document_type_label' => $type->label(),
            'current_number' => (int) $row->current_number,
            // Next document number that will actually be issued.
            'next_number' => (int) $row->current_number + 1,
        ];
    }
}
