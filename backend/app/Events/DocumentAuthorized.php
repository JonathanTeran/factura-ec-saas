<?php

namespace App\Events;

use App\Models\SRI\ElectronicDocument;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentAuthorized implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ElectronicDocument $document
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->document->tenant_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'document.authorized';
    }

    public function broadcastWith(): array
    {
        return [
            'document_id' => $this->document->id,
            'document_number' => $this->document->getDocumentNumber(),
            'document_type' => $this->document->document_type->label(),
            'customer_name' => $this->document->customer?->name,
            'total' => $this->document->total,
            'authorization_number' => $this->document->authorization_number,
        ];
    }
}
