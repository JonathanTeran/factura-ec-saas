<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens, TwoFactorAuthenticatable, HasRoles;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'phone',
        'avatar_path',
        'role',
        'is_active',
        'timezone',
        'locale',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    // ==================== BOOT ====================

    protected static function boot()
    {
        parent::boot();

        // Auto-scope para usuarios con tenant
        // Usa hasUser() en lugar de check() para evitar recursión infinita:
        // check() intenta resolver el usuario → dispara query User → activa este scope → loop
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->hasUser()) {
                $user = auth()->user();
                if ($user->tenant_id && !$user->isSuperAdmin()) {
                    $query->where('tenant_id', $user->tenant_id);
                }
            }
        });
    }

    // ==================== RELACIONES ====================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function currentCompany(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant\Company::class, 'current_company_id');
    }

    // ==================== HELPERS ====================

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPER_ADMIN;
    }

    public function isTenantOwner(): bool
    {
        return $this->role === UserRole::TENANT_OWNER;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [UserRole::SUPER_ADMIN, UserRole::TENANT_OWNER, UserRole::ADMIN]);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->isSuperAdmin();
        }

        return $this->is_active;
    }

    public function updateLastLogin(): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);
    }

    public function getInitialsAttribute(): string
    {
        $names = explode(' ', $this->name);
        $initials = '';

        foreach (array_slice($names, 0, 2) as $name) {
            $initials .= strtoupper(substr($name, 0, 1));
        }

        return $initials;
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar_path) {
            return asset('storage/' . $this->avatar_path);
        }

        return null;
    }

    /**
     * Check if user has a specific permission based on their role.
     */
    public function hasPermission(string $permission): bool
    {
        // Super admin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Get permissions for the user's role
        $rolePermissions = $this->getRolePermissions();

        return in_array($permission, $rolePermissions);
    }

    /**
     * Get all permissions for the user's role.
     */
    protected function getRolePermissions(): array
    {
        return match ($this->role) {
            UserRole::TENANT_OWNER => [
                // Full access to everything in tenant
                'manage_customers', 'view_customers',
                'manage_products', 'view_products', 'manage_inventory',
                'create_documents', 'edit_documents', 'delete_documents',
                'sign_documents', 'send_documents', 'void_documents', 'view_documents',
                'manage_companies', 'manage_certificates', 'view_companies',
                'manage_users', 'view_users',
                'view_reports', 'export_reports',
                'manage_settings',
                // Contabilidad
                'view_accounting', 'manage_accounting', 'manage_fiscal_periods',
                'generate_tax_forms', 'manage_budgets', 'manage_cost_centers',
            ],
            UserRole::ADMIN => [
                // Admin access (no user management, no settings)
                'manage_customers', 'view_customers',
                'manage_products', 'view_products', 'manage_inventory',
                'create_documents', 'edit_documents', 'delete_documents',
                'sign_documents', 'send_documents', 'void_documents', 'view_documents',
                'view_companies',
                'view_users',
                'view_reports', 'export_reports',
                // Contabilidad
                'view_accounting', 'manage_accounting', 'manage_cost_centers', 'manage_budgets',
            ],
            UserRole::ACCOUNTANT => [
                // Accountant access - documents and reports focused
                'view_customers',
                'view_products',
                'create_documents', 'edit_documents', 'sign_documents',
                'send_documents', 'view_documents',
                'view_companies',
                'view_reports', 'export_reports',
                // Contabilidad - acceso completo (usuario principal del módulo)
                'view_accounting', 'manage_accounting', 'manage_fiscal_periods',
                'generate_tax_forms', 'manage_budgets', 'manage_cost_centers',
            ],
            UserRole::SELLER => [
                // Seller access - basic document creation
                'view_customers', 'manage_customers',
                'view_products',
                'create_documents', 'edit_documents', 'view_documents',
            ],
            UserRole::VIEWER => [
                // View only access
                'view_customers',
                'view_products',
                'view_documents',
                'view_companies',
                'view_reports',
                // Contabilidad - solo lectura
                'view_accounting',
            ],
            default => [],
        };
    }

    /**
     * Check if user can perform any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }
}
