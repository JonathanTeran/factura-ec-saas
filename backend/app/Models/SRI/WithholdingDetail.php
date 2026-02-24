<?php

namespace App\Models\SRI;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithholdingDetail extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'electronic_document_id',
        'tax_type',
        'tax_code',
        'withholding_code',
        'withholding_percentage',
        'base_amount',
        'withheld_amount',
        'supporting_doc_type',
        'supporting_doc_number',
        'supporting_doc_date',
        'supporting_doc_auth',
    ];

    protected $casts = [
        'withholding_percentage' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'withheld_amount' => 'decimal:2',
        'supporting_doc_date' => 'date',
    ];

    // ==================== RELACIONES ====================

    public function electronicDocument(): BelongsTo
    {
        return $this->belongsTo(ElectronicDocument::class);
    }

    // ==================== HELPERS ====================

    public function getTaxTypeName(): string
    {
        return match ($this->tax_type) {
            '1' => 'Renta',
            '2' => 'IVA',
            '6' => 'ISD',
            default => 'Otro',
        };
    }

    public function getWithholdingDescription(): string
    {
        return match ($this->tax_type) {
            '1' => $this->getRetentionRentaDescription(),
            '2' => $this->getRetentionIvaDescription(),
            default => "Retención {$this->withholding_percentage}%",
        };
    }

    private function getRetentionRentaDescription(): string
    {
        return match ($this->withholding_code) {
            '303' => 'Honorarios profesionales',
            '304' => 'Servicios predomina mano de obra',
            '307' => 'Servicios predomina intelecto',
            '308' => 'Servicios entre sociedades',
            '309' => 'Servicios publicidad',
            '310' => 'Transporte privado',
            '312' => 'Transferencia bienes muebles',
            '319' => 'Arrendamiento bienes inmuebles',
            '320' => 'Arrendamiento bienes muebles',
            '322' => 'Seguros y reaseguros',
            '323' => 'Rendimientos financieros',
            '332' => 'Pagos por concepto de IVA',
            '340' => 'Otras retenciones 1%',
            '341' => 'Otras retenciones 2%',
            '342' => 'Otras retenciones 8%',
            '343' => 'Otras retenciones 25%',
            '344' => 'Otras retenciones aplicables',
            default => "Retención IR {$this->withholding_percentage}%",
        };
    }

    private function getRetentionIvaDescription(): string
    {
        return match ($this->withholding_percentage) {
            10 => 'Retención IVA 10%',
            20 => 'Retención IVA 20%',
            30 => 'Retención IVA 30%',
            50 => 'Retención IVA 50%',
            70 => 'Retención IVA 70%',
            100 => 'Retención IVA 100%',
            default => "Retención IVA {$this->withholding_percentage}%",
        };
    }

    public function getSupportingDocTypeLabel(): string
    {
        return match ($this->supporting_doc_type) {
            '01' => 'Factura',
            '02' => 'Nota de Venta',
            '03' => 'Liquidación de Compra',
            '04' => 'Nota de Crédito',
            '05' => 'Nota de Débito',
            '06' => 'Guía de Remisión',
            '07' => 'Comprobante de Retención',
            '15' => 'Comprobante de venta emitido en exterior',
            '19' => 'Comprobante de pagos/servicios públicos',
            '41' => 'Comprobante reembolso',
            default => 'Otro',
        };
    }
}
