<?php

namespace App\Models\Accounting;

use App\Enums\AccountingStandard;
use App\Models\Tenant\Company;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingSetting extends Model
{
    use BelongsToTenant;

    protected $table = 'accounting_settings';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'accounting_standard',
        'auto_journal_entries',
        'cost_centers_enabled',
        'budgets_enabled',
    ];

    protected $casts = [
        'accounting_standard' => AccountingStandard::class,
        'auto_journal_entries' => 'boolean',
        'cost_centers_enabled' => 'boolean',
        'budgets_enabled' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
