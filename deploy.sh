#!/bin/bash
# ===========================================
# Factura EC SaaS - Deploy Script
# ===========================================
# Uso:
#   ./deploy.sh              # Deploy completo (primera vez)
#   ./deploy.sh update       # Actualizar codigo existente
#   ./deploy.sh rollback     # Rollback al build anterior
#   ./deploy.sh status       # Ver estado de servicios
#   ./deploy.sh logs         # Ver logs en tiempo real
#   ./deploy.sh backup       # Backup manual de DB
# ===========================================

set -euo pipefail

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Config
DOCKER_DIR="$(cd "$(dirname "$0")/docker" && pwd)"
BACKEND_DIR="$(cd "$(dirname "$0")/backend" && pwd)"
COMPOSE_FILE="$DOCKER_DIR/docker-compose.production.yml"
ENV_FILE="$BACKEND_DIR/.env"
BACKUP_DIR="$DOCKER_DIR/backups"

log() { echo -e "${GREEN}[DEPLOY]${NC} $1"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

# ===========================================
# Pre-flight checks
# ===========================================
preflight() {
    log "Verificando requisitos..."

    command -v docker >/dev/null 2>&1 || error "Docker no instalado"
    command -v docker compose >/dev/null 2>&1 || error "Docker Compose no instalado"

    if [ ! -f "$ENV_FILE" ]; then
        error ".env no encontrado en $ENV_FILE\nCopia .env.production a .env y configura los valores"
    fi

    # Valores CAMBIAR_* => fallo duro (no cachear config con secretos placeholder)
    if grep -q "CAMBIAR_" "$ENV_FILE"; then
        error "Valores sin configurar en .env (CAMBIAR_*):\n$(grep 'CAMBIAR_' "$ENV_FILE" | head -8)\nConfigúralos antes de desplegar."
    fi

    # APP_KEY debe existir y ser válido
    if ! grep -qE '^APP_KEY=base64:.+' "$ENV_FILE"; then
        error "APP_KEY no configurado. Genera uno con: php artisan key:generate --show"
    fi

    # APP_DEBUG debe ser false => fallo duro
    if grep -q "APP_DEBUG=true" "$ENV_FILE"; then
        error "APP_DEBUG=true en .env — debe ser false en producción."
    fi

    # Certificados SSL obligatorios (nginx no arranca sin ellos) => fallo duro
    if [ ! -f "$DOCKER_DIR/nginx/ssl/fullchain.pem" ] || [ ! -f "$DOCKER_DIR/nginx/ssl/privkey.pem" ]; then
        error "Faltan certificados SSL en docker/nginx/ssl/ (fullchain.pem y privkey.pem).\nProvisiónalos: certbot certonly --standalone -d facturacion.amephia.com\ny copia los .pem a docker/nginx/ssl/"
    fi

    log "Pre-flight OK"
}

# ===========================================
# Deploy completo (primera vez)
# ===========================================
deploy_full() {
    preflight

    log "=== DEPLOY COMPLETO ==="

    # Crear directorios necesarios
    mkdir -p "$DOCKER_DIR/nginx/ssl"
    mkdir -p "$BACKUP_DIR"

    # Build
    log "Construyendo imagenes..."
    docker compose -f "$COMPOSE_FILE" build --no-cache

    # Start infrastructure first
    log "Iniciando MySQL y Redis..."
    docker compose -f "$COMPOSE_FILE" up -d mysql redis
    sleep 10

    # Wait for MySQL
    log "Esperando MySQL..."
    for i in $(seq 1 30); do
        if docker compose -f "$COMPOSE_FILE" exec mysql mysqladmin ping -h localhost --silent 2>/dev/null; then
            break
        fi
        sleep 2
    done

    # Start app
    log "Iniciando aplicacion..."
    docker compose -f "$COMPOSE_FILE" up -d app

    # Publicar assets Vite compilados al host (nginx los sirve desde public/).
    log "Publicando assets compilados..."
    docker cp factura-ec-app:/var/www/html/public/build "$BACKEND_DIR/public/" 2>/dev/null || true

    # Run Laravel setup
    log "Ejecutando migraciones..."
    docker compose -f "$COMPOSE_FILE" exec app php artisan migrate --force

    log "Generando APP_KEY (si no existe)..."
    docker compose -f "$COMPOSE_FILE" exec app php artisan key:generate --force --no-interaction 2>/dev/null || true

    log "Creando storage link..."
    docker compose -f "$COMPOSE_FILE" exec app php artisan storage:link --force 2>/dev/null || true

    log "Ejecutando seeders..."
    docker compose -f "$COMPOSE_FILE" exec app php artisan db:seed --force

    log "Cacheando configuracion..."
    docker compose -f "$COMPOSE_FILE" exec app php artisan config:clear
    docker compose -f "$COMPOSE_FILE" exec app php artisan route:clear
    docker compose -f "$COMPOSE_FILE" exec app php artisan view:cache
    docker compose -f "$COMPOSE_FILE" exec app php artisan event:cache

    # Create MinIO bucket
    log "Configurando MinIO bucket..."
    docker compose -f "$COMPOSE_FILE" up -d minio
    sleep 5
    docker compose -f "$COMPOSE_FILE" exec minio mc alias set local http://localhost:9000 "$AWS_ACCESS_KEY_ID" "$AWS_SECRET_ACCESS_KEY" 2>/dev/null || true
    docker compose -f "$COMPOSE_FILE" exec minio mc mb local/factura-ec --ignore-existing 2>/dev/null || true

    # Start all remaining services
    log "Iniciando todos los servicios..."
    docker compose -f "$COMPOSE_FILE" up -d

    # Verify
    log "Verificando servicios..."
    sleep 5
    show_status

    echo ""
    log "=== DEPLOY COMPLETO EXITOSO ==="
    log "App: https://$(grep APP_URL "$ENV_FILE" | cut -d'/' -f3)"
    log "Horizon: https://$(grep APP_URL "$ENV_FILE" | cut -d'/' -f3)/horizon"
    log "Admin: https://$(grep APP_URL "$ENV_FILE" | cut -d'/' -f3)/admin"
}

# ===========================================
# Update (deploy de codigo nuevo)
# ===========================================
deploy_update() {
    preflight

    log "=== ACTUALIZACION ==="

    # Backup DB before update
    backup_db

    # Pull latest code (if using git)
    if [ -d "$BACKEND_DIR/.git" ]; then
        log "Pulling latest code..."
        cd "$BACKEND_DIR" && git pull
    fi

    # Rebuild app + horizon + scheduler (comparten Dockerfile pero son
    # imágenes separadas; si solo se reconstruye app, los workers quedan con
    # código viejo y los jobs fallan con __PHP_Incomplete_Class).
    log "Reconstruyendo imagenes (app, horizon, scheduler)..."
    docker compose -f "$COMPOSE_FILE" build app horizon scheduler

    # Maintenance mode
    log "Activando modo mantenimiento..."
    docker compose -f "$COMPOSE_FILE" exec app php artisan down --retry=60 || true

    # Update containers
    log "Actualizando contenedores..."
    docker compose -f "$COMPOSE_FILE" up -d app horizon scheduler

    # nginx cachea la IP del upstream 'app'; al recrear el contenedor cambia
    # de IP y nginx sigue con la vieja -> 502 en cascada. Recargarlo lo evita.
    log "Recargando nginx (re-resolver upstream)..."
    docker compose -f "$COMPOSE_FILE" restart nginx

    # Copiar assets Vite compilados al host (nginx los sirve desde public/).
    log "Publicando assets compilados..."
    docker cp factura-ec-app:/var/www/html/public/build "$BACKEND_DIR/public/" 2>/dev/null || true

    # Run migrations
    log "Ejecutando migraciones..."
    docker compose -f "$COMPOSE_FILE" exec app php artisan migrate --force

    # Clear and rebuild caches
    log "Recacheando..."
    docker compose -f "$COMPOSE_FILE" exec app php artisan config:clear
    docker compose -f "$COMPOSE_FILE" exec app php artisan route:clear
    docker compose -f "$COMPOSE_FILE" exec app php artisan view:cache
    docker compose -f "$COMPOSE_FILE" exec app php artisan event:cache

    # Restart Horizon to pick up new code
    log "Reiniciando Horizon..."
    docker compose -f "$COMPOSE_FILE" exec app php artisan horizon:terminate || true

    # Disable maintenance
    log "Desactivando modo mantenimiento..."
    docker compose -f "$COMPOSE_FILE" exec app php artisan up

    log "=== ACTUALIZACION EXITOSA ==="
}

# ===========================================
# Rollback
# ===========================================
rollback() {
    warn "=== ROLLBACK ==="

    # Maintenance mode
    docker compose -f "$COMPOSE_FILE" exec app php artisan down --retry=60 || true

    # Rollback last migration
    log "Rollback ultima migracion..."
    docker compose -f "$COMPOSE_FILE" exec app php artisan migrate:rollback --step=1 --force

    # Rebuild with previous image
    if [ -d "$BACKEND_DIR/.git" ]; then
        log "Revirtiendo al commit anterior..."
        cd "$BACKEND_DIR" && git checkout HEAD~1
        docker compose -f "$COMPOSE_FILE" build app
        docker compose -f "$COMPOSE_FILE" up -d app horizon scheduler
    fi

    # Recache
    docker compose -f "$COMPOSE_FILE" exec app php artisan config:clear
    docker compose -f "$COMPOSE_FILE" exec app php artisan route:clear

    # Up
    docker compose -f "$COMPOSE_FILE" exec app php artisan up

    log "=== ROLLBACK COMPLETO ==="
}

# ===========================================
# Status
# ===========================================
show_status() {
    log "Estado de servicios:"
    docker compose -f "$COMPOSE_FILE" ps --format "table {{.Name}}\t{{.Status}}\t{{.Ports}}"

    echo ""
    log "Horizon status:"
    docker compose -f "$COMPOSE_FILE" exec app php artisan horizon:status 2>/dev/null || warn "Horizon no disponible"

    echo ""
    log "Uso de disco:"
    docker system df --format "table {{.Type}}\t{{.TotalCount}}\t{{.Size}}\t{{.Reclaimable}}"
}

# ===========================================
# Logs
# ===========================================
show_logs() {
    docker compose -f "$COMPOSE_FILE" logs -f --tail=100 app horizon scheduler nginx
}

# ===========================================
# Backup
# ===========================================
backup_db() {
    mkdir -p "$BACKUP_DIR"
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    BACKUP_FILE="$BACKUP_DIR/factura_ec_${TIMESTAMP}.sql.gz"

    log "Backup DB -> $BACKUP_FILE"
    docker compose -f "$COMPOSE_FILE" exec mysql \
        mysqldump -u root -p"${DB_ROOT_PASSWORD:-rootsecret}" factura_ec \
        | gzip > "$BACKUP_FILE"

    # Keep only last 7 backups
    ls -t "$BACKUP_DIR"/*.sql.gz 2>/dev/null | tail -n +8 | xargs rm -f 2>/dev/null || true

    log "Backup completado: $(du -h "$BACKUP_FILE" | cut -f1)"
}

# ===========================================
# Load env vars for script use
# ===========================================
if [ -f "$ENV_FILE" ]; then
    set -a
    source "$ENV_FILE"
    set +a
fi

# ===========================================
# Main
# ===========================================
case "${1:-}" in
    update)
        deploy_update
        ;;
    rollback)
        rollback
        ;;
    status)
        show_status
        ;;
    logs)
        show_logs
        ;;
    backup)
        backup_db
        ;;
    *)
        deploy_full
        ;;
esac
