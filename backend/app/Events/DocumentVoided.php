<?php

namespace App\Events;

use App\Models\SRI\ElectronicDocument;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentVoided
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ElectronicDocument $document,
        public string $reason
    ) {}
}
