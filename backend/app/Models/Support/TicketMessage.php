<?php

namespace App\Models\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketMessage extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'is_admin_reply',
        'message',
        'attachments',
    ];

    protected $casts = [
        'is_admin_reply' => 'boolean',
        'attachments'    => 'array',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
