<?php

namespace App\Http\Controllers\Api\V1\Accounting;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\Accounting\AccountingService;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Accounting\FinancialReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountingReportController extends ApiController
{
    public function __construct(
        private readonly FinancialReportService $reportService,
        private readonly ChartOfAccountsService $chartService,
        private readonly AccountingService $accountingService,
    ) {}

    public function trialBalance(Request $request): JsonResponse
    {
        $company = $request->user()->currentCompany;
        $this->reportService->forCompany($company);

        $data = $this->reportService->getTrialBalance(
            $request->input('from'),
            $request->input('to'),
        );

        return $this->success($data);
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        $company = $request->user()->currentCompany;
        $this->reportService->forCompany($company);

        $data = $this->reportService->getBalanceSheet(
            $request->input('to'),
        );

        return $this->success($data);
    }

    public function incomeStatement(Request $request): JsonResponse
    {
        $company = $request->user()->currentCompany;
        $this->reportService->forCompany($company);

        $data = $this->reportService->getIncomeStatement(
            $request->input('from'),
            $request->input('to'),
        );

        return $this->success($data);
    }

    public function generalLedger(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => ['required', 'exists:chart_of_accounts,id'],
        ]);

        $company = $request->user()->currentCompany;
        $this->accountingService->forCompany($company);

        $data = $this->accountingService->getGeneralLedger(
            $request->input('account_id'),
            $request->input('from'),
            $request->input('to'),
        );

        return $this->success(['movements' => $data]);
    }

    public function cashFlow(Request $request): JsonResponse
    {
        $company = $request->user()->currentCompany;
        $this->reportService->forCompany($company);

        $data = $this->reportService->getCashFlowStatement(
            $request->input('from'),
            $request->input('to'),
        );

        return $this->success($data);
    }
}
