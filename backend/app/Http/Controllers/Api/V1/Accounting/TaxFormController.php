<?php

namespace App\Http\Controllers\Api\V1\Accounting;

use App\Enums\TaxFormType;
use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Accounting\TaxFormSubmission;
use App\Services\Accounting\ATSService;
use App\Services\Accounting\TaxFormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxFormController extends ApiController
{
    public function __construct(
        private readonly TaxFormService $taxFormService,
        private readonly ATSService $atsService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $submissions = TaxFormSubmission::where('company_id', $request->user()->current_company_id ?? 0)
            ->orderByDesc('fiscal_year')
            ->orderByDesc('fiscal_month')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $submissions->items(),
            'meta' => [
                'current_page' => $submissions->currentPage(),
                'last_page' => $submissions->lastPage(),
                'per_page' => $submissions->perPage(),
                'total' => $submissions->total(),
            ],
        ]);
    }

    public function generate(Request $request, string $type): JsonResponse
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2020'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $formType = TaxFormType::tryFrom($type);
        if (!$formType) {
            return $this->error('Tipo de formulario no valido.');
        }

        $company = $request->user()->currentCompany;
        $year = $request->input('year');
        $month = $request->input('month');

        try {
            if ($formType === TaxFormType::ATS) {
                $data = $this->atsService->forCompany($company)->generate($year, $month);
            } else {
                $this->taxFormService->forCompany($company);
                $data = match ($formType) {
                    TaxFormType::F103 => $this->taxFormService->generateF103($year, $month),
                    TaxFormType::F104 => $this->taxFormService->generateF104($year, $month),
                    TaxFormType::F101 => $this->taxFormService->generateF101($year),
                    TaxFormType::F102 => $this->taxFormService->generateF102($year),
                    default => throw new \RuntimeException('Formulario no implementado.'),
                };
            }

            // Save submission
            $submission = $this->taxFormService
                ->forCompany($company)
                ->saveSubmission($data, $formType);

            return $this->success([
                'form_data' => $data,
                'submission' => $submission,
            ], 'Formulario generado exitosamente');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function download(Request $request, TaxFormSubmission $submission): JsonResponse
    {
        if ($submission->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        return $this->success([
            'submission' => $submission,
            'data' => $submission->generated_data,
        ]);
    }
}
