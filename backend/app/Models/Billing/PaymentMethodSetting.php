<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PaymentMethodSetting extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_enabled',
        'requires_gateway',
        'instructions',
        'sort_order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'requires_gateway' => 'boolean',
    ];

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true)->orderBy('sort_order');
    }
}
