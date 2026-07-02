<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    public $timestamps = false;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'subject_type',
        'subject_id',
        'event',
        'description',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    // ==================== RELACIONES ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    // ==================== FACTORY ====================

    public static function record(
        string $event,
        string $description,
        ?int $tenantId = null,
        ?int $userId = null,
        mixed $subject = null,
        array $properties = []
    ): self {
        return static::create([
            'tenant_id'    => $tenantId ?? (auth()->check() ? auth()->user()->tenant_id : null),
            'user_id'      => $userId ?? auth()->id(),
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->id,
            'event'        => $event,
            'description'  => $description,
            'properties'   => empty($properties) ? null : $properties,
            'ip_address'   => request()->ip(),
            'user_agent'   => substr(request()->userAgent() ?? '', 0, 500),
        ]);
    }
}
