<?php

namespace App\Livewire\Panel\Support;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Support\SupportTicket;
use Livewire\Component;
use Livewire\WithPagination;

class TicketList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $priority = '';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'search'   => ['except' => ''],
        'status'   => ['except' => ''],
        'priority' => ['except' => ''],
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
        $this->reset(['search', 'status', 'priority']);
        $this->resetPage();
    }

    public function getTicketsProperty()
    {
        $query = SupportTicket::where('tenant_id', auth()->user()->tenant_id)
            ->with('user');

        if ($this->search) {
            $query->where('subject', 'like', "%{$this->search}%");
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->priority) {
            $query->where('priority', $this->priority);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->paginate(15);
    }

    public function getStatsProperty(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'total'       => SupportTicket::where('tenant_id', $tenantId)->count(),
            'open'        => SupportTicket::where('tenant_id', $tenantId)->where('status', TicketStatus::OPEN->value)->count(),
            'in_progress' => SupportTicket::where('tenant_id', $tenantId)->where('status', TicketStatus::IN_PROGRESS->value)->count(),
            'resolved'    => SupportTicket::where('tenant_id', $tenantId)->whereIn('status', [TicketStatus::RESOLVED->value, TicketStatus::CLOSED->value])->count(),
        ];
    }

    public function render()
    {
        return view('livewire.panel.support.ticket-list', [
            'tickets'    => $this->tickets,
            'stats'      => $this->stats,
            'statuses'   => TicketStatus::cases(),
            'priorities' => TicketPriority::cases(),
        ])->layout('layouts.tenant', ['title' => 'Soporte']);
    }
}
