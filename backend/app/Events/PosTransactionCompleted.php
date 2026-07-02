<?php

namespace App\Events;

use App\Models\Tenant\PosTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PosTransactionCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PosTransaction $transaction,
    ) {}
}
