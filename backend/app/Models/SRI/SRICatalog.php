<?php

namespace App\Models\SRI;

use App\Enums\SRICatalogType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SRICatalog extends Model
{
    protected $table = 'sri_catalogs';

    public $timestamps = false;

    protected $fillable = [
        'catalog_type',
        'code',
        'description',
        'percentage',
        'is_active',
        'parent_code',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'catalog_type' => SRICatalogType::class,
        'percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, SRICatalogType $type)
    {
        return $query->where('catalog_type', $type);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('description');
    }

    // ==================== MÉTODOS ESTÁTICOS ====================

    /**
     * Obtiene catálogos por tipo con caché.
     */
    public static function getByType(SRICatalogType $type): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember(
            "sri_catalog_{$type->value}",
            now()->addDay(),
            fn() => static::ofType($type)->active()->ordered()->get()
        );
    }

    /**
     * Tipos de identificación.
     */
    public static function identificationTypes(): \Illuminate\Database\Eloquent\Collection
    {
        return static::getByType(SRICatalogType::IDENTIFICATION_TYPE);
    }

    /**
     * Códigos de impuesto IVA.
     */
    public static function taxCodes(): \Illuminate\Database\Eloquent\Collection
    {
        return static::getByType(SRICatalogType::TAX_CODE);
    }

    /**
     * Porcentajes de IVA.
     */
    public static function taxRates(): \Illuminate\Database\Eloquent\Collection
    {
        return static::getByType(SRICatalogType::TAX_RATE);
    }

    /**
     * Formas de pago.
     */
    public static function paymentMethods(): \Illuminate\Database\Eloquent\Collection
    {
        return static::getByType(SRICatalogType::PAYMENT_METHOD);
    }

    /**
     * Códigos de retención Renta.
     */
    public static function withholdingCodesRenta(): \Illuminate\Database\Eloquent\Collection
    {
        return static::getByType(SRICatalogType::WITHHOLDING_CODE_RENTA);
    }

    /**
     * Códigos de retención IVA.
     */
    public static function withholdingCodesIva(): \Illuminate\Database\Eloquent\Collection
    {
        return static::getByType(SRICatalogType::WITHHOLDING_CODE_IVA);
    }

    /**
     * Tipos de documento sustento.
     */
    public static function supportingDocTypes(): \Illuminate\Database\Eloquent\Collection
    {
        return static::getByType(SRICatalogType::SUPPORTING_DOC_TYPE);
    }

    /**
     * Motivos de anulación.
     */
    public static function voidReasons(): \Illuminate\Database\Eloquent\Collection
    {
        return static::getByType(SRICatalogType::VOID_REASON);
    }

    /**
     * Busca un catálogo por tipo y código.
     */
    public static function findByTypeAndCode(SRICatalogType $type, string $code): ?static
    {
        return static::getByType($type)->firstWhere('code', $code);
    }

    /**
     * Obtiene la descripción de un código específico.
     */
    public static function getDescription(SRICatalogType $type, string $code): string
    {
        return static::findByTypeAndCode($type, $code)?->description ?? $code;
    }

    /**
     * Limpia el caché de catálogos.
     */
    public static function clearCache(): void
    {
        foreach (SRICatalogType::cases() as $type) {
            Cache::forget("sri_catalog_{$type->value}");
        }
    }
}
