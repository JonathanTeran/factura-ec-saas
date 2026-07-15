<?php

namespace App\Models\Arbitros;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Solicitud de campeonato/club faltante en el catálogo (POR TENANT).
 * El super admin la aprueba (creando la entrada en el catálogo) o la rechaza.
 */
class CatalogRequest extends Model
{
    use HasFactory, BelongsToTenant;

    public const TYPE_CHAMPIONSHIP = 'championship';
    public const TYPE_CLUB = 'club';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'tenant_id',
        'type',
        'name',
        'comment',
        'status',
        'resolution_note',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /** Aprueba la solicitud creando la entrada correspondiente del catálogo. */
    public function approve(?string $note = null): void
    {
        if ($this->type === self::TYPE_CHAMPIONSHIP) {
            Championship::firstOrCreate(
                ['name' => $this->name],
                ['is_active' => true]
            );
        } else {
            Club::firstOrCreate(['name' => $this->name]);
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'resolution_note' => $note,
            'resolved_at' => now(),
        ]);
    }

    public function reject(?string $note = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'resolution_note' => $note,
            'resolved_at' => now(),
        ]);
    }
}
