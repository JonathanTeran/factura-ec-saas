<?php

namespace App\Livewire\Panel\Pos;

use App\Models\Tenant\PosSession;
use App\Models\Tenant\PosTransaction;
use App\Services\Pos\PosService;
use Livewire\Component;
use Livewire\WithPagination;

class PosHistory extends Component
{
    use WithPagination;

    public string $status = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    // Session detail modal
    public ?int $selectedSessionId = null;
    public bool $showSessionDetail = false;

    // Transaction detail modal
    public ?int $selectedTransactionId = null;
    public bool $showTransactionDetail = false;

    // Void confirmation
    public bool $showVoidConfirm = false;
    public ?int $voidTransactionId = null;

    public function getSessionsProperty()
    {
        $query = PosSession::where('tenant_id', auth()->user()->tenant_id)
            ->with(['branch', 'emissionPoint', 'openedByUser', 'closedByUser'])
            ->withCount('transactions');

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->dateFrom) {
            $query->where('opened_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('opened_at', '<=', $this->dateTo . ' 23:59:59');
        }

        return $query->orderByDesc('opened_at')
            ->paginate(15);
    }

    public function viewSession(int $sessionId): void
    {
        $this->selectedSessionId = $sessionId;
        $this->showSessionDetail = true;
    }

    public function closeSessionDetail(): void
    {
        $this->showSessionDetail = false;
        $this->selectedSessionId = null;
    }

    public function getSelectedSessionProperty(): ?PosSession
    {
        if (!$this->selectedSessionId) return null;

        return PosSession::where('tenant_id', auth()->user()->tenant_id)
            ->with([
                'branch',
                'emissionPoint',
                'company',
                'openedByUser',
                'closedByUser',
                'transactions' => fn($q) => $q->orderByDesc('created_at'),
                'transactions.items',
                'transactions.customer',
            ])
            ->find($this->selectedSessionId);
    }

    public function viewTransaction(int $transactionId): void
    {
        $this->selectedTransactionId = $transactionId;
        $this->showTransactionDetail = true;
    }

    public function closeTransactionDetail(): void
    {
        $this->showTransactionDetail = false;
        $this->selectedTransactionId = null;
    }

    public function getSelectedTransactionProperty(): ?PosTransaction
    {
        if (!$this->selectedTransactionId) return null;

        return PosTransaction::where('tenant_id', auth()->user()->tenant_id)
            ->with(['items', 'items.product', 'customer', 'session.branch', 'session.emissionPoint'])
            ->find($this->selectedTransactionId);
    }

    public function confirmVoid(int $transactionId): void
    {
        $this->voidTransactionId = $transactionId;
        $this->showVoidConfirm = true;
    }

    public function cancelVoid(): void
    {
        $this->showVoidConfirm = false;
        $this->voidTransactionId = null;
    }

    public function voidTransaction(PosService $service): void
    {
        if (!$this->voidTransactionId) return;

        $transaction = PosTransaction::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($this->voidTransactionId);

        try {
            $service
                ->forTenant(auth()->user()->tenant)
                ->voidTransaction($transaction);

            $this->showVoidConfirm = false;
            $this->voidTransactionId = null;

            $this->dispatch('notify', ['type' => 'success', 'message' => 'Transaccion #' . $transaction->transaction_number . ' anulada exitosamente.']);
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.panel.pos.pos-history', [
            'sessions' => $this->sessions,
            'selectedSession' => $this->selectedSession,
            'selectedTransaction' => $this->selectedTransaction,
        ])->layout('layouts.tenant', ['title' => 'Historial de Caja']);
    }
}
