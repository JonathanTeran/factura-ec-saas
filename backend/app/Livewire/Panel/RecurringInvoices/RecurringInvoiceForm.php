<?php

namespace App\Livewire\Panel\RecurringInvoices;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Company;
use App\Models\Tenant\Customer;
use App\Models\Tenant\EmissionPoint;
use App\Models\Tenant\Product;
use App\Models\Tenant\RecurringInvoice;
use Livewire\Component;

class RecurringInvoiceForm extends Component
{
    public ?int $recurringInvoiceId = null;

    // Form fields
    public ?int $company_id = null;
    public ?int $branch_id = null;
    public ?int $emission_point_id = null;
    public ?int $customer_id = null;
    public string $frequency = 'monthly';
    public string $start_date = '';
    public ?string $end_date = null;
    public ?int $max_issues = null;
    public string $notes = '';
    public string $currency = 'DOLAR';
    public bool $notify_before_issue = true;
    public int $notify_days_before = 1;

    // Items
    public array $items = [];

    // Payment
    public string $payment_method = '01';
    public ?int $payment_due_days = null;

    // Customer search
    public string $customerSearch = '';

    protected function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'emission_point_id' => ['required', 'exists:emission_points,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'frequency' => ['required', 'in:weekly,biweekly,monthly,bimonthly,quarterly,semiannual,annual'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'max_issues' => ['nullable', 'integer', 'min:1'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['required', 'numeric'],
        ];
    }

    protected function messages(): array
    {
        return [
            'customer_id.required' => 'Selecciona un cliente.',
            'items.required' => 'Agrega al menos un producto.',
            'items.min' => 'Agrega al menos un producto.',
            'start_date.required' => 'La fecha de inicio es obligatoria.',
        ];
    }

    public function mount(?int $id = null): void
    {
        if (!auth()->user()->tenant?->hasFeature('recurring_invoices')) {
            abort(403, 'Tu plan no incluye facturación recurrente.');
        }

        $this->start_date = now()->addDay()->toDateString();

        if ($id) {
            $this->recurringInvoiceId = $id;
            $this->loadRecurringInvoice($id);
        } else {
            $this->addItem();
            $this->loadDefaults();
        }
    }

    protected function loadRecurringInvoice(int $id): void
    {
        $recurring = RecurringInvoice::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);

        $this->company_id = $recurring->company_id;
        $this->branch_id = $recurring->branch_id;
        $this->emission_point_id = $recurring->emission_point_id;
        $this->customer_id = $recurring->customer_id;
        $this->frequency = $recurring->frequency;
        $this->start_date = $recurring->start_date->toDateString();
        $this->end_date = $recurring->end_date?->toDateString();
        $this->max_issues = $recurring->max_issues;
        $this->notes = $recurring->notes ?? '';
        $this->currency = $recurring->currency;
        $this->notify_before_issue = $recurring->notify_before_issue;
        $this->notify_days_before = $recurring->notify_days_before;
        $this->items = $recurring->items;

        if ($recurring->payment_methods) {
            $this->payment_method = $recurring->payment_methods[0]['code'] ?? '01';
            $this->payment_due_days = $recurring->payment_methods[0]['due_days'] ?? null;
        }
    }

    protected function loadDefaults(): void
    {
        $tenant = auth()->user()->tenant;
        $company = $tenant->companies()->first();

        if ($company) {
            $this->company_id = $company->id;
            $branch = $company->branches()->first();

            if ($branch) {
                $this->branch_id = $branch->id;
                $ep = $branch->emissionPoints()->first();

                if ($ep) {
                    $this->emission_point_id = $ep->id;
                }
            }
        }
    }

    public function addItem(): void
    {
        $this->items[] = [
            'product_id' => null,
            'main_code' => '',
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'discount' => 0,
            'tax_code' => '2',
            'tax_percentage_code' => '4',
            'tax_rate' => 15,
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

    public function selectProduct(int $index, int $productId): void
    {
        $product = Product::where('tenant_id', auth()->user()->tenant_id)->find($productId);

        if ($product) {
            $this->items[$index]['product_id'] = $product->id;
            $this->items[$index]['main_code'] = $product->code;
            $this->items[$index]['description'] = $product->name;
            $this->items[$index]['unit_price'] = (float) $product->price;
            $this->items[$index]['tax_rate'] = (float) ($product->tax_rate ?? 15);
            $this->items[$index]['tax_percentage_code'] = $product->tax_percentage_code ?? '4';
        }
    }

    public function getEstimatedTotalProperty(): float
    {
        return collect($this->items)->sum(function ($item) {
            $subtotal = (($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0)) - ($item['discount'] ?? 0);

            return $subtotal + round($subtotal * (($item['tax_rate'] ?? 0) / 100), 2);
        });
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'tenant_id' => auth()->user()->tenant_id,
            'company_id' => $this->company_id,
            'branch_id' => $this->branch_id,
            'emission_point_id' => $this->emission_point_id,
            'customer_id' => $this->customer_id,
            'created_by' => auth()->id(),
            'frequency' => $this->frequency,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date ?: null,
            'next_issue_date' => $this->start_date,
            'max_issues' => $this->max_issues,
            'items' => $this->items,
            'payment_methods' => [[
                'code' => $this->payment_method,
                'total' => $this->estimatedTotal,
                'due_days' => $this->payment_due_days,
            ]],
            'notes' => $this->notes ?: null,
            'currency' => $this->currency,
            'notify_before_issue' => $this->notify_before_issue,
            'notify_days_before' => $this->notify_days_before,
        ];

        if ($this->recurringInvoiceId) {
            $recurring = RecurringInvoice::where('tenant_id', auth()->user()->tenant_id)
                ->findOrFail($this->recurringInvoiceId);
            $recurring->update($data);
            $message = 'Factura recurrente actualizada.';
        } else {
            $data['status'] = 'active';
            RecurringInvoice::create($data);
            $message = 'Factura recurrente creada.';
        }

        $this->dispatch('notify', ['type' => 'success', 'message' => $message]);

        $this->redirect(route('panel.recurring-invoices.index'), navigate: true);
    }

    public function getCustomersProperty()
    {
        if (strlen($this->customerSearch) < 2) {
            return collect();
        }

        return Customer::where('tenant_id', auth()->user()->tenant_id)
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->customerSearch}%")
                    ->orWhere('identification', 'like', "%{$this->customerSearch}%");
            })
            ->limit(10)
            ->get();
    }

    public function getCompaniesProperty()
    {
        return Company::where('tenant_id', auth()->user()->tenant_id)->get();
    }

    public function getBranchesProperty()
    {
        if (!$this->company_id) {
            return collect();
        }

        return Branch::where('company_id', $this->company_id)->get();
    }

    public function getEmissionPointsProperty()
    {
        if (!$this->branch_id) {
            return collect();
        }

        return EmissionPoint::where('branch_id', $this->branch_id)->get();
    }

    public function render()
    {
        return view('livewire.panel.recurring-invoices.recurring-invoice-form', [
            'companies' => $this->companies,
            'branches' => $this->branches,
            'emissionPoints' => $this->emissionPoints,
            'estimatedTotal' => $this->estimatedTotal,
        ])->layout('layouts.tenant', [
            'title' => $this->recurringInvoiceId ? 'Editar Factura Recurrente' : 'Nueva Factura Recurrente',
        ]);
    }
}
