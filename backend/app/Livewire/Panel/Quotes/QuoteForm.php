<?php

namespace App\Livewire\Panel\Quotes;

use App\Enums\QuoteStatus;
use App\Models\Tenant\Company;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Quote;
use App\Services\Quote\QuoteService;
use Livewire\Component;

class QuoteForm extends Component
{
    public ?int $quoteId = null;
    public int $companyId = 0;
    public int $customerId = 0;
    public string $issueDate = '';
    public string $expiryDate = '';
    public string $notes = '';
    public string $paymentTerms = '';
    public array $items = [];

    public function mount(?int $quote = null): void
    {
        $this->issueDate  = now()->format('Y-m-d');
        $this->expiryDate = now()->addDays(30)->format('Y-m-d');

        if ($quote) {
            $this->quoteId = $quote;
            $q = Quote::where('tenant_id', auth()->user()->tenant_id)
                ->with('items')
                ->findOrFail($quote);

            $this->companyId    = $q->company_id;
            $this->customerId   = $q->customer_id;
            $this->issueDate    = $q->issue_date->format('Y-m-d');
            $this->expiryDate   = $q->expiry_date?->format('Y-m-d') ?? '';
            $this->notes        = $q->notes ?? '';
            $this->paymentTerms = $q->payment_terms ?? '';

            $this->items = $q->items->map(fn ($item) => [
                'description' => $item->description,
                'quantity'    => (float) $item->quantity,
                'unit_price'  => (float) $item->unit_price,
                'discount'    => (float) $item->discount,
                'tax_rate'    => (float) $item->tax_rate,
                'product_id'  => $item->product_id,
            ])->toArray();
        }

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    public function addItem(): void
    {
        $this->items[] = [
            'description' => '',
            'quantity'    => 1,
            'unit_price'  => 0,
            'discount'    => 0,
            'tax_rate'    => 15,
            'product_id'  => null,
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    public function getSubtotalProperty(): float
    {
        return collect($this->items)->sum(fn ($item) => ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0) - ($item['discount'] ?? 0));
    }

    public function getTotalTaxProperty(): float
    {
        return collect($this->items)->sum(function ($item) {
            $sub = ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0) - ($item['discount'] ?? 0);
            return $sub * (($item['tax_rate'] ?? 0) / 100);
        });
    }

    public function getTotalProperty(): float
    {
        return $this->subtotal + $this->totalTax;
    }

    public function getCompaniesProperty()
    {
        return Company::where('tenant_id', auth()->user()->tenant_id)->where('is_active', true)->orderBy('business_name')->get();
    }

    public function getCustomersProperty()
    {
        return Customer::where('tenant_id', auth()->user()->tenant_id)->where('is_active', true)->orderBy('name')->get();
    }

    protected function rules(): array
    {
        return [
            'companyId'               => ['required', 'integer', 'min:1'],
            'customerId'              => ['required', 'integer', 'min:1'],
            'issueDate'               => ['required', 'date'],
            'expiryDate'              => ['nullable', 'date', 'after_or_equal:issueDate'],
            'items'                   => ['required', 'array', 'min:1'],
            'items.*.description'     => ['required', 'string', 'max:300'],
            'items.*.quantity'        => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price'      => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function messages(): array
    {
        return [
            'companyId.required'           => 'Selecciona una empresa.',
            'customerId.required'          => 'Selecciona un cliente.',
            'issueDate.required'           => 'La fecha de emisión es obligatoria.',
            'items.*.description.required' => 'La descripción del item es obligatoria.',
            'items.*.quantity.min'         => 'La cantidad debe ser mayor a 0.',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $tenantId = auth()->user()->tenant_id;
        $service  = new QuoteService();

        $data = [
            'tenant_id'    => $tenantId,
            'company_id'   => $this->companyId,
            'customer_id'  => $this->customerId,
            'created_by'   => auth()->id(),
            'issue_date'   => $this->issueDate,
            'expiry_date'  => $this->expiryDate ?: null,
            'notes'        => $this->notes ?: null,
            'payment_terms' => $this->paymentTerms ?: null,
            'status'       => QuoteStatus::DRAFT->value,
        ];

        if ($this->quoteId) {
            $quote = Quote::where('tenant_id', $tenantId)->findOrFail($this->quoteId);
            $service->update($quote, $data, $this->items);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Cotización actualizada correctamente.']);
        } else {
            $data['quote_number'] = $service->generateQuoteNumber($tenantId);
            $service->create($data, $this->items);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Cotización creada correctamente.']);
        }

        $this->redirect(route('panel.quotes.index'));
    }

    public function render()
    {
        return view('livewire.panel.quotes.quote-form', [
            'companies' => $this->companies,
            'customers' => $this->customers,
        ])->layout('layouts.tenant', ['title' => $this->quoteId ? 'Editar Cotización' : 'Nueva Cotización']);
    }
}
