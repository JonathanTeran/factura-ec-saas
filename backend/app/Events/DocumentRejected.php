<?php

namespace App\Events;

use App\Models\SRI\ElectronicDocument;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentRejected implements ShouldBroadcast
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
        return 'document.rejected';
    }

    public function broadcastWith(): array
    {
        return [
            'document_id' => $this->document->id,
            'document_number' => $this->document->getDocumentNumber(),
            'document_type' => $this->document->document_type->label(),
            'errors' => $this->document->sri_errors,
        ];
    }
}
