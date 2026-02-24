<?php

namespace App\Services\Accounting;

use App\Enums\JournalEntrySource;
use App\Models\Accounting\Account;
use App\Models\Accounting\AccountMappingTemplate;
use App\Models\Accounting\JournalEntry;
use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\Company;
use App\Models\Tenant\Purchase;
use Illuminate\Support\Facades\Log;

class AutoJournalEntryService
{
    private Company $company;
    private AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function forCompany(Company $company): self
    {
        $this->company = $company;
        $this->accountingService->forCompany($company);
        return $this;
    }

    public function generateFromDocument(ElectronicDocument $document): ?JournalEntry
    {
        $sourceType = $this->getSourceTypeForDocument($document);
        if (!$sourceType) {
            return null;
        }

        // Buscar si ya existe un asiento para este documento
        $existing = JournalEntry::where('company_id', $this->company->id)
            ->where('source_document_type', get_class($document))
            ->where('source_document_id', $document->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $documentType = $this->mapDocumentTypeToMapping($document);
        $template = $this->getTemplate($documentType);

        if (!$template) {
            $lines = $this->getDefaultLinesForDocument($document, $sourceType);
        } else {
            $lines = $this->buildLinesFromTemplate($template, $document);
        }

        if (empty($lines)) {
            Log::warning("AutoJournalEntry: No se pudieron generar lineas para documento {$document->id}");
            return null;
        }

        try {
            $entry = $this->accountingService->createJournalEntry([
                'entry_date' => $document->authorization_date ?? $document->issue_date ?? now(),
                'description' => "Asiento automatico - {$sourceType->label()} #{$document->getDocumentNumber()}",
                'source_type' => $sourceType,
                'source_document_type' => get_class($document),
                'source_document_id' => $document->id,
            ], $lines);

            // Auto-contabilizar
            $this->accountingService->postJournalEntry($entry);

            return $entry;
        } catch (\Throwable $e) {
            Log::error("AutoJournalEntry error para documento {$document->id}: {$e->getMessage()}");
            return null;
        }
    }

    public function generateFromPurchase(Purchase $purchase): ?JournalEntry
    {
        $existing = JournalEntry::where('company_id', $this->company->id)
            ->where('source_document_type', get_class($purchase))
            ->where('source_document_id', $purchase->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $template = $this->getTemplate('purchase');

        if (!$template) {
            $lines = $this->getDefaultLinesForPurchase($purchase);
        } else {
            $lines = $this->buildLinesFromTemplateForPurchase($template, $purchase);
        }

        if (empty($lines)) {
            return null;
        }

        try {
            $entry = $this->accountingService->createJournalEntry([
                'entry_date' => $purchase->issue_date,
                'description' => "Compra - {$purchase->supplier->business_name} #{$purchase->supplier_document_number}",
                'source_type' => JournalEntrySource::AUTO_PURCHASE,
                'source_document_type' => get_class($purchase),
                'source_document_id' => $purchase->id,
            ], $lines);

            $this->accountingService->postJournalEntry($entry);

            return $entry;
        } catch (\Throwable $e) {
            Log::error("AutoJournalEntry error para compra {$purchase->id}: {$e->getMessage()}");
            return null;
        }
    }

    private function getSourceTypeForDocument(ElectronicDocument $document): ?JournalEntrySource
    {
        return match ($document->document_type->value ?? null) {
            '01' => JournalEntrySource::AUTO_INVOICE,
            '04' => JournalEntrySource::AUTO_CREDIT_NOTE,
            '05' => JournalEntrySource::AUTO_DEBIT_NOTE,
            '07' => JournalEntrySource::AUTO_RETENTION,
            '06' => JournalEntrySource::AUTO_LIQUIDATION,
            default => null,
        };
    }

    private function mapDocumentTypeToMapping(ElectronicDocument $document): string
    {
        return match ($document->document_type->value ?? null) {
            '01' => 'invoice',
            '04' => 'credit_note',
            '05' => 'debit_note',
            '07' => 'retention',
            '06' => 'liquidation',
            default => 'invoice',
        };
    }

    private function getTemplate(string $documentType): ?AccountMappingTemplate
    {
        return AccountMappingTemplate::where('company_id', $this->company->id)
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->first();
    }

    private function getDefaultLinesForDocument(ElectronicDocument $document, JournalEntrySource $sourceType): array
    {
        $subtotal = (float) ($document->subtotal ?? 0);
        $tax = (float) ($document->total_tax ?? 0);
        $total = (float) ($document->total ?? 0);

        if ($total <= 0) {
            return [];
        }

        $lines = [];

        if ($sourceType === JournalEntrySource::AUTO_INVOICE) {
            // Débito: Cuentas por cobrar (total)
            $cuentasCobrar = $this->findAccountByCode('1.01.02.05');
            // Crédito: Ventas (subtotal) + IVA por pagar (impuesto)
            $ventas = $this->findAccountByCode('4.01.01');
            $ivaPorPagar = $this->findAccountByCode('2.01.07.01');

            if ($cuentasCobrar && $ventas) {
                $lines[] = ['account_id' => $cuentasCobrar->id, 'debit' => $total, 'credit' => 0, 'description' => 'Cuentas por cobrar'];
                $lines[] = ['account_id' => $ventas->id, 'debit' => 0, 'credit' => $subtotal, 'description' => 'Ventas'];
                if ($tax > 0 && $ivaPorPagar) {
                    $lines[] = ['account_id' => $ivaPorPagar->id, 'debit' => 0, 'credit' => $tax, 'description' => 'IVA por pagar'];
                }
            }
        } elseif ($sourceType === JournalEntrySource::AUTO_CREDIT_NOTE) {
            $ventas = $this->findAccountByCode('4.01.01');
            $ivaPorPagar = $this->findAccountByCode('2.01.07.01');
            $cuentasCobrar = $this->findAccountByCode('1.01.02.05');

            if ($cuentasCobrar && $ventas) {
                $lines[] = ['account_id' => $ventas->id, 'debit' => $subtotal, 'credit' => 0, 'description' => 'Devolucion ventas'];
                if ($tax > 0 && $ivaPorPagar) {
                    $lines[] = ['account_id' => $ivaPorPagar->id, 'debit' => $tax, 'credit' => 0, 'description' => 'IVA por pagar (reverso)'];
                }
                $lines[] = ['account_id' => $cuentasCobrar->id, 'debit' => 0, 'credit' => $total, 'description' => 'Cuentas por cobrar'];
            }
        }

        return $lines;
    }

    private function getDefaultLinesForPurchase(Purchase $purchase): array
    {
        $subtotal = (float) ($purchase->subtotal ?? 0);
        $tax = (float) ($purchase->total_tax ?? 0);
        $total = (float) ($purchase->total ?? 0);

        if ($total <= 0) {
            return [];
        }

        $gasto = $this->findAccountByCode('6.02.01');
        $creditoTributario = $this->findAccountByCode('1.01.05.01');
        $cuentasPagar = $this->findAccountByCode('2.01.03.01');

        $lines = [];

        if ($gasto && $cuentasPagar) {
            $lines[] = ['account_id' => $gasto->id, 'debit' => $subtotal, 'credit' => 0, 'description' => 'Gasto por compra'];
            if ($tax > 0 && $creditoTributario) {
                $lines[] = ['account_id' => $creditoTributario->id, 'debit' => $tax, 'credit' => 0, 'description' => 'Credito tributario IVA'];
            }
            $lines[] = ['account_id' => $cuentasPagar->id, 'debit' => 0, 'credit' => $total, 'description' => 'Cuentas por pagar'];
        }

        return $lines;
    }

    private function buildLinesFromTemplate(AccountMappingTemplate $template, ElectronicDocument $document): array
    {
        $rules = $template->mapping_rules;
        $lines = [];

        foreach ($rules as $rule) {
            $account = $this->findAccountByCode($rule['account_code']);
            if (!$account) {
                continue;
            }

            $amount = $this->resolveAmount($rule['amount_field'], $document);

            if ($amount <= 0) {
                continue;
            }

            $lines[] = [
                'account_id' => $account->id,
                'debit' => $rule['side'] === 'debit' ? $amount : 0,
                'credit' => $rule['side'] === 'credit' ? $amount : 0,
                'description' => $rule['description'] ?? null,
            ];
        }

        return $lines;
    }

    private function buildLinesFromTemplateForPurchase(AccountMappingTemplate $template, Purchase $purchase): array
    {
        $rules = $template->mapping_rules;
        $lines = [];

        foreach ($rules as $rule) {
            $account = $this->findAccountByCode($rule['account_code']);
            if (!$account) {
                continue;
            }

            $amount = (float) ($purchase->{$rule['amount_field']} ?? 0);

            if ($amount <= 0) {
                continue;
            }

            $lines[] = [
                'account_id' => $account->id,
                'debit' => $rule['side'] === 'debit' ? $amount : 0,
                'credit' => $rule['side'] === 'credit' ? $amount : 0,
                'description' => $rule['description'] ?? null,
            ];
        }

        return $lines;
    }

    private function findAccountByCode(string $code): ?Account
    {
        return Account::where('company_id', $this->company->id)
            ->where('code', $code)
            ->where('is_active', true)
            ->first();
    }

    private function resolveAmount(string $field, ElectronicDocument $document): float
    {
        return (float) ($document->{$field} ?? 0);
    }
}
