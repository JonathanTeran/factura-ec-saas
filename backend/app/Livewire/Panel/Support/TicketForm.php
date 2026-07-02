<?php

namespace App\Livewire\Panel\Support;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Support\SupportTicket;
use App\Models\Support\TicketMessage;
use Livewire\Component;

class TicketForm extends Component
{
    public string $subject = '';
    public string $category = 'general';
    public string $priority = 'medium';
    public string $message = '';

    protected function rules(): array
    {
        return [
            'subject'  => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:technical,billing,sri,general,feature_request'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'message'  => ['required', 'string', 'min:10'],
        ];
    }

    protected function messages(): array
    {
        return [
            'subject.required' => 'El asunto es obligatorio.',
            'message.required' => 'El mensaje es obligatorio.',
            'message.min'      => 'El mensaje debe tener al menos 10 caracteres.',
        ];
    }

    public function getCategoriesProperty(): array
    {
        return TicketCategory::cases();
    }

    public function getPrioritiesProperty(): array
    {
        return TicketPriority::cases();
    }

    public function save(): void
    {
        $this->validate();

        $ticket = SupportTicket::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id'   => auth()->id(),
            'subject'   => $this->subject,
            'category'  => $this->category,
            'priority'  => $this->priority,
            'status'    => TicketStatus::OPEN->value,
        ]);

        TicketMessage::create([
            'ticket_id'     => $ticket->id,
            'user_id'       => auth()->id(),
            'is_admin_reply' => false,
            'message'       => $this->message,
        ]);

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Ticket creado. Te responderemos pronto.']);
        $this->redirect(route('panel.support.show', $ticket));
    }

    public function render()
    {
        return view('livewire.panel.support.ticket-form', [
            'categories' => $this->categories,
            'priorities' => $this->priorities,
        ])->layout('layouts.tenant', ['title' => 'Nuevo Ticket']);
    }
}
