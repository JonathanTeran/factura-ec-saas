<?php

namespace App\Models\Tenant;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Company extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, BelongsToTenant, InteractsWithMedia;

    protected $fillable = [
        'tenant_id',
        'ruc',
        'business_name',
        'trade_name',
        'legal_representative',
        'taxpayer_type',
        'obligated_accounting',
        'special_taxpayer',
        'special_taxpayer_number',
        'retention_agent_number',
        'rimpe_type',
        'address',
        'city',
        'province',
        'phone',
        'email',
        'logo_path',
        'sri_environment',
        'signature_path',
        'signature_password',
        'signature_expires_at',
        'signature_issuer',
        'signature_subject',
        'is_active',
        'activated_at',
        'settings',
    ];

    protected $casts = [
        'obligated_accounting' => 'boolean',
        'special_taxpayer' => 'boolean',
        'is_active' => 'boolean',
        'signature_expires_at' => 'datetime',
        'activated_at' => 'datetime',
        'settings' => 'array',
    ];

    protected $hidden = [
        'signature_password',
    ];

    // ==================== RELACIONES ====================

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function mainBranch()
    {
        return $this->branches()->where('is_main', true)->first();
    }

    public function emissionPoints(): HasMany
    {
        return $this->hasManyThrough(EmissionPoint::class, Branch::class);
    }

    public function electronicDocuments(): HasMany
    {
        return $this->hasMany(ElectronicDocument::class);
    }

    // ==================== FIRMA ELECTRÓNICA ====================

    public function setSignaturePasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['signature_password'] = Crypt::encryptString($value);
        }
    }

    public function getDecryptedSignaturePassword(): ?string
    {
        if ($this->signature_password) {
            return Crypt::decryptString($this->signature_password);
        }

        return null;
    }

    public function hasSriPassword(): bool
    {
        return !empty(data_get($this->settings, 'sri_password'));
    }

    public function setSriPassword(?string $value): void
    {
        $settings = $this->settings ?? [];

        if (blank($value)) {
            unset($settings['sri_password']);
        } else {
            $settings['sri_password'] = Crypt::encryptString($value);
        }

        $this->settings = $settings;
    }

    public function getDecryptedSriPassword(): ?string
    {
        $encrypted = data_get($this->settings, 'sri_password');

        if (empty($encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return null;
        }
    }

    public function hasValidSignature(): bool
    {
        return $this->signature_path &&
               $this->signature_password &&
               $this->signature_expires_at &&
               $this->signature_expires_at->isFuture();
    }

    public function hasBasicFiscalData(): bool
    {
        return filled($this->ruc)
            && filled($this->business_name)
            && filled($this->address)
            && filled($this->email);
    }

    public function hasOperationalSetup(): bool
    {
        return $this->branches()
            ->where('is_active', true)
            ->whereHas('emissionPoints', fn ($query) => $query->where('is_active', true))
            ->exists();
    }

    public function emissionReadinessChecklist(): array
    {
        return [
            'basic_data' => $this->hasBasicFiscalData(),
            'sri_password' => $this->hasSriPassword(),
            'digital_signature' => $this->hasValidSignature(),
            'establishments' => $this->hasOperationalSetup(),
        ];
    }

    public function isReadyForEmission(): bool
    {
        return collect($this->emissionReadinessChecklist())->every(fn (bool $isReady) => $isReady);
    }

    public function signatureDaysRemaining(): int
    {
        if (!$this->signature_expires_at) {
            return 0;
        }

        return max(0, now()->diffInDays($this->signature_expires_at, false));
    }

    public function isSignatureExpiringSoon(): bool
    {
        return $this->signatureDaysRemaining() <= 30;
    }

    // ==================== HELPERS ====================

    public function isProduction(): bool
    {
        return $this->sri_environment === '2';
    }

    public function getEnvironmentLabel(): string
    {
        return $this->isProduction() ? 'Producción' : 'Pruebas';
    }

    public function getFullAddress(): string
    {
        $parts = array_filter([$this->address, $this->city, $this->province]);
        return implode(', ', $parts);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->useFallbackUrl('/images/company-placeholder.png');

        $this->addMediaCollection('signature')
            ->singleFile()
            ->acceptsMimeTypes(['application/x-pkcs12']);
    }
}
