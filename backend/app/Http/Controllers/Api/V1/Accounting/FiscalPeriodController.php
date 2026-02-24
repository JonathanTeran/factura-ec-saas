<?php

namespace App\Http\Controllers\Api\V1\Accounting;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Accounting\FiscalPeriod;
use App\Services\Accounting\FiscalPeriodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FiscalPeriodController extends ApiController
{
    public function __construct(
        private readonly FiscalPeriodService $fiscalPeriodService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = FiscalPeriod::where('company_id', $request->user()->current_company_id ?? 0)
            ->orderByDesc('year')
            ->orderBy('month');

        if ($request->has('year')) {
            $query->where('year', $request->input('year'));
        }

        return $this->success(['periods' => $query->get()]);
    }

    public function createYear(Request $request): JsonResponse
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2099'],
        ]);

        $company = $request->user()->currentCompany;

        $periods = $this->fiscalPeriodService
            ->forCompany($company)
            ->createPeriodsForYear($request->input('year'));

        return $this->created(['periods' => $periods], 'Periodos fiscales creados exitosamente');
    }

    public function close(Request $request, FiscalPeriod $period): JsonResponse
    {
        $this->authorizePeriod($request, $period);
        $company = $request->user()->currentCompany;

        try {
            $period = $this->fiscalPeriodService
                ->forCompany($company)
                ->closePeriod($period);

            return $this->success(['period' => $period], 'Periodo cerrado exitosamente');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }
    }

    public function lock(Request $request, FiscalPeriod $period): JsonResponse
    {
        $this->authorizePeriod($request, $period);
        $company = $request->user()->currentCompany;

        try {
            $period = $this->fiscalPeriodService
                ->forCompany($company)
                ->lockPeriod($period);

            return $this->success(['period' => $period], 'Periodo bloqueado exitosamente');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }
    }

    public function reopen(Request $request, FiscalPeriod $period): JsonResponse
    {
        $this->authorizePeriod($request, $period);
        $company = $request->user()->currentCompany;

        try {
            $period = $this->fiscalPeriodService
                ->forCompany($company)
                ->reopenPeriod($period);

            return $this->success(['period' => $period], 'Periodo reabierto exitosamente');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }
    }

    protected function authorizePeriod(Request $request, FiscalPeriod $period): void
    {
        if ($period->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'No tienes acceso a este periodo.');
        }
    }
}
