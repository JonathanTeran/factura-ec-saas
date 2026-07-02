<?php

namespace App\Livewire\Panel\Quotes;

use App\Enums\QuoteStatus;
use App\Models\Tenant\Quote;
use Livewire\Component;
use Livewire\WithPagination;

class QuoteList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $sortField = 'issue_date';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function getQuotesProperty()
    {
        $query = Quote::where('tenant_id', auth()->user()->tenant_id)
            ->with(['customer', 'company']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('quote_number', 'like', "%{$this->search}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->dateFrom) {
            $query->where('issue_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('issue_date', '<=', $this->dateTo);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->paginate(15);
    }

    public function getStatsProperty(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $thisMonth = now()->startOfMonth();

        return [
            'total'              => Quote::where('tenant_id', $tenantId)->count(),
            'this_month'         => Quote::where('tenant_id', $tenantId)->where('issue_date', '>=', $thisMonth)->count(),
            'total_amount_month' => (float) Quote::where('tenant_id', $tenantId)
                ->where('issue_date', '>=', $thisMonth)
                ->whereNotIn('status', [QuoteStatus::REJECTED->value, QuoteStatus::EXPIRED->value])
                ->sum('total'),
            'accepted'           => Quote::where('tenant_id', $tenantId)->where('status', QuoteStatus::ACCEPTED->value)->count(),
        ];
    }

    public function deleteQuote(int $quoteId): void
    {
        $quote = Quote::where('tenant_id', auth()->user()->tenant_id)->findOrFail($quoteId);

        if (!$quote->isDraft()) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Solo se pueden eliminar cotizaciones en borrador.']);
            return;
        }

        $quote->delete();
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Cotización eliminada.']);
    }

    public function render()
    {
        return view('livewire.panel.quotes.quote-list', [
            'quotes'   => $this->quotes,
            'stats'    => $this->stats,
            'statuses' => QuoteStatus::cases(),
        ])->layout('layouts.tenant', ['title' => 'Cotizaciones']);
    }
}
