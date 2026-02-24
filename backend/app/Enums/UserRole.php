<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case TENANT_OWNER = 'tenant_owner';
    case ADMIN = 'admin';
    case ACCOUNTANT = 'accountant';
    case INVOICER = 'invoicer';
    case VIEWER = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Administrador',
            self::TENANT_OWNER => 'Propietario',
            self::ADMIN => 'Administrador',
            self::ACCOUNTANT => 'Contador',
            self::INVOICER => 'Facturador',
            self::VIEWER => 'Solo Lectura',
        };
    }

    public function permissions(): array
    {
        return match ($this) {
            self::SUPER_ADMIN => ['*'],
            self::TENANT_OWNER => [
                'manage_company', 'manage_users', 'manage_products', 'manage_customers',
                'create_documents', 'view_documents', 'void_documents', 'view_reports',
                'manage_settings', 'manage_subscription', 'export_data', 'use_api',
            ],
            self::ADMIN => [
                'manage_users', 'manage_products', 'manage_customers',
                'create_documents', 'view_documents', 'void_documents', 'view_reports',
            ],
            self::ACCOUNTANT => [
                'manage_products', 'manage_customers',
                'create_documents', 'view_documents', 'void_documents',
                'view_reports', 'export_data',
            ],
            self::INVOICER => [
                'create_documents', 'view_documents', 'manage_customers',
            ],
            self::VIEWER => [
                'view_documents', 'view_reports',
            ],
        };
    }

    public function isSuperAdmin(): bool
    {
        return $this === self::SUPER_ADMIN;
    }

    public function isTenantLevel(): bool
    {
        return $this !== self::SUPER_ADMIN;
    }
}
