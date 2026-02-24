<?php

namespace App\Models\Accounting;

use App\Enums\TaxFormType;
use App\Models\Tenant\Company;
use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxFormSubmission extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'form_type',
        'fiscal_year',
        'fiscal_month',
        'status',
        'generated_data',
        'xml_path',
        'generated_by',
        'generated_at',
        'submitted_at',
    ];

    protected $casts = [
        'form_type' => TaxFormType::class,
        'generated_data' => 'array',
        'fiscal_year' => 'integer',
        'fiscal_month' => 'integer',
        'generated_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    // ==================== RELACIONES ====================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function generatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    // ==================== SCOPES ====================

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForType(Builder $query, TaxFormType $type): Builder
    {
        return $query->where('form_type', $type);
    }

    public function scopeForPeriod(Builder $query, int $year, ?int $month = null): Builder
    {
        $query->where('fiscal_year', $year);

        if ($month !== null) {
            $query->where('fiscal_month', $month);
        }

        return $query;
    }

    // ==================== HELPERS ====================

    public function getPeriodLabel(): string
    {
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        if ($this->fiscal_month) {
            return ($months[$this->fiscal_month] ?? '') . " {$this->fiscal_year}";
        }

        return "Año {$this->fiscal_year}";
    }
}
