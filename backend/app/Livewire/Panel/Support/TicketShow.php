<?php

namespace App\Livewire\Panel\Support;

use App\Enums\TicketStatus;
use App\Models\Support\SupportTicket;
use App\Models\Support\TicketMessage;
use Livewire\Component;

class TicketShow extends Component
{
    public SupportTicket $ticket;
    public string $newMessage = '';

    public function mount(SupportTicket $ticket): void
    {
        // Ensure tenant isolation
        abort_if($ticket->tenant_id !== auth()->user()->tenant_id, 404);
        $this->ticket = $ticket->load(['messages.user', 'user']);
    }

    protected function rules(): array
    {
        return [
            'newMessage' => ['required', 'string', 'min:5'],
        ];
    }

    public function sendMessage(): void
    {
        $this->validate();

        abort_if($this->ticket->isResolved(), 403, 'El ticket está cerrado.');

        TicketMessage::create([
            'ticket_id'     => $this->ticket->id,
            'user_id'       => auth()->id(),
            'is_admin_reply' => false,
            'message'       => $this->newMessage,
        ]);

        // Reopen if it was waiting for customer response
        if ($this->ticket->status === TicketStatus::WAITING_CUSTOMER) {
            $this->ticket->update(['status' => TicketStatus::OPEN]);
        }

        $this->newMessage = '';
        $this->ticket = $this->ticket->fresh(['messages.user', 'user']);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Mensaje enviado.']);
    }

    public function resolve(): void
    {
        abort_if($this->ticket->tenant_id !== auth()->user()->tenant_id, 403);
        $this->ticket->resolve();
        $this->ticket = $this->ticket->fresh(['messages.user', 'user']);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Ticket marcado como resuelto.']);
    }

    public function render()
    {
        return view('livewire.panel.support.ticket-show', [
            'ticket' => $this->ticket,
        ])->layout('layouts.tenant', ['title' => 'Ticket #' . $this->ticket->id]);
    }
}
