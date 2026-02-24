<?php

namespace App\Models\SRI;

use App\Enums\DocumentType;
use App\Models\Tenant\EmissionPoint;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class SequentialNumber extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'emission_point_id',
        'document_type',
        'current_number',
    ];

    protected $casts = [
        'document_type' => DocumentType::class,
        'current_number' => 'integer',
    ];

    // ==================== RELACIONES ====================

    public function emissionPoint(): BelongsTo
    {
        return $this->belongsTo(EmissionPoint::class);
    }

    // ==================== MÉTODOS ====================

    /**
     * Obtiene el siguiente número secuencial para un punto de emisión y tipo de documento.
     * Usa bloqueo de fila para garantizar unicidad en concurrencia.
     */
    public static function getNextNumber(int $emissionPointId, DocumentType $documentType): int
    {
        return DB::transaction(function () use ($emissionPointId, $documentType) {
            $sequential = static::lockForUpdate()
                ->where('emission_point_id', $emissionPointId)
                ->where('document_type', $documentType)
                ->first();

            if (!$sequential) {
                $emissionPoint = EmissionPoint::find($emissionPointId);

                $sequential = static::create([
                    'tenant_id' => $emissionPoint->tenant_id,
                    'emission_point_id' => $emissionPointId,
                    'document_type' => $documentType,
                    'current_number' => 1,
                ]);

                return 1;
            }

            $nextNumber = $sequential->current_number + 1;

            $sequential->update([
                'current_number' => $nextNumber,
            ]);

            return $nextNumber;
        });
    }

    /**
     * Formatea el número secuencial con ceros a la izquierda (9 dígitos).
     */
    public static function formatNumber(int $number): string
    {
        return str_pad($number, 9, '0', STR_PAD_LEFT);
    }

    /**
     * Obtiene el número secuencial actual sin incrementar.
     */
    public static function getCurrentNumber(int $emissionPointId, DocumentType $documentType): int
    {
        $sequential = static::where('emission_point_id', $emissionPointId)
            ->where('document_type', $documentType)
            ->first();

        return $sequential?->current_number ?? 0;
    }

    /**
     * Reinicia el secuencial a un número específico (uso administrativo).
     */
    public static function resetToNumber(int $emissionPointId, DocumentType $documentType, int $number): void
    {
        static::updateOrCreate(
            [
                'emission_point_id' => $emissionPointId,
                'document_type' => $documentType,
            ],
            [
                'current_number' => $number,
            ]
        );
    }
}
