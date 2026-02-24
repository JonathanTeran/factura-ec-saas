# 🚀 SUPER PROMPT DEFINITIVO: SaaS Facturación Electrónica Ecuador
# Laravel 12 + MySQL + amephia/sri-ec + Multi-tenant para Miles de Usuarios

---

## ROL Y CONTEXTO

Eres un arquitecto de software senior full-stack especializado en Laravel con +15 años de experiencia construyendo plataformas SaaS multi-tenant de alta escala. Tu misión es diseñar y desarrollar una **plataforma SaaS de facturación electrónica para Ecuador** que:

- Soporte **miles de usuarios simultáneos** con suscripciones
- Cumpla al 100% con los requisitos del **SRI (Servicio de Rentas Internas)** de Ecuador
- Supere en funcionalidad, UX y rentabilidad al modelo de **Ecuafact** (ecuafact.com)
- Use el paquete **`amephia/sri-ec`** como motor central de comunicación con el SRI
- Sea extremadamente eficiente, escalable y rentable

**REGLA DE ORO**: Cada decisión técnica debe priorizar: 1) Fiabilidad fiscal, 2) Escalabilidad, 3) Experiencia de usuario, 4) Rentabilidad del negocio.

---

## STACK TECNOLÓGICO DEFINITIVO

```
╔══════════════════════════════════════════════════════════════╗
║                    STACK PRINCIPAL                            ║
╠══════════════════════════════════════════════════════════════╣
║  Framework      │ Laravel 12 (última versión estable)        ║
║  PHP            │ 8.3+ (requerido por Laravel 12)            ║
║  Base de Datos  │ MySQL 8.0+ (single database, row-level)    ║
║  Cache/Queue    │ Redis 7+                                   ║
║  Queue Worker   │ Laravel Horizon                            ║
║  Search         │ Laravel Scout + Meilisearch                ║
║  WebSockets     │ Laravel Reverb                             ║
║  SRI Engine     │ amephia/sri-ec v1.1+ (PAQUETE CENTRAL)    ║
╠══════════════════════════════════════════════════════════════╣
║                    FRONTEND                                   ║
╠══════════════════════════════════════════════════════════════╣
║  Admin Panel    │ Filament PHP 3.x (Super Admin)             ║
║  Tenant Panel   │ Livewire 3 + Alpine.js + TailwindCSS 4    ║
║  Charts         │ ApexCharts (via Livewire)                  ║
║  PDF            │ DomPDF + Blade templates                   ║
║  QR Codes       │ simplesoftwareio/simple-qrcode             ║
╠══════════════════════════════════════════════════════════════╣
║                    INFRAESTRUCTURA                            ║
╠══════════════════════════════════════════════════════════════╣
║  Servidor       │ Ubuntu 24.04 LTS                           ║
║  Web Server     │ Nginx + PHP-FPM (tuned para alta carga)    ║
║  Container      │ Docker + Docker Compose                    ║
║  CI/CD          │ GitHub Actions                             ║
║  Storage        │ S3-compatible (DO Spaces / MinIO)          ║
║  CDN            │ Cloudflare                                 ║
║  Monitoring     │ Laravel Telescope + Pulse                  ║
╠══════════════════════════════════════════════════════════════╣
║                    SERVICIOS EXTERNOS                         ║
╠══════════════════════════════════════════════════════════════╣
║  Email          │ Resend / Amazon SES                        ║
║  WhatsApp       │ Twilio / Meta WhatsApp Business API        ║
║  Pagos EC       │ PayPhone / Kushki (tarjetas Ecuador)       ║
║  Pagos Intl     │ Stripe (tarjetas internacionales)          ║
║  Firma Elect.   │ Reventa via proveedores ARCOTEL            ║
║  AI             │ OpenAI API (categorización, asistente)     ║
║  SRI Ecuador    │ amephia/sri-ec → SOAP WS del SRI          ║
╚══════════════════════════════════════════════════════════════╝
```

---

## ESTRATEGIA MULTI-TENANT: SINGLE DATABASE CON ROW-LEVEL ISOLATION

### ¿Por qué Single Database y NO Database-per-tenant?

Para **miles de usuarios** con MySQL, la estrategia correcta es **una sola base de datos con aislamiento por filas** (tenant_id en cada tabla):

| Criterio | DB por Tenant | Single DB (elegido) |
|----------|--------------|---------------------|
| Miles de usuarios | ❌ Miles de DBs = pesadilla | ✅ Una DB, índices optimizados |
| Costo servidor | ❌ Altísimo | ✅ Bajo |
| Migraciones | ❌ Ejecutar en cada DB | ✅ Una sola migración |
| Backups | ❌ Uno por DB | ✅ Un solo backup |
| Queries cross-tenant | ❌ Imposible | ✅ Fácil para métricas admin |
| Reportes globales | ❌ Agregar de todas las DBs | ✅ Un solo query |
| Escalabilidad | ❌ Límite de conexiones MySQL | ✅ Read replicas, sharding futuro |

### Implementación: Tenant Scope Global

```php
// Trait que se agrega a TODOS los modelos que pertenecen a un tenant
// app/Traits/BelongsToTenant.php

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // AUTO-SCOPE: Siempre filtrar por tenant del usuario logueado
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });

        // AUTO-ASSIGN: Al crear, asignar tenant automáticamente
        static::creating(function ($model) {
            if (auth()->check() && !$model->tenant_id) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

**Esto garantiza que un usuario NUNCA vea datos de otro tenant, incluso si hay un bug en el código. El scope global es la capa de seguridad más importante del sistema.**

---

## BASE DE DATOS COMPLETA (MySQL 8.0+)

### Tablas del Sistema (sin tenant_id)

```sql
-- =====================================================
-- TABLA: tenants (las cuentas/suscripciones)
-- =====================================================
CREATE TABLE tenants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL COMMENT 'Nombre de la cuenta/negocio',
    slug VARCHAR(255) UNIQUE NOT NULL COMMENT 'Subdominio o identificador URL',
    owner_email VARCHAR(255) NOT NULL,
    status ENUM('trial','active','suspended','cancelled','expired') DEFAULT 'trial',
    trial_ends_at TIMESTAMP NULL,
    current_plan_id BIGINT UNSIGNED NULL,
    subscription_status ENUM('trialing','active','past_due','cancelled','incomplete') DEFAULT 'trialing',
    -- Límites del plan actual (cache denormalizado para performance)
    max_documents_per_month INT DEFAULT 10,
    max_users INT DEFAULT 1,
    max_companies INT DEFAULT 1,
    max_emission_points INT DEFAULT 1,
    has_api_access BOOLEAN DEFAULT FALSE,
    has_inventory BOOLEAN DEFAULT FALSE,
    has_pos BOOLEAN DEFAULT FALSE,
    has_recurring_invoices BOOLEAN DEFAULT FALSE,
    has_advanced_reports BOOLEAN DEFAULT FALSE,
    has_whitelabel_ride BOOLEAN DEFAULT FALSE,
    -- Contadores del período actual
    documents_this_month INT DEFAULT 0,
    documents_month_reset_at DATE NULL,
    -- Referidos
    referral_code VARCHAR(20) UNIQUE NULL,
    referred_by_tenant_id BIGINT UNSIGNED NULL,
    -- Metadata
    settings JSON NULL COMMENT 'Configuraciones varias del tenant',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_subscription (subscription_status),
    INDEX idx_referral (referral_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: plans (planes de suscripción)
-- =====================================================
CREATE TABLE plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT NULL,
    -- Precios
    price_monthly DECIMAL(8,2) NOT NULL DEFAULT 0,
    price_yearly DECIMAL(8,2) NOT NULL DEFAULT 0,
    currency VARCHAR(3) DEFAULT 'USD',
    -- Límites
    max_documents_per_month INT DEFAULT 10 COMMENT '-1 = ilimitado',
    max_users INT DEFAULT 1,
    max_companies INT DEFAULT 1,
    max_emission_points INT DEFAULT 1,
    -- Features (flags)
    has_electronic_signature BOOLEAN DEFAULT FALSE,
    has_api_access BOOLEAN DEFAULT FALSE,
    has_inventory BOOLEAN DEFAULT FALSE,
    has_pos BOOLEAN DEFAULT FALSE,
    has_recurring_invoices BOOLEAN DEFAULT FALSE,
    has_proformas BOOLEAN DEFAULT FALSE,
    has_ats BOOLEAN DEFAULT FALSE,
    has_thermal_printer BOOLEAN DEFAULT FALSE,
    has_advanced_reports BOOLEAN DEFAULT FALSE,
    has_whitelabel_ride BOOLEAN DEFAULT FALSE,
    has_webhooks BOOLEAN DEFAULT FALSE,
    has_client_portal BOOLEAN DEFAULT FALSE,
    has_multi_currency BOOLEAN DEFAULT FALSE,
    has_accountant_access BOOLEAN DEFAULT FALSE,
    has_ai_categorization BOOLEAN DEFAULT FALSE,
    -- Soporte
    support_level ENUM('community','email','priority','dedicated') DEFAULT 'community',
    support_response_hours INT DEFAULT 72,
    -- Control
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE COMMENT 'Destacar en pricing page',
    sort_order INT DEFAULT 0,
    trial_days INT DEFAULT 14,
    -- Metadata
    features_json JSON NULL COMMENT 'Features adicionales key:value',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: subscriptions (suscripciones con historial)
-- =====================================================
CREATE TABLE subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    status ENUM('trialing','active','past_due','cancelled','expired','incomplete') DEFAULT 'trialing',
    billing_cycle ENUM('monthly','yearly') DEFAULT 'monthly',
    -- Fechas
    trial_ends_at TIMESTAMP NULL,
    starts_at TIMESTAMP NOT NULL,
    ends_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    -- Pago
    payment_gateway VARCHAR(50) NULL COMMENT 'stripe, payphone, kushki, transfer',
    gateway_subscription_id VARCHAR(255) NULL,
    gateway_customer_id VARCHAR(255) NULL,
    -- Montos
    amount DECIMAL(8,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    discount_percent DECIMAL(5,2) DEFAULT 0,
    coupon_code VARCHAR(50) NULL,
    -- Metadata
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_status (status),
    INDEX idx_ends (ends_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: payments (historial de pagos)
-- =====================================================
CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    subscription_id BIGINT UNSIGNED NULL,
    -- Detalle
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    status ENUM('pending','completed','failed','refunded','partial_refund') DEFAULT 'pending',
    payment_method ENUM('credit_card','debit_card','transfer','payphone','kushki','stripe','cash','other') NOT NULL,
    gateway_payment_id VARCHAR(255) NULL,
    gateway_response JSON NULL,
    -- Facturación (el SaaS factura a sus clientes)
    invoice_number VARCHAR(50) NULL,
    invoice_path VARCHAR(500) NULL,
    -- Metadata
    description VARCHAR(500) NULL,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_status (status),
    INDEX idx_paid (paid_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: coupons (cupones de descuento)
-- =====================================================
CREATE TABLE coupons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    description VARCHAR(255) NULL,
    discount_type ENUM('percentage','fixed') DEFAULT 'percentage',
    discount_value DECIMAL(8,2) NOT NULL,
    max_uses INT NULL COMMENT 'NULL = ilimitado',
    times_used INT DEFAULT 0,
    valid_from TIMESTAMP NULL,
    valid_until TIMESTAMP NULL,
    applicable_plans JSON NULL COMMENT 'IDs de planes, NULL = todos',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: referral_commissions
-- =====================================================
CREATE TABLE referral_commissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    referrer_tenant_id BIGINT UNSIGNED NOT NULL,
    referred_tenant_id BIGINT UNSIGNED NOT NULL,
    payment_id BIGINT UNSIGNED NULL,
    commission_amount DECIMAL(8,2) NOT NULL,
    commission_percent DECIMAL(5,2) NOT NULL,
    status ENUM('pending','approved','paid','rejected') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (referred_tenant_id) REFERENCES tenants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: sri_catalogs (catálogos compartidos del SRI)
-- =====================================================
CREATE TABLE sri_catalogs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    catalog_type VARCHAR(50) NOT NULL COMMENT 'tax_types, retention_codes, payment_methods, id_types, etc.',
    code VARCHAR(20) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    percentage DECIMAL(5,2) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_catalog (catalog_type, code),
    INDEX idx_type_active (catalog_type, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: system_settings
-- =====================================================
CREATE TABLE system_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) UNIQUE NOT NULL,
    value TEXT NULL,
    type ENUM('string','integer','boolean','json','encrypted') DEFAULT 'string',
    group_name VARCHAR(50) DEFAULT 'general',
    description VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: support_tickets
-- =====================================================
CREATE TABLE support_tickets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    assigned_to BIGINT UNSIGNED NULL COMMENT 'Admin user ID',
    subject VARCHAR(255) NOT NULL,
    category ENUM('technical','billing','sri','general','feature_request') DEFAULT 'general',
    priority ENUM('low','medium','high','critical') DEFAULT 'medium',
    status ENUM('open','in_progress','waiting_customer','resolved','closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    INDEX idx_tenant (tenant_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    is_admin_reply BOOLEAN DEFAULT FALSE,
    message TEXT NOT NULL,
    attachments JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tablas del Tenant (TODAS tienen tenant_id)

```sql
-- =====================================================
-- TABLA: users (usuarios del sistema)
-- =====================================================
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NULL COMMENT 'NULL = super admin',
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    avatar_path VARCHAR(500) NULL,
    role ENUM('super_admin','tenant_owner','admin','accountant','invoicer','viewer') DEFAULT 'invoicer',
    is_active BOOLEAN DEFAULT TRUE,
    timezone VARCHAR(50) DEFAULT 'America/Guayaquil',
    locale VARCHAR(5) DEFAULT 'es',
    two_factor_secret TEXT NULL,
    two_factor_confirmed_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45) NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uk_email (email),
    INDEX idx_tenant (tenant_id),
    INDEX idx_role (role),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: companies (empresas/RUCs por tenant)
-- =====================================================
CREATE TABLE companies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    ruc VARCHAR(13) NOT NULL,
    business_name VARCHAR(300) NOT NULL COMMENT 'Razón social',
    trade_name VARCHAR(300) NULL COMMENT 'Nombre comercial',
    legal_representative VARCHAR(255) NULL,
    taxpayer_type ENUM('natural','juridical','rise') DEFAULT 'natural',
    obligated_accounting BOOLEAN DEFAULT FALSE,
    special_taxpayer BOOLEAN DEFAULT FALSE,
    special_taxpayer_number VARCHAR(20) NULL,
    retention_agent_number VARCHAR(20) NULL,
    rimpe_type ENUM('none','emprendedor','negocio_popular') DEFAULT 'none',
    address TEXT NOT NULL,
    city VARCHAR(100) NULL,
    province VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(255) NOT NULL,
    logo_path VARCHAR(500) NULL,
    -- SRI Config
    sri_environment CHAR(1) DEFAULT '1' COMMENT '1=pruebas, 2=producción',
    -- Firma electrónica (almacenada encriptada)
    signature_path VARCHAR(500) NULL COMMENT 'Ruta al .p12 encriptado en S3',
    signature_password TEXT NULL COMMENT 'Contraseña encriptada con Crypt::',
    signature_expires_at TIMESTAMP NULL,
    signature_issuer VARCHAR(255) NULL COMMENT 'Ej: Security Data, Uanataca',
    signature_subject VARCHAR(255) NULL,
    -- Estado
    is_active BOOLEAN DEFAULT TRUE,
    activated_at TIMESTAMP NULL COMMENT 'Cuando pasó a producción',
    settings JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uk_tenant_ruc (tenant_id, ruc),
    INDEX idx_tenant (tenant_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: branches (establecimientos)
-- =====================================================
CREATE TABLE branches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    code CHAR(3) NOT NULL COMMENT '001, 002, etc.',
    name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    is_main BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_company_code (company_id, code),
    INDEX idx_tenant (tenant_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: emission_points (puntos de emisión)
-- =====================================================
CREATE TABLE emission_points (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    code CHAR(3) NOT NULL COMMENT '001, 002, etc.',
    name VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_branch_code (branch_id, code),
    INDEX idx_tenant (tenant_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: sequential_numbers (secuenciales por tipo)
-- =====================================================
CREATE TABLE sequential_numbers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    emission_point_id BIGINT UNSIGNED NOT NULL,
    document_type CHAR(2) NOT NULL COMMENT '01,03,04,05,06,07',
    current_number INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_point_type (emission_point_id, document_type),
    INDEX idx_tenant (tenant_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (emission_point_id) REFERENCES emission_points(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: customers (clientes/compradores)
-- =====================================================
CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    identification_type CHAR(2) NOT NULL COMMENT '04=RUC, 05=cédula, 06=pasaporte, 07=consumidor final',
    identification VARCHAR(20) NOT NULL,
    name VARCHAR(300) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    total_invoiced DECIMAL(14,2) DEFAULT 0 COMMENT 'Cache para dashboard',
    last_invoice_date DATE NULL,
    notes TEXT NULL,
    tags JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uk_tenant_id_type (tenant_id, identification_type, identification),
    INDEX idx_tenant (tenant_id),
    INDEX idx_name (tenant_id, name(100)),
    INDEX idx_identification (identification),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: categories (categorías de productos)
-- =====================================================
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NULL,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: products (catálogo de productos/servicios)
-- =====================================================
CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    main_code VARCHAR(25) NOT NULL,
    aux_code VARCHAR(25) NULL,
    name VARCHAR(300) NOT NULL,
    description TEXT NULL,
    unit_price DECIMAL(14,6) NOT NULL DEFAULT 0,
    cost_price DECIMAL(14,6) NULL COMMENT 'Precio de costo para rentabilidad',
    -- Impuestos
    tax_code CHAR(1) DEFAULT '2' COMMENT '2=IVA, 3=ICE, 5=IRBPNR',
    tax_percentage_code VARCHAR(4) DEFAULT '2' COMMENT '0=0%, 2=12%, 3=14%, 4=15%, 5=5%, 6=noIVA, 7=exento',
    tax_rate DECIMAL(5,2) DEFAULT 15.00,
    has_ice BOOLEAN DEFAULT FALSE,
    ice_code VARCHAR(10) NULL,
    -- Inventario
    track_inventory BOOLEAN DEFAULT FALSE,
    current_stock DECIMAL(14,4) DEFAULT 0,
    min_stock DECIMAL(14,4) DEFAULT 0,
    unit_of_measure VARCHAR(50) DEFAULT 'unidad',
    -- Control
    type ENUM('product','service') DEFAULT 'product',
    is_active BOOLEAN DEFAULT TRUE,
    is_favorite BOOLEAN DEFAULT FALSE,
    barcode VARCHAR(100) NULL,
    image_path VARCHAR(500) NULL,
    -- Precios múltiples
    prices JSON NULL COMMENT '{"wholesale": 8.50, "retail": 10.00}',
    -- Stats cache
    total_sold DECIMAL(14,2) DEFAULT 0,
    times_sold INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uk_tenant_code (tenant_id, main_code),
    INDEX idx_tenant (tenant_id),
    INDEX idx_barcode (barcode),
    INDEX idx_name (tenant_id, name(100)),
    FULLTEXT idx_search (name, description, main_code),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: electronic_documents (TABLA CENTRAL - todos los comprobantes)
-- =====================================================
CREATE TABLE electronic_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    emission_point_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NOT NULL,

    -- Identificación SRI
    document_type CHAR(2) NOT NULL COMMENT '01=factura,03=liq.compra,04=NC,05=ND,06=guía,07=retención',
    environment CHAR(1) NOT NULL COMMENT '1=pruebas, 2=producción',
    series CHAR(6) NOT NULL COMMENT 'est(3)+pto(3) ej: 001001',
    sequential VARCHAR(9) NOT NULL,
    access_key CHAR(49) NULL COMMENT 'Clave de acceso generada por amephia/sri-ec',

    -- Estado del flujo SRI
    status ENUM(
        'draft',        -- Borrador, editable
        'processing',   -- En cola, esperando procesamiento
        'signed',       -- Firmado, no enviado aún
        'sent',         -- Enviado al SRI, esperando respuesta
        'authorized',   -- ✅ AUTORIZADO por el SRI
        'rejected',     -- ❌ Rechazado por el SRI
        'failed',       -- Error técnico (timeout, SRI caído)
        'voided'        -- Anulado (vía nota de crédito)
    ) DEFAULT 'draft',
    authorization_number VARCHAR(49) NULL,
    authorization_date TIMESTAMP NULL,

    -- Montos
    subtotal_no_tax DECIMAL(14,2) DEFAULT 0 COMMENT 'No objeto de IVA',
    subtotal_0 DECIMAL(14,2) DEFAULT 0 COMMENT 'Tarifa 0%',
    subtotal_5 DECIMAL(14,2) DEFAULT 0 COMMENT 'Tarifa 5%',
    subtotal_12 DECIMAL(14,2) DEFAULT 0 COMMENT 'Tarifa 12%',
    subtotal_15 DECIMAL(14,2) DEFAULT 0 COMMENT 'Tarifa 15%',
    total_discount DECIMAL(14,2) DEFAULT 0,
    total_tax DECIMAL(14,2) DEFAULT 0,
    total_ice DECIMAL(14,2) DEFAULT 0,
    tip DECIMAL(14,2) DEFAULT 0,
    total DECIMAL(14,2) DEFAULT 0,

    -- Archivos (S3 paths)
    xml_unsigned_path VARCHAR(500) NULL,
    xml_signed_path VARCHAR(500) NULL,
    xml_authorized_path VARCHAR(500) NULL,
    ride_pdf_path VARCHAR(500) NULL,

    -- SRI Communication Log
    sri_response JSON NULL COMMENT 'Respuesta completa del SRI',
    sri_errors JSON NULL COMMENT 'Errores del SRI',
    sri_attempts TINYINT UNSIGNED DEFAULT 0,
    last_sri_attempt_at TIMESTAMP NULL,

    -- Documento relacionado (NC, ND, Retención)
    related_document_id BIGINT UNSIGNED NULL,
    related_document_type CHAR(2) NULL,
    related_document_number VARCHAR(17) NULL COMMENT 'Ej: 001-001-000000001',
    related_document_date DATE NULL,

    -- Envío al cliente
    email_sent BOOLEAN DEFAULT FALSE,
    email_sent_at TIMESTAMP NULL,
    whatsapp_sent BOOLEAN DEFAULT FALSE,
    whatsapp_sent_at TIMESTAMP NULL,

    -- Pagos
    payment_methods JSON NULL,

    -- Metadata
    additional_info JSON NULL,
    issue_date DATE NOT NULL,
    due_date DATE NULL,
    currency VARCHAR(10) DEFAULT 'DOLAR',
    notes TEXT NULL,

    -- Factura recurrente
    recurring_invoice_id BIGINT UNSIGNED NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- ÍNDICES CRÍTICOS PARA PERFORMANCE CON MILES DE USUARIOS
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_tenant_type_date (tenant_id, document_type, issue_date),
    INDEX idx_tenant_customer (tenant_id, customer_id),
    INDEX idx_tenant_date (tenant_id, issue_date),
    INDEX idx_company_type (company_id, document_type),
    INDEX idx_access_key (access_key),
    INDEX idx_status_attempts (status, sri_attempts),
    INDEX idx_authorization (authorization_number),
    INDEX idx_created (created_at),

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (emission_point_id) REFERENCES emission_points(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (related_document_id) REFERENCES electronic_documents(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: document_items (detalle de cada comprobante)
-- =====================================================
CREATE TABLE document_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    electronic_document_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    main_code VARCHAR(25) NOT NULL,
    aux_code VARCHAR(25) NULL,
    description TEXT NOT NULL,
    quantity DECIMAL(14,6) NOT NULL,
    unit_price DECIMAL(14,6) NOT NULL,
    discount DECIMAL(14,2) DEFAULT 0,
    subtotal DECIMAL(14,2) NOT NULL,
    -- Impuestos
    tax_code CHAR(1) DEFAULT '2',
    tax_percentage_code VARCHAR(4) NOT NULL,
    tax_rate DECIMAL(5,2) NOT NULL,
    tax_base DECIMAL(14,2) NOT NULL,
    tax_value DECIMAL(14,2) NOT NULL,
    -- ICE
    ice_code VARCHAR(10) NULL,
    ice_rate DECIMAL(5,2) NULL,
    ice_value DECIMAL(14,2) NULL,
    -- Orden
    sort_order INT DEFAULT 0,
    additional_details JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_document (electronic_document_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (electronic_document_id) REFERENCES electronic_documents(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: withholding_details (detalle de retenciones)
-- =====================================================
CREATE TABLE withholding_details (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    electronic_document_id BIGINT UNSIGNED NOT NULL,
    -- Documento sustento
    support_doc_code VARCHAR(2) NOT NULL COMMENT 'Código tipo doc sustento',
    support_doc_number VARCHAR(17) NOT NULL,
    support_doc_date DATE NOT NULL,
    support_doc_total DECIMAL(14,2) NULL,
    support_reason_code VARCHAR(2) NULL COMMENT 'Código sustento tributario',
    -- Retención
    tax_type ENUM('renta','iva') NOT NULL,
    retention_code VARCHAR(10) NOT NULL COMMENT 'Código retención SRI',
    tax_base DECIMAL(14,2) NOT NULL,
    retention_rate DECIMAL(5,2) NOT NULL,
    retained_value DECIMAL(14,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_document (electronic_document_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (electronic_document_id) REFERENCES electronic_documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: received_documents (documentos recibidos)
-- =====================================================
CREATE TABLE received_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    -- Datos del comprobante
    document_type CHAR(2) NOT NULL,
    access_key CHAR(49) UNIQUE NULL,
    authorization_number VARCHAR(49) NULL,
    authorization_date TIMESTAMP NULL,
    -- Emisor
    issuer_ruc VARCHAR(13) NOT NULL,
    issuer_name VARCHAR(300) NOT NULL,
    -- Montos
    subtotal DECIMAL(14,2) DEFAULT 0,
    total_tax DECIMAL(14,2) DEFAULT 0,
    total DECIMAL(14,2) DEFAULT 0,
    -- Clasificación
    expense_category VARCHAR(50) NULL COMMENT 'Para gastos deducibles',
    expense_subcategory VARCHAR(50) NULL,
    is_deductible BOOLEAN DEFAULT TRUE,
    -- Archivos
    xml_path VARCHAR(500) NULL,
    pdf_path VARCHAR(500) NULL,
    -- Control
    source ENUM('email','manual','sri_query','api') DEFAULT 'manual',
    issue_date DATE NOT NULL,
    is_processed BOOLEAN DEFAULT FALSE,
    notes TEXT NULL,
    tags JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_tenant_date (tenant_id, issue_date),
    INDEX idx_issuer (tenant_id, issuer_ruc),
    INDEX idx_category (tenant_id, expense_category),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: quotes (proformas/cotizaciones)
-- =====================================================
CREATE TABLE quotes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    quote_number VARCHAR(20) NOT NULL,
    status ENUM('draft','sent','accepted','rejected','invoiced','expired') DEFAULT 'draft',
    issue_date DATE NOT NULL,
    expiry_date DATE NULL,
    subtotal DECIMAL(14,2) DEFAULT 0,
    total_tax DECIMAL(14,2) DEFAULT 0,
    total_discount DECIMAL(14,2) DEFAULT 0,
    total DECIMAL(14,2) DEFAULT 0,
    items JSON NOT NULL,
    payment_terms TEXT NULL,
    notes TEXT NULL,
    converted_document_id BIGINT UNSIGNED NULL COMMENT 'Factura generada',
    pdf_path VARCHAR(500) NULL,
    sent_at TIMESTAMP NULL,
    accepted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_tenant (tenant_id),
    INDEX idx_status (tenant_id, status),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: recurring_invoices
-- =====================================================
CREATE TABLE recurring_invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    frequency ENUM('daily','weekly','biweekly','monthly','quarterly','yearly') DEFAULT 'monthly',
    next_issue_date DATE NOT NULL,
    end_date DATE NULL,
    items JSON NOT NULL,
    payment_methods JSON NULL,
    auto_send BOOLEAN DEFAULT TRUE COMMENT 'Emitir automáticamente o esperar aprobación',
    is_active BOOLEAN DEFAULT TRUE,
    total_generated INT DEFAULT 0,
    last_generated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_next_date (next_issue_date, is_active),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: inventory_movements
-- =====================================================
CREATE TABLE inventory_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    movement_type ENUM('purchase','sale','adjustment_in','adjustment_out','transfer','return','initial') NOT NULL,
    quantity DECIMAL(14,4) NOT NULL,
    unit_cost DECIMAL(14,4) NULL,
    stock_before DECIMAL(14,4) NOT NULL,
    stock_after DECIMAL(14,4) NOT NULL,
    reference_type VARCHAR(50) NULL COMMENT 'electronic_document, manual, etc.',
    reference_id BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_product (tenant_id, product_id),
    INDEX idx_product_date (product_id, created_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: cash_registers (cajas POS)
-- =====================================================
CREATE TABLE cash_registers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    opened_by BIGINT UNSIGNED NOT NULL,
    closed_by BIGINT UNSIGNED NULL,
    opening_amount DECIMAL(14,2) DEFAULT 0,
    closing_amount DECIMAL(14,2) NULL,
    total_sales DECIMAL(14,2) DEFAULT 0,
    total_cash DECIMAL(14,2) DEFAULT 0,
    total_card DECIMAL(14,2) DEFAULT 0,
    total_transfer DECIMAL(14,2) DEFAULT 0,
    transactions_count INT DEFAULT 0,
    status ENUM('open','closed') DEFAULT 'open',
    opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    notes TEXT NULL,
    INDEX idx_tenant (tenant_id),
    INDEX idx_status (tenant_id, status),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: personal_expenses (gastos deducibles)
-- =====================================================
CREATE TABLE personal_expenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    received_document_id BIGINT UNSIGNED NULL,
    fiscal_year YEAR NOT NULL,
    category ENUM('vivienda','educacion','salud','alimentacion','vestimenta','turismo') NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    description VARCHAR(500) NULL,
    issue_date DATE NOT NULL,
    issuer_ruc VARCHAR(13) NULL,
    issuer_name VARCHAR(300) NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_year (tenant_id, fiscal_year),
    INDEX idx_category (tenant_id, fiscal_year, category),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: api_keys (para clientes que usan la API)
-- =====================================================
CREATE TABLE api_keys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    key_hash VARCHAR(255) NOT NULL COMMENT 'SHA-256 hash de la API key',
    key_prefix VARCHAR(10) NOT NULL COMMENT 'Primeros chars para identificación',
    permissions JSON NULL COMMENT '["invoices:create","invoices:read"]',
    rate_limit_per_minute INT DEFAULT 60,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_prefix (key_prefix),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: webhook_endpoints
-- =====================================================
CREATE TABLE webhook_endpoints (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    url VARCHAR(500) NOT NULL,
    secret VARCHAR(255) NOT NULL,
    events JSON NOT NULL COMMENT '["document.authorized","document.rejected"]',
    is_active BOOLEAN DEFAULT TRUE,
    last_triggered_at TIMESTAMP NULL,
    failure_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: activity_log (auditoría por tenant)
-- =====================================================
CREATE TABLE activity_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    log_type VARCHAR(50) NOT NULL COMMENT 'document_created, user_login, settings_changed, etc.',
    subject_type VARCHAR(100) NULL,
    subject_id BIGINT UNSIGNED NULL,
    description TEXT NULL,
    properties JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_user (user_id),
    INDEX idx_type (log_type),
    INDEX idx_created (created_at),
    INDEX idx_subject (subject_type, subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: notifications
-- =====================================================
CREATE TABLE notifications (
    id CHAR(36) PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NULL,
    type VARCHAR(255) NOT NULL,
    notifiable_type VARCHAR(255) NOT NULL,
    notifiable_id BIGINT UNSIGNED NOT NULL,
    data JSON NOT NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifiable (notifiable_type, notifiable_id),
    INDEX idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## INTEGRACIÓN DE amephia/sri-ec (PAQUETE CENTRAL)

### Instalación

```bash
composer require amephia/sri-ec
```

### Arquitectura de integración

```
LA CLAVE: El paquete amephia/sri-ec maneja TODO lo del SRI
(XML, firma XAdES-BES, SOAP, clave de acceso, validación XSD).
Nosotros construimos las capas de Laravel alrededor.

┌──────────────────────────────────────────────────┐
│              FLUJO DE UN DOCUMENTO                │
├──────────────────────────────────────────────────┤
│                                                   │
│  1. Controller recibe request                     │
│        ↓                                          │
│  2. Se crea ElectronicDocument (status: draft)    │
│        ↓                                          │
│  3. Usuario hace clic en "Emitir"                 │
│        ↓                                          │
│  4. Se despacha ProcessDocumentJob (async)         │
│        ↓                                          │
│  5. Job inicializa SRIService                     │
│        ↓                                          │
│  6. DocumentBuilder transforma Eloquent → Array   │
│        ↓                                          │
│  7. SignatureManager desencripta .p12              │
│        ↓                                          │
│  ╔═══════════════════════════════════════════╗     │
│  ║  amephia/sri-ec hace su magia:           ║     │
│  ║  - Genera XML del comprobante            ║     │
│  ║  - Valida contra XSD                     ║     │
│  ║  - Genera clave de acceso (49 dígitos)   ║     │
│  ║  - Firma con XAdES-BES (.p12)            ║     │
│  ║  - Envía al SRI vía SOAP                 ║     │
│  ║  - Consulta autorización                 ║     │
│  ║  - Retorna resultado                     ║     │
│  ╚═══════════════════════════════════════════╝     │
│        ↓                                          │
│  8. Se actualiza status en DB                     │
│        ↓                                          │
│  9. Si AUTORIZADO:                                │
│     → Genera RIDE (PDF) con DomPDF               │
│     → Almacena XMLs en S3                        │
│     → Envía email/WhatsApp al cliente            │
│     → Dispara webhooks                            │
│     → Actualiza contadores del tenant            │
│        ↓                                          │
│  10. Si RECHAZADO:                                │
│     → Notifica al usuario con errores del SRI    │
│     → Loguea para análisis                       │
│        ↓                                          │
│  11. Si FALLO TÉCNICO (timeout, SRI caído):      │
│     → Reintenta automáticamente (max 3)          │
│     → Backoff exponencial (30s, 2min, 5min)      │
│                                                   │
└──────────────────────────────────────────────────┘
```

### Service Layer completo

```php
// ============================================
// app/Services/SRI/SRIService.php
// SERVICIO PRINCIPAL - Envuelve amephia/sri-ec
// ============================================

namespace App\Services\SRI;

use Teran\Sri\SRI;
use Teran\Sri\Exceptions\SriException;
use Teran\Sri\Exceptions\ValidationException;
use App\Models\ElectronicDocument;
use App\Models\Company;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SRIService
{
    private ?SRI $sri = null;

    public function __construct(
        private DocumentBuilder $builder,
        private SignatureManager $signatures,
        private RIDEGenerator $rideGenerator,
    ) {}

    /**
     * Inicializar para una empresa específica
     */
    public function forCompany(Company $company): self
    {
        $env = $company->sri_environment === '2' ? 'produccion' : 'pruebas';
        $this->sri = new SRI($env);

        $sig = $this->signatures->decrypt($company);
        $this->sri->setFirma($sig['content'], $sig['password']);

        return $this;
    }

    /**
     * Procesar cualquier tipo de documento
     * Este es el método que llama el Job
     */
    public function process(ElectronicDocument $doc): array
    {
        $this->forCompany($doc->company);

        $data = $this->builder->build($doc);

        try {
            $result = match ($doc->document_type) {
                '01' => $this->sri->facturaFromArray($data),
                '04' => $this->sri->notaCreditoFromArray($data),
                '05' => $this->sri->notaDebitoFromArray($data),
                '06' => $this->sri->guiaRemisionFromArray($data),
                '07' => $this->sri->retencionFromArray($data),
                default => throw new \InvalidArgumentException("Tipo no soportado: {$doc->document_type}"),
            };

            $this->handleResult($doc, $result);
            return $result;

        } catch (ValidationException $e) {
            $doc->update([
                'status' => 'rejected',
                'sri_errors' => json_encode($e->getErrors()),
                'sri_attempts' => $doc->sri_attempts + 1,
                'last_sri_attempt_at' => now(),
            ]);
            throw $e;

        } catch (SriException $e) {
            $doc->update([
                'status' => 'failed',
                'sri_errors' => json_encode(['general' => $e->getMessage()]),
                'sri_attempts' => $doc->sri_attempts + 1,
                'last_sri_attempt_at' => now(),
            ]);
            throw $e;
        }
    }

    /**
     * Solo consultar autorización
     */
    public function checkAuthorization(Company $company, string $accessKey): object
    {
        $this->forCompany($company);
        return $this->sri->consultarAutorizacion($accessKey);
    }

    /**
     * Health check del SRI
     */
    public function isAvailable(): bool
    {
        return Cache::remember('sri:health', 60, function () {
            try {
                $sri = new SRI('produccion');
                $sri->consultarAutorizacion(str_repeat('0', 49));
                return true;
            } catch (\Throwable $e) {
                if (str_contains($e->getMessage(), 'timeout') ||
                    str_contains($e->getMessage(), 'Could not connect')) {
                    return false;
                }
                return true; // Error esperado (clave inválida = SRI activo)
            }
        });
    }

    /**
     * Procesar resultado del SRI
     */
    private function handleResult(ElectronicDocument $doc, array $result): void
    {
        $auth = $result['autorizacion'] ?? null;
        $basePath = "tenants/{$doc->tenant_id}/documents/{$doc->id}";

        $update = [
            'access_key' => $result['claveAcceso'] ?? $doc->access_key,
            'sri_response' => json_encode($result),
            'sri_attempts' => $doc->sri_attempts + 1,
            'last_sri_attempt_at' => now(),
        ];

        // Guardar XML firmado
        if (isset($result['xmlFirmado'])) {
            Storage::disk('s3')->put("{$basePath}/signed.xml", $result['xmlFirmado']);
            $update['xml_signed_path'] = "{$basePath}/signed.xml";
        }

        if ($auth && $auth->estado === 'AUTORIZADO') {
            $update['status'] = 'authorized';
            $update['authorization_number'] = $auth->numeroAutorizacion;
            $update['authorization_date'] = $auth->fechaAutorizacion;

            // Guardar XML autorizado
            if (isset($result['xmlAutorizado'])) {
                Storage::disk('s3')->put("{$basePath}/authorized.xml", $result['xmlAutorizado']);
                $update['xml_authorized_path'] = "{$basePath}/authorized.xml";
            }

            // Generar RIDE (PDF)
            $ridePath = $this->rideGenerator->generate($doc, $result);
            $update['ride_pdf_path'] = $ridePath;

        } else {
            $update['status'] = 'rejected';
            if ($auth && isset($auth->mensajes)) {
                $errors = array_map(
                    fn($m) => "[{$m->identificador}] {$m->mensaje} - {$m->informacionAdicional}",
                    $auth->mensajes
                );
                $update['sri_errors'] = json_encode($errors);
            }
        }

        $doc->update($update);
    }
}
```

```php
// ============================================
// app/Services/SRI/DocumentBuilder.php
// Transforma modelos Eloquent → Arrays para amephia/sri-ec
// ============================================

namespace App\Services\SRI;

use App\Models\ElectronicDocument;

class DocumentBuilder
{
    /**
     * Construir array según tipo de documento
     */
    public function build(ElectronicDocument $doc): array
    {
        return match ($doc->document_type) {
            '01' => $this->invoice($doc),
            '04' => $this->creditNote($doc),
            '05' => $this->debitNote($doc),
            '06' => $this->waybill($doc),
            '07' => $this->withholding($doc),
            default => throw new \InvalidArgumentException("Tipo: {$doc->document_type}"),
        };
    }

    public function invoice(ElectronicDocument $doc): array
    {
        $company = $doc->company;
        $customer = $doc->customer;
        $branch = $doc->branch;
        $ep = $doc->emissionPoint;

        return [
            'infoTributaria' => $this->infoTributaria($doc),
            'infoFactura' => [
                'fechaEmision' => $doc->issue_date->format('d/m/Y'),
                'dirEstablecimiento' => $branch->address,
                'contribuyenteEspecial' => $company->special_taxpayer_number,
                'obligadoContabilidad' => $company->obligated_accounting ? 'SI' : 'NO',
                'tipoIdentificacionComprador' => $customer->identification_type,
                'razonSocialComprador' => $customer->name,
                'identificacionComprador' => $customer->identification,
                'direccionComprador' => $customer->address ?? '',
                'totalSinImpuestos' => $this->fmt($doc->total - $doc->total_tax - $doc->total_ice),
                'totalDescuento' => $this->fmt($doc->total_discount),
                'importetotal' => $this->fmt($doc->total),
                'moneda' => $doc->currency,
                'totalConImpuestos' => $this->taxTotals($doc),
                'propina' => $this->fmt($doc->tip),
                'pagos' => $this->payments($doc),
            ],
            'detalles' => $this->items($doc),
            'infoAdicional' => $this->additionalInfo($doc),
        ];
    }

    public function creditNote(ElectronicDocument $doc): array
    {
        $related = $doc->relatedDocument;
        return [
            'infoTributaria' => $this->infoTributaria($doc),
            'infoNotaCredito' => [
                'fechaEmision' => $doc->issue_date->format('d/m/Y'),
                'dirEstablecimiento' => $doc->branch->address,
                'tipoIdentificacionComprador' => $doc->customer->identification_type,
                'razonSocialComprador' => $doc->customer->name,
                'identificacionComprador' => $doc->customer->identification,
                'contribuyenteEspecial' => $doc->company->special_taxpayer_number,
                'obligadoContabilidad' => $doc->company->obligated_accounting ? 'SI' : 'NO',
                'codDocModificado' => $doc->related_document_type,
                'numDocModificado' => $doc->related_document_number,
                'fechaEmisionDocSustento' => $doc->related_document_date->format('d/m/Y'),
                'totalSinImpuestos' => $this->fmt($doc->total - $doc->total_tax),
                'valorModificacion' => $this->fmt($doc->total),
                'moneda' => 'DOLAR',
                'totalConImpuestos' => $this->taxTotals($doc),
                'motivo' => $doc->additional_info['motivo'] ?? 'Devolución',
            ],
            'detalles' => $this->items($doc),
        ];
    }

    public function withholding(ElectronicDocument $doc): array
    {
        return [
            'infoTributaria' => $this->infoTributaria($doc),
            'infoCompRetencion' => [
                'fechaEmision' => $doc->issue_date->format('d/m/Y'),
                'dirEstablecimiento' => $doc->branch->address,
                'contribuyenteEspecial' => $doc->company->special_taxpayer_number,
                'obligadoContabilidad' => $doc->company->obligated_accounting ? 'SI' : 'NO',
                'tipoIdentificacionSujetoRetenido' => $doc->customer->identification_type,
                'razonSocialSujetoRetenido' => $doc->customer->name,
                'identificacionSujetoRetenido' => $doc->customer->identification,
                'periodoFiscal' => $doc->issue_date->format('m/Y'),
            ],
            'docsSustento' => $this->withholdingDetails($doc),
        ];
    }

    public function debitNote(ElectronicDocument $doc): array
    {
        return [
            'infoTributaria' => $this->infoTributaria($doc),
            'infoNotaDebito' => [
                'fechaEmision' => $doc->issue_date->format('d/m/Y'),
                'dirEstablecimiento' => $doc->branch->address,
                'tipoIdentificacionComprador' => $doc->customer->identification_type,
                'razonSocialComprador' => $doc->customer->name,
                'identificacionComprador' => $doc->customer->identification,
                'contribuyenteEspecial' => $doc->company->special_taxpayer_number,
                'obligadoContabilidad' => $doc->company->obligated_accounting ? 'SI' : 'NO',
                'codDocModificado' => $doc->related_document_type,
                'numDocModificado' => $doc->related_document_number,
                'fechaEmisionDocSustento' => $doc->related_document_date->format('d/m/Y'),
                'totalSinImpuestos' => $this->fmt($doc->total - $doc->total_tax),
                'valorTotal' => $this->fmt($doc->total),
                'pagos' => $this->payments($doc),
            ],
            'motivos' => $doc->additional_info['motivos'] ?? [],
        ];
    }

    public function waybill(ElectronicDocument $doc): array
    {
        $info = $doc->additional_info;
        return [
            'infoTributaria' => $this->infoTributaria($doc),
            'infoGuiaRemision' => [
                'dirEstablecimiento' => $doc->branch->address,
                'dirPartida' => $info['dirPartida'],
                'razonSocialTransportista' => $info['razonSocialTransportista'],
                'tipoIdentificacionTransportista' => $info['tipoIdTransportista'],
                'rucTransportista' => $info['rucTransportista'],
                'obligadoContabilidad' => $doc->company->obligated_accounting ? 'SI' : 'NO',
                'fechaIniTransporte' => $info['fechaIniTransporte'],
                'fechaFinTransporte' => $info['fechaFinTransporte'],
                'placa' => $info['placa'],
            ],
            'destinatarios' => $info['destinatarios'] ?? [],
        ];
    }

    // --- Helpers privados ---

    private function infoTributaria(ElectronicDocument $doc): array
    {
        $c = $doc->company;
        return [
            'ambiente' => $c->sri_environment,
            'razonSocial' => $c->business_name,
            'nombreComercial' => $c->trade_name ?? $c->business_name,
            'ruc' => $c->ruc,
            'estab' => $doc->branch->code,
            'ptoEmi' => $doc->emissionPoint->code,
            'secuencial' => str_pad($doc->sequential, 9, '0', STR_PAD_LEFT),
            'dirMatriz' => $c->address,
            'contribuyenteEspecial' => $c->special_taxpayer_number,
            'obligadoContabilidad' => $c->obligated_accounting ? 'SI' : 'NO',
            'agenteRetencion' => $c->retention_agent_number,
        ];
    }

    private function taxTotals(ElectronicDocument $doc): array
    {
        $taxes = [];
        if ($doc->subtotal_0 > 0)        $taxes[] = ['codigo'=>'2','codigoPorcentaje'=>'0','baseImponible'=>$this->fmt($doc->subtotal_0),'valor'=>'0.00'];
        if ($doc->subtotal_5 > 0)         $taxes[] = ['codigo'=>'2','codigoPorcentaje'=>'5','baseImponible'=>$this->fmt($doc->subtotal_5),'valor'=>$this->fmt($doc->subtotal_5 * 0.05)];
        if ($doc->subtotal_12 > 0)        $taxes[] = ['codigo'=>'2','codigoPorcentaje'=>'2','baseImponible'=>$this->fmt($doc->subtotal_12),'valor'=>$this->fmt($doc->subtotal_12 * 0.12)];
        if ($doc->subtotal_15 > 0)        $taxes[] = ['codigo'=>'2','codigoPorcentaje'=>'4','baseImponible'=>$this->fmt($doc->subtotal_15),'valor'=>$this->fmt($doc->subtotal_15 * 0.15)];
        if ($doc->subtotal_no_tax > 0)    $taxes[] = ['codigo'=>'2','codigoPorcentaje'=>'6','baseImponible'=>$this->fmt($doc->subtotal_no_tax),'valor'=>'0.00'];
        return $taxes;
    }

    private function payments(ElectronicDocument $doc): array
    {
        $methods = $doc->payment_methods ?? [];
        if (empty($methods)) return [['formaPago'=>'01','total'=>$this->fmt($doc->total)]];
        return array_map(fn($p) => [
            'formaPago' => $p['code'],
            'total' => $this->fmt($p['amount']),
            'plazo' => $p['term'] ?? '',
            'unidadTiempo' => $p['time_unit'] ?? '',
        ], $methods);
    }

    private function items(ElectronicDocument $doc): array
    {
        return $doc->items->map(fn($i) => [
            'codigoPrincipal' => $i->main_code,
            'codigoAuxiliar' => $i->aux_code ?? '',
            'descripcion' => $i->description,
            'cantidad' => number_format($i->quantity, 6, '.', ''),
            'precioUnitario' => number_format($i->unit_price, 6, '.', ''),
            'descuento' => $this->fmt($i->discount),
            'precioTotalSinImpuesto' => $this->fmt($i->subtotal),
            'impuestos' => [[
                'codigo' => $i->tax_code,
                'codigoPorcentaje' => $i->tax_percentage_code,
                'tarifa' => $this->fmt($i->tax_rate),
                'baseImponible' => $this->fmt($i->tax_base),
                'valor' => $this->fmt($i->tax_value),
            ]],
        ])->toArray();
    }

    private function additionalInfo(ElectronicDocument $doc): array
    {
        $info = [];
        if ($doc->customer?->email) $info[] = ['nombre'=>'email','valor'=>$doc->customer->email];
        if ($doc->customer?->phone) $info[] = ['nombre'=>'teléfono','valor'=>$doc->customer->phone];
        if ($doc->customer?->address) $info[] = ['nombre'=>'dirección','valor'=>$doc->customer->address];
        return $info;
    }

    private function withholdingDetails(ElectronicDocument $doc): array
    {
        return $doc->withholdingDetails->groupBy('support_doc_number')->map(function ($group) {
            $first = $group->first();
            return [
                'codSustento' => $first->support_reason_code ?? '01',
                'codDocSustento' => $first->support_doc_code,
                'numDocSustento' => $first->support_doc_number,
                'fechaEmisionDocSustento' => $first->support_doc_date->format('d/m/Y'),
                'totalSinImpuestos' => $this->fmt($first->support_doc_total ?? 0),
                'importeTotal' => $this->fmt($first->support_doc_total ?? 0),
                'retenciones' => $group->map(fn($r) => [
                    'codigo' => $r->tax_type === 'renta' ? '1' : '2',
                    'codigoRetencion' => $r->retention_code,
                    'baseImponible' => $this->fmt($r->tax_base),
                    'porcentajeRetener' => $this->fmt($r->retention_rate),
                    'valorRetenido' => $this->fmt($r->retained_value),
                ])->toArray(),
            ];
        })->values()->toArray();
    }

    private function fmt(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
```

```php
// ============================================
// app/Jobs/SRI/ProcessDocumentJob.php
// Job asíncrono que procesa documentos con el SRI
// ============================================

namespace App\Jobs\SRI;

use App\Models\ElectronicDocument;
use App\Services\SRI\SRIService;
use App\Events\DocumentAuthorized;
use App\Events\DocumentRejected;
use App\Events\DocumentFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Teran\Sri\Exceptions\ValidationException;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 3;
    public array $backoff = [30, 120, 300];

    public function __construct(
        public ElectronicDocument $document
    ) {
        $this->queue = 'sri-send';
    }

    /**
     * Evitar procesamiento duplicado del mismo documento
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->document->id))
                ->releaseAfter(120)
                ->expireAfter(300),
        ];
    }

    public function handle(SRIService $sriService): void
    {
        $result = $sriService->process($this->document);
        $this->document->refresh();

        if ($this->document->status === 'authorized') {
            event(new DocumentAuthorized($this->document));

            // Incrementar contador del tenant
            $this->document->tenant->increment('documents_this_month');

            // Disparar envío al cliente
            SendDocumentToClientJob::dispatch($this->document)->onQueue('email');
        } else {
            event(new DocumentRejected($this->document));
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->document->update([
            'status' => 'failed',
            'sri_errors' => json_encode(['fatal' => $e->getMessage()]),
        ]);
        event(new DocumentFailed($this->document));
    }

    /**
     * No reintentar errores de validación
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(15);
    }
}
```

---

## MODELO DE PRECIOS DEFINITIVO

```
╔═══════════════════════════════════════════════════════════════════╗
║  PLAN          │ MENSUAL │ ANUAL    │ DOCS/MES │ USUARIOS │ RUCs║
╠═══════════════════════════════════════════════════════════════════╣
║  🆓 Starter    │ GRATIS  │ GRATIS   │ 10       │ 1        │ 1   ║
║  💼 Emprendedor│ $4.99   │ $49.90   │ 50       │ 3        │ 1   ║
║  ⭐ Negocio    │ $14.99  │ $149.90  │ Ilimitado│ 10       │ 3   ║
║  🚀 Profesional│ $34.99  │ $349.90  │ Ilimitado│ Ilimitado│ ∞   ║
║  🏢 Enterprise │ $99+    │ Custom   │ Ilimitado│ Ilimitado│ ∞   ║
╚═══════════════════════════════════════════════════════════════════╝

Features por plan:
                        Starter  Emprend  Negocio  Profes   Enterprise
Firma electrónica       ❌       ✅       ✅       ✅       ✅
Logo en RIDE            ❌       ✅       ✅       ✅       ✅
Proformas               ❌       ❌       ✅       ✅       ✅
Facturas recurrentes    ❌       ❌       ✅       ✅       ✅
ATS automático          ❌       ❌       ✅       ✅       ✅
Inventario              ❌       ❌       ✅       ✅       ✅
POS                     ❌       ❌       ✅       ✅       ✅
Impresora térmica       ❌       ❌       ✅       ✅       ✅
API REST                ❌       ❌       ❌       ✅       ✅
Webhooks                ❌       ❌       ❌       ✅       ✅
Portal del cliente      ❌       ❌       ❌       ✅       ✅
Acceso contador         ❌       ❌       ❌       ✅       ✅
RIDE personalizado      ❌       ❌       ❌       ❌       ✅
Soporte 24/7            ❌       ❌       ❌       ❌       ✅
Account manager         ❌       ❌       ❌       ❌       ✅
SLA 99.9%               ❌       ❌       ❌       ❌       ✅

Soporte:               KB only  Email    Chat     Priorit  Dedicado
Respuesta SLA:         -        48h      12h      4h       1h

ADD-ONS:
├── Firma electrónica adicional: $15-25 (comisión)
├── +100 documentos extra: $5 (para plan Emprendedor)
├── Migración asistida: $49
├── Capacitación: $79 (hasta 5 personas)
└── Personalización RIDE: $29

REFERIDOS: 20% comisión recurrente del primer año
```

---

## PAQUETES COMPOSER (composer.json)

```json
{
    "name": "tu-empresa/factura-saas",
    "description": "SaaS de Facturación Electrónica Ecuador",
    "type": "project",
    "require": {
        "php": "^8.3",
        "laravel/framework": "^12.0",
        "laravel/sanctum": "^4.0",
        "laravel/horizon": "^5.0",
        "laravel/reverb": "^1.0",
        "laravel/scout": "^10.0",
        "laravel/fortify": "^1.0",
        "laravel/pulse": "^1.0",

        "amephia/sri-ec": "^1.1",

        "filament/filament": "^3.0",
        "filament/spatie-laravel-media-library-plugin": "^3.0",
        "filament/spatie-laravel-settings-plugin": "^3.0",

        "spatie/laravel-permission": "^6.0",
        "spatie/laravel-activitylog": "^4.0",
        "spatie/laravel-backup": "^9.0",
        "spatie/laravel-medialibrary": "^11.0",
        "spatie/laravel-query-builder": "^6.0",

        "barryvdh/laravel-dompdf": "^3.0",
        "maatwebsite/excel": "^3.1",
        "simplesoftwareio/simple-qrcode": "^4.0",
        "league/flysystem-aws-s3-v3": "^3.0",
        "predis/predis": "^2.0",
        "meilisearch/meilisearch-php": "^1.0",
        "openai-php/laravel": "^0.10"
    },
    "require-dev": {
        "laravel/telescope": "^5.0",
        "laravel/pint": "^1.0",
        "pestphp/pest": "^3.0",
        "pestphp/pest-plugin-laravel": "^3.0"
    }
}
```

---

## QUEUES Y HORIZON

```php
// config/horizon.php - Configuración para miles de usuarios

'environments' => [
    'production' => [
        'sri-supervisor' => [
            'connection' => 'redis',
            'queue' => ['sri-send', 'sri-authorize'],
            'balance' => 'auto',
            'minProcesses' => 3,
            'maxProcesses' => 10,
            'tries' => 3,
            'timeout' => 120,
        ],
        'email-supervisor' => [
            'connection' => 'redis',
            'queue' => ['email', 'whatsapp', 'notifications'],
            'balance' => 'auto',
            'minProcesses' => 2,
            'maxProcesses' => 5,
            'tries' => 3,
            'timeout' => 60,
        ],
        'reports-supervisor' => [
            'connection' => 'redis',
            'queue' => ['reports', 'ats', 'exports'],
            'balance' => 'simple',
            'minProcesses' => 1,
            'maxProcesses' => 3,
            'tries' => 1,
            'timeout' => 300,
        ],
        'billing-supervisor' => [
            'connection' => 'redis',
            'queue' => ['billing', 'dunning'],
            'balance' => 'simple',
            'minProcesses' => 1,
            'maxProcesses' => 2,
            'tries' => 3,
            'timeout' => 60,
        ],
        'default-supervisor' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'simple',
            'minProcesses' => 1,
            'maxProcesses' => 3,
            'tries' => 3,
            'timeout' => 60,
        ],
    ],
],
```

---

## OPTIMIZACIÓN MySQL PARA MILES DE USUARIOS

```ini
# my.cnf - Optimizaciones para facturación electrónica masiva

[mysqld]
# InnoDB Buffer Pool: 70-80% de la RAM disponible
innodb_buffer_pool_size = 4G
innodb_buffer_pool_instances = 4

# Logs y escritura
innodb_log_file_size = 1G
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Conexiones (miles de usuarios)
max_connections = 500
thread_cache_size = 50
table_open_cache = 4000

# Query cache (desactivado en MySQL 8, usar Redis)
# Slow query log
slow_query_log = 1
long_query_time = 1

# Charset
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# Temporal tables
tmp_table_size = 256M
max_heap_table_size = 256M

# Sort buffer
sort_buffer_size = 4M
join_buffer_size = 4M
```

---

## ROADMAP DE DESARROLLO

```
FASE 1 - MVP (Semanas 1-8):
├── Setup Laravel 12 + MySQL + Redis + Horizon
├── Modelo de datos completo (migraciones)
├── Trait BelongsToTenant + middleware de aislamiento
├── Auth: registro, login, 2FA, roles básicos
├── CRUD: empresas, clientes, productos
├── Integración amephia/sri-ec: SRIService + DocumentBuilder + SignatureManager
├── Emisión de facturas (flujo completo: crear → firmar → SRI → autorizar)
├── Notas de crédito y retenciones
├── Generación de RIDE (PDF) con DomPDF
├── Dashboard básico del tenant
├── Super Admin con Filament (tenants, planes)
├── Planes y suscripciones (manual)
└── Deploy Docker inicial

FASE 2 - MONETIZACIÓN (Semanas 9-14):
├── Integración pagos: PayPhone/Kushki + Stripe
├── Billing automático: cobro, dunning, suspensión
├── Cupones y descuentos
├── Programa de referidos
├── Notas de débito, guías de remisión
├── Recepción de documentos
├── Gastos personales deducibles
├── Proformas/cotizaciones
├── Reportes: ventas, compras, ATS
├── Onboarding wizard
├── Landing page + pricing page
└── SEO básico

FASE 3 - GROWTH (Semanas 15-20):
├── Inventario completo
├── Módulo POS
├── Facturas recurrentes
├── API REST pública + documentación Swagger
├── Webhooks
├── Impresión térmica (ESC/POS)
├── Portal del cliente
├── Integraciones: WooCommerce
├── Categorización con IA (OpenAI)
├── App PWA
└── Base de conocimiento + video tutoriales

FASE 4 - SCALE (Semanas 21+):
├── SDKs (PHP, Python, Node.js)
├── Multi-moneda
├── White-label para resellers
├── App nativa (Flutter)
├── Read replicas MySQL
├── Sharding por tenant_id (si necesario)
├── Expansión regional
└── Marketplace de integraciones
```

---

## REGLAS CRÍTICAS DE DESARROLLO

1. **SIEMPRE tenant_id**: TODA tabla que tenga datos de usuario DEBE tener tenant_id con índice compuesto. Sin excepciones.

2. **NUNCA llamar al SRI síncronamente**: TODO va por Jobs con Horizon. El SRI es lento e impredecible.

3. **amephia/sri-ec es el ÚNICO punto de contacto con el SRI**: Nunca escribir SOAP/XML directo. Todo pasa por el paquete.

4. **Los secuenciales son sagrados**: Usar `SELECT ... FOR UPDATE` con transacciones para evitar duplicados bajo alta concurrencia.

5. **Las firmas .p12 se encriptan SIEMPRE**: Nunca en texto plano en DB ni en disco sin encriptar. Usar `Crypt::encrypt()`.

6. **El RIDE se genera DESPUÉS de autorización**: Nunca generar PDF antes de tener respuesta del SRI.

7. **Medir TODO desde el día 1**: Laravel Pulse + Telescope en dev. Métricas de negocio en dashboard admin.

8. **Los contadores del tenant se cachean**: `documents_this_month` se incrementa con `increment()`, no con `COUNT(*)`.

9. **Testing del SRI en ambiente de pruebas**: NUNCA mandar datos de prueba a producción del SRI.

10. **El onboarding determina el éxito**: Si un usuario no emite su primera factura en 10 minutos, lo pierdes.

---

*Prompt versión definitiva - Febrero 2026*
*Motor SRI: amephia/sri-ec v1.1+ (MIT License)*
*Stack: Laravel 12 + MySQL 8 + Redis + Filament 3 + Livewire 3*
*Diseñado para escalar a miles de usuarios con suscripciones*
