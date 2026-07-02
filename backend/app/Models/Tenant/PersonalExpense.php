<?php

namespace App\Models\Tenant;

use App\Enums\PersonalExpenseCategory;
use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalExpense extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'fiscal_year',
        'category',
        'description',
        'issuer_ruc',
        'issuer_name',
        'document_number',
        'issue_date',
        'amount',
        'notes',
        'receipt_path',
    ];

    protected $casts = [
        'category'    => PersonalExpenseCategory::class,
        'issue_date'  => 'date',
        'amount'      => 'decimal:2',
        'fiscal_year' => 'integer',
    ];

    // ==================== RELACIONES ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== SCOPES ====================

    public function scopeForYear($query, int $year)
    {
        return $query->where('fiscal_year', $year);
    }

    public function scopeByCategory($query, PersonalExpenseCategory $category)
    {
        return $query->where('category', $category->value);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
