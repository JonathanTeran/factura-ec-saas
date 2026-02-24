<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Models\SRI\DocumentItem;
use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\RecurringInvoice;
use App\Models\Tenant\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecurringInvoiceService
{
    public function processAllDue(): array
    {
        $results = ['processed' => 0, 'failed' => 0, 'skipped' => 0];

        $dueInvoices = RecurringInvoice::withoutTenantScope()
            ->dueToday()
            ->with(['company', 'branch', 'emissionPoint', 'customer', 'tenant'])
            ->get();

        foreach ($dueInvoices as $recurring) {
            try {
                if (!$this->canProcess($recurring)) {
                    $results['skipped']++;
                    continue;
                }

                $this->generateDocument($recurring);
                $results['processed']++;

            } catch (\Exception $e) {
                Log::error("Failed to process recurring invoice {$recurring->id}: {$e->getMessage()}");
                $results['failed']++;
            }
        }

        return $results;
    }

    public function generateDocument(RecurringInvoice $recurring): ElectronicDocument
    {
        return DB::transaction(function () use ($recurring) {
            $sequential = $this->getNextSequential($recurring);

            $document = ElectronicDocument::create([
                'tenant_id' => $recurring->tenant_id,
                'company_id' => $recurring->company_id,
                'branch_id' => $recurring->branch_id,
                'emission_point_id' => $recurring->emission_point_id,
                'customer_id' => $recurring->customer_id,
                'created_by' => $recurring->created_by,
                'document_type' => DocumentType::FACTURA->value,
                'environment' => $recurring->company->sri_environment ?? '1',
                'series' => sprintf(
                    '%s-%s',
                    $recurring->branch->code ?? '001',
                    $recurring->emissionPoint->code ?? '001'
                ),
                'sequential' => $sequential,
                'status' => 'draft',
                'issue_date' => now()->toDateString(),
                'due_date' => $recurring->payment_methods[0]['due_days'] ?? null
                    ? now()->addDays($recurring->payment_methods[0]['due_days'])->toDateString()
                    : null,
                'currency' => $recurring->currency,
                'payment_methods' => $recurring->payment_methods,
                'additional_info' => $recurring->additional_info,
                'notes' => $recurring->notes,
                'recurring_invoice_id' => $recurring->id,
            ]);

            // Create items
            $this->createDocumentItems($document, $recurring->items);

            // Calculate totals
            $document->load('items');
            $document->calculateTotals();

            // Advance recurring schedule
            $recurring->advanceToNextIssue();

            Log::info("Generated document {$document->id} from recurring invoice {$recurring->id}");

            return $document;
        });
    }

    protected function canProcess(RecurringInvoice $recurring): bool
    {
        $tenant = $recurring->tenant;

        if (!$tenant || !$tenant->hasFeature('recurring_invoices')) {
            Log::info("Tenant {$recurring->tenant_id} does not have recurring invoices feature");
            return false;
        }

        if (!$recurring->canIssue()) {
            return false;
        }

        if (!$tenant->canIssueDocuments()) {
            Log::info("Tenant {$recurring->tenant_id} has reached document limit");
            return false;
        }

        return true;
    }

    protected function getNextSequential(RecurringInvoice $recurring): string
    {
        $lastSequential = ElectronicDocument::withoutTenantScope()
            ->where('tenant_id', $recurring->tenant_id)
            ->where('company_id', $recurring->company_id)
            ->where('emission_point_id', $recurring->emission_point_id)
            ->where('document_type', DocumentType::FACTURA->value)
            ->max('sequential');

        $next = $lastSequential ? (int) $lastSequential + 1 : 1;

        return str_pad($next, 9, '0', STR_PAD_LEFT);
    }

    protected function createDocumentItems(ElectronicDocument $document, array $items): void
    {
        foreach ($items as $index => $item) {
            DocumentItem::create([
                'electronic_document_id' => $document->id,
                'product_id' => $item['product_id'] ?? null,
                'main_code' => $item['main_code'] ?? '',
                'aux_code' => $item['aux_code'] ?? null,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount' => $item['discount'] ?? 0,
                'subtotal' => ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0),
                'tax_code' => $item['tax_code'] ?? '2',
                'tax_percentage_code' => $item['tax_percentage_code'] ?? '2',
                'tax_rate' => $item['tax_rate'] ?? 15,
                'tax_base' => ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0),
                'tax_value' => round(
                    (($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0)) * (($item['tax_rate'] ?? 15) / 100),
                    2
                ),
                'sort_order' => $index,
            ]);
        }
    }
}
