# Guía de Despliegue — Factura EC SaaS

Despliegue en producción para **facturacion.amephia.com** usando Docker Compose
y el script `deploy.sh` que vive en la raíz del repositorio.

Stack: Laravel 12 (PHP 8.3-fpm-alpine) · MySQL 8 · Redis 7 · Horizon ·
Scheduler · Meilisearch · MinIO · Nginx (TLS).

---

## 1. Prerrequisitos

En el servidor de producción (VPS, 1 CPU mínimo):

- **Docker** y **Docker Compose v2** (`docker compose`, no `docker-compose`).
- **Git** (el `deploy.sh update` hace `git pull` del backend).
- Un **dominio** apuntando al servidor: `facturacion.amephia.com`.
- **DNS**: registro `A` (y/o `AAAA`) de `facturacion.amephia.com` → IP pública del
  servidor. Verifica con `dig +short facturacion.amephia.com` antes de seguir.
- Puertos **80** y **443** abiertos en el firewall (los publica el servicio `nginx`).
- Clonar el repo en el servidor, p. ej. en `/opt/factura-ec-saas`.

```bash
git clone https://github.com/<org>/factura-ec-saas.git /opt/factura-ec-saas
cd /opt/factura-ec-saas
```

> Rutas reales que usa `deploy.sh` (resueltas relativas a su ubicación):
> - Compose file: `docker/docker-compose.production.yml`
> - `.env` de la app: `backend/.env`
> - Backups manuales: `docker/backups/`
> - Certificados SSL: `docker/nginx/ssl/`

---

## 2. Configurar `.env.production`

El archivo plantilla está en `backend/.env.production`. Cópialo a `backend/.env`
(que es el que monta `docker-compose.production.yml` como volumen de solo lectura
en `app`, `horizon` y `scheduler`):

```bash
cp backend/.env.production backend/.env
```

Edita `backend/.env` y reemplaza **todos** los valores marcados `CAMBIAR_*`:

| Variable | Qué poner |
|---|---|
| `APP_KEY` | Se genera solo en el primer deploy (`key:generate`). Déjalo como está. |
| `DB_PASSWORD` | Password fuerte del usuario `factura` de MySQL. |
| `DB_ROOT_PASSWORD` | Password fuerte de `root` de MySQL (lo usa el backup manual). |
| `REDIS_PASSWORD` | Password de Redis. |
| `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` | Credenciales de MinIO (también son `MINIO_ROOT_USER`/`MINIO_ROOT_PASSWORD`). |
| `MAIL_HOST` / `MAIL_USERNAME` / `MAIL_PASSWORD` | SMTP real (envío de facturas y avisos de backup). |
| `MEILISEARCH_KEY` | Master key de Meilisearch. |
| `REVERB_APP_KEY` / `REVERB_APP_SECRET` | Claves de Laravel Reverb (websockets). |
| `TWILIO_*` / `OPENAI_API_KEY` | Solo si usas WhatsApp / categorización IA (opcionales). |

Confirma estos valores ya correctos en la plantilla:

- `APP_ENV=production`, `APP_DEBUG=false`
- `APP_URL=https://facturacion.amephia.com`
- `SRI_ENVIRONMENT=2` (producción del SRI)
- `BACKUP_NOTIFICATION_EMAIL` *(opcional)* — correo que recibirá avisos de backup
  fallido (por defecto `soporte@amephia.com`, definido en `config/backup.php`).

`deploy.sh` avisará (`preflight`) si quedan valores `CAMBIAR_` o si
`APP_DEBUG=true`.

---

## 3. Certificados SSL (Let's Encrypt)

Nginx (`docker/nginx/conf.d/production.conf`) espera los certificados montados en
`docker/nginx/ssl/` con estos nombres exactos:

```
docker/nginx/ssl/fullchain.pem
docker/nginx/ssl/privkey.pem
```

Genera los certificados con **certbot** en modo standalone (los puertos 80/443
deben estar libres; si nginx ya corre, deténlo antes con
`docker compose -f docker/docker-compose.production.yml stop nginx`):

```bash
mkdir -p docker/nginx/ssl

# Instalar certbot (Debian/Ubuntu)
sudo apt-get update && sudo apt-get install -y certbot

# Emitir el certificado
sudo certbot certonly --standalone \
  -d facturacion.amephia.com \
  --agree-tos -m soporte@amephia.com --no-eff-email

# Copiar al directorio que monta nginx
sudo cp /etc/letsencrypt/live/facturacion.amephia.com/fullchain.pem docker/nginx/ssl/fullchain.pem
sudo cp /etc/letsencrypt/live/facturacion.amephia.com/privkey.pem   docker/nginx/ssl/privkey.pem
```

### Renovación

Let's Encrypt caduca cada 90 días. Renueva y recarga nginx:

```bash
sudo certbot renew --quiet
sudo cp /etc/letsencrypt/live/facturacion.amephia.com/fullchain.pem docker/nginx/ssl/fullchain.pem
sudo cp /etc/letsencrypt/live/facturacion.amephia.com/privkey.pem   docker/nginx/ssl/privkey.pem
docker compose -f docker/docker-compose.production.yml restart nginx
```

> Puedes poner ese bloque en un `cron` mensual del host (la renovación de
> certificados es del host, distinta del backup de BD que ya gestiona Laravel).

---

## 4. Primer despliegue

Con `.env` configurado y certificados en su sitio:

```bash
./deploy.sh
```

Esto (ver `deploy.sh` → `deploy_full`):

1. Corre el `preflight` (Docker presente, `.env` existe, avisa de `CAMBIAR_`/SSL).
2. Crea `docker/nginx/ssl/` y `docker/backups/`.
3. `docker compose build --no-cache` de todas las imágenes.
4. Levanta MySQL + Redis y espera a que MySQL responda.
5. Levanta `app` y ejecuta: `migrate --force`, `key:generate`, `storage:link`,
   `db:seed --force`.
6. Cachea config/route/view/event.
7. Levanta MinIO y crea el bucket `factura-ec`.
8. Levanta el resto de servicios (`nginx`, `horizon`, `scheduler`,
   `meilisearch`).
9. Muestra el estado final.

Al terminar tendrás:

- App: `https://facturacion.amephia.com`
- Horizon: `https://facturacion.amephia.com/horizon`
- Admin (Filament): `https://facturacion.amephia.com/admin`

---

## 5. Actualizaciones

```bash
./deploy.sh update
```

Flujo (`deploy.sh` → `deploy_update`):

1. `preflight`.
2. **Backup de BD antes de actualizar** (ver §7).
3. `git pull` del backend.
4. `docker compose build app`.
5. Modo mantenimiento (`artisan down`).
6. Recrea `app`, `horizon`, `scheduler`.
7. `migrate --force` + recacheo de config/route/view/event.
8. `horizon:terminate` (Horizon reinicia con el código nuevo).
9. `artisan up` (sale de mantenimiento).

> **CD automático**: el workflow `.github/workflows/deploy.yml` ejecuta exactamente
> `./deploy.sh update` por SSH tras un push a `main` que pase CI. Ver §8.

---

## 6. Rollback, estado y logs

```bash
# Rollback: down + rollback de 1 migración + git checkout HEAD~1 + rebuild + up
./deploy.sh rollback

# Estado de contenedores + Horizon + uso de disco
./deploy.sh status

# Logs en vivo (app, horizon, scheduler, nginx)
./deploy.sh logs
```

---

## 7. Backups

### 7.1 Backups automáticos (recomendado — ya configurados)

Los backups corren **solos**. No necesitas cron externo: el contenedor
`scheduler` ejecuta `php artisan schedule:run` cada 60 s, y el scheduler de
Laravel (`backend/routes/console.php`) tiene programado **spatie/laravel-backup**:

| Comando | Horario | Qué hace |
|---|---|---|
| `backup:run` | diario 02:00 | Dump de MySQL comprimido (`.zip` con gzip interno). |
| `backup:clean` | diario 03:30 | Borra backups según retención. |
| `backup:monitor` | diario 04:00 | Verifica salud; avisa por mail si falla. |

Configuración en **`backend/config/backup.php`**:

- **Destino**: disco `local` de Laravel → `storage/app/private`, persistido en el
  volumen Docker `app_storage` (sobrevive a rebuilds).
- **Prefijo** de archivo: `factura_ec_`.
- **Retención**: 7 días (coherente con la rotación de 7 copias del backup manual).
- **Notificaciones**: mail a `BACKUP_NOTIFICATION_EMAIL` (def. `soporte@amephia.com`)
  ante fallo o backup no saludable.

Comandos útiles:

```bash
CF=docker/docker-compose.production.yml

# Forzar un backup ahora
docker compose -f $CF exec app php artisan backup:run

# Listar backups y su estado de salud
docker compose -f $CF exec app php artisan backup:list

# Ver los archivos en el disco local del contenedor
docker compose -f $CF exec app ls -lh storage/app/private
```

Para enviar copias **off-site** a MinIO/S3, añade `'s3'` a
`backup.destination.disks` en `config/backup.php`.

### 7.2 Backup manual (rápido, vía mysqldump)

`deploy.sh` también ofrece un backup manual independiente (lo invoca automáticamente
antes de cada `update`):

```bash
./deploy.sh backup
```

- Hace `mysqldump` de la BD `factura_ec` con `DB_ROOT_PASSWORD`.
- Guarda `docker/backups/factura_ec_<timestamp>.sql.gz`.
- Conserva solo las **últimas 7** copias (rota el resto).

### 7.3 Restaurar

**Desde un backup manual** (`docker/backups/*.sql.gz`), usando el servicio `mysql`:

```bash
CF=docker/docker-compose.production.yml

# Descomprime y reimporta (ajusta el nombre del archivo)
gunzip -c docker/backups/factura_ec_20260630_020000.sql.gz | \
  docker compose -f $CF exec -T mysql \
  mysql -u root -p"$DB_ROOT_PASSWORD" factura_ec
```

**Desde un backup de spatie** (un `.zip` en `storage/app/private`): copia el zip
fuera del contenedor, descomprímelo para obtener el `.sql`/`.sql.gz` interno y
reimpórtalo con el mismo `mysql -u root` de arriba.

```bash
# Copiar el zip del contenedor al host
docker compose -f $CF cp app:/var/www/html/storage/app/private/<archivo>.zip ./
unzip <archivo>.zip            # extrae db-dumps/...
```

> En desarrollo, el `Makefile` también trae `make db-backup` y
> `make db-restore FILE=archivo.sql` (apuntan a `docker/docker-compose.yml`, no a
> producción).

---

## 8. CI/CD (GitHub Actions)

- **CI** (`.github/workflows/ci.yml`): en push/PR a `main`/`develop` corre Pint,
  tests del backend, build de assets y `flutter analyze`.
- **CD** (`.github/workflows/deploy.yml`): se dispara cuando **CI termina con éxito
  en `main`** (vía `workflow_run`) o manualmente (`workflow_dispatch`). Conecta por
  SSH al servidor y ejecuta `git pull` + `./deploy.sh update` + `./deploy.sh status`.

### Secrets de GitHub a configurar

`Settings → Secrets and variables → Actions`:

| Secret | Descripción |
|---|---|
| `SSH_HOST` | IP o hostname del servidor de producción. |
| `SSH_USER` | Usuario SSH que puede ejecutar `deploy.sh`. |
| `SSH_KEY` | Clave **privada** SSH (la pública va en `~/.ssh/authorized_keys` del servidor). |
| `SSH_PORT` | *(Opcional)* Puerto SSH; por defecto `22`. |
| `DEPLOY_PATH` | Ruta absoluta del repo en el servidor (p. ej. `/opt/factura-ec-saas`). |

### Gate de aprobación manual

El job `deploy` usa el environment `production`. En
`Settings → Environments → production` activa **Required reviewers** para exigir
aprobación humana antes de cada despliegue. Sin esa protección, el deploy es
automático en cuanto CI pasa en `main`.
