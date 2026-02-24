<?php

namespace App\Livewire\Panel\RecurringInvoices;

use App\Models\Tenant\RecurringInvoice;
use Livewire\Component;
use Livewire\WithPagination;

class RecurringInvoiceList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $frequency = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'frequency' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function getHasFeatureProperty(): bool
    {
        return auth()->user()->tenant?->hasFeature('recurring_invoices') ?? false;
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status', 'frequency']);
    }

    public function toggleStatus(int $id): void
    {
        $recurring = RecurringInvoice::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);

        if ($recurring->status === 'active') {
            $recurring->update(['status' => 'paused']);
            $this->dispatch('notify', ['type' => 'info', 'message' => 'Factura recurrente pausada.']);
        } elseif ($recurring->status === 'paused') {
            $recurring->update(['status' => 'active']);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Factura recurrente activada.']);
        }
    }

    public function cancel(int $id): void
    {
        $recurring = RecurringInvoice::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);

        $recurring->update(['status' => 'cancelled']);

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Factura recurrente cancelada.']);
    }

    public function delete(int $id): void
    {
        $recurring = RecurringInvoice::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);

        if ($recurring->total_issued > 0) {
            $recurring->delete(); // soft delete
        } else {
            $recurring->forceDelete();
        }

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Factura recurrente eliminada.']);
    }

    public function render()
    {
        $query = RecurringInvoice::where('tenant_id', auth()->user()->tenant_id)
            ->with(['customer', 'company']);

        if ($this->search) {
            $query->whereHas('customer', function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('identification', 'like', "%{$this->search}%");
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->frequency) {
            $query->where('frequency', $this->frequency);
        }

        return view('livewire.panel.recurring-invoices.recurring-invoice-list', [
            'recurringInvoices' => $query->latest()->paginate(15),
            'hasFeature' => $this->hasFeature,
        ])->layout('layouts.tenant', ['title' => 'Facturas Recurrentes']);
    }
}
