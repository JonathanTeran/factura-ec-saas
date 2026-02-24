# Factura EC SaaS

Sistema de Facturación Electrónica para Ecuador - Plataforma SaaS Multi-tenant

## Stack Tecnológico

### Backend
- **Framework**: Laravel 12
- **PHP**: 8.3+
- **Base de Datos**: MySQL 8.0+
- **Cache/Queue**: Redis 7+
- **Search**: Meilisearch
- **WebSockets**: Laravel Reverb
- **Motor SRI**: amephia/sri-ec

### Frontend
- **Admin Panel**: Filament 3
- **Tenant UI**: Livewire 3 + Alpine.js + TailwindCSS 4

### Mobile
- **Framework**: Flutter 3.x
- **State Management**: Riverpod
- **HTTP**: Dio + Retrofit

## Estructura del Proyecto

```
factura-ec-saas/
├── backend/          # Laravel API + Admin
├── mobile/           # Flutter App
├── docker/           # Docker configuration
├── docs/             # Documentation
└── shared/           # Shared resources
```

## Requisitos

- Docker & Docker Compose
- PHP 8.3+
- Composer
- Node.js 20+
- Flutter 3.x (para desarrollo móvil)

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/tu-usuario/factura-ec-saas.git
cd factura-ec-saas
```

### 2. Configurar Backend

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
```

### 3. Iniciar con Docker

```bash
cd docker
docker-compose up -d
```

### 4. Ejecutar migraciones

```bash
docker-compose exec app php artisan migrate --seed
```

### 5. Acceder a la aplicación

- **App**: http://localhost:8000
- **Horizon**: http://localhost:8000/horizon
- **Mailpit**: http://localhost:8025
- **MinIO Console**: http://localhost:9001

## Comandos Útiles

```bash
# Iniciar servicios
make up

# Detener servicios
make down

# Ver logs
make logs

# Ejecutar migraciones
make migrate

# Limpiar cache
make clear

# Ejecutar tests
make test
```

## Arquitectura

### Multi-tenant (Single Database)

El sistema usa una arquitectura multi-tenant con una sola base de datos y aislamiento por filas usando `tenant_id` en cada tabla.

```php
// Trait que se aplica a todos los modelos de tenant
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });
    }
}
```

### Flujo de Documentos Electrónicos

```
1. Usuario crea documento (borrador)
2. Usuario emite documento
3. Job procesa documento (cola sri-send)
4. amephia/sri-ec genera XML, firma y envía al SRI
5. SRI responde (autorizado/rechazado)
6. Sistema actualiza estado y genera RIDE
7. Sistema envía email/WhatsApp al cliente
```

## Planes de Suscripción

| Plan | Precio/Mes | Documentos | Usuarios | RUCs |
|------|-----------|------------|----------|------|
| Starter | Gratis | 10 | 1 | 1 |
| Emprendedor | $4.99 | 50 | 3 | 1 |
| Negocio | $14.99 | Ilimitado | 10 | 3 |
| Profesional | $34.99 | Ilimitado | ∞ | ∞ |
| Enterprise | $99+ | Ilimitado | ∞ | ∞ |

## Licencia

Propietario - Todos los derechos reservados

## Soporte

Para soporte técnico, contactar a soporte@factura-ec.com
