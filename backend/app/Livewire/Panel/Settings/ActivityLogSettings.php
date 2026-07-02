<?php

namespace App\Livewire\Panel\Settings;

use App\Models\ActivityLog;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLogSettings extends Component
{
    use WithPagination;

    public string $event = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public function updatingEvent(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset(['event', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function getLogsProperty()
    {
        $tenantId = auth()->user()->tenant_id;

        $query = ActivityLog::where('tenant_id', $tenantId)
            ->with('user')
            ->orderByDesc('created_at');

        if ($this->event) {
            $query->where('event', $this->event);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        return $query->paginate(25);
    }

    public function getEventsProperty(): array
    {
        return ActivityLog::where('tenant_id', auth()->user()->tenant_id)
            ->distinct()
            ->orderBy('event')
            ->pluck('event')
            ->toArray();
    }

    public function render()
    {
        return view('livewire.panel.settings.activity-log', [
            'logs'   => $this->logs,
            'events' => $this->events,
        ])->layout('layouts.tenant', ['title' => 'Historial de Actividad']);
    }
}
