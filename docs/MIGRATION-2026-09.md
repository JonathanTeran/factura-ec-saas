# Migración a facturon.ec y al droplet nuevo (septiembre 2026)

Estado al 13-sep-2026: el droplet nuevo (`159.89.40.217`, cuenta nueva de DigitalOcean,
2 vCPU / 2 GB + 4 GB swap, Ubuntu 24.04) ya tiene Docker, el repo en `/opt/factura-ec-saas`,
el `.env` de producción, el certificado actual de `facturacion.amephia.com`, la base de
datos y los volúmenes copiados del droplet viejo (`157.230.236.198`) y la pila completa
corriendo. Falta el **cutover**: DNS, certificado nuevo y sincronización final.

## 1. DNS (lo hace el dueño de los dominios)

| Registro | Dónde | Valor |
|---|---|---|
| `facturon.ec` A | DonDominio | `159.89.40.217` |
| `www.facturon.ec` A (o CNAME → `facturon.ec`) | DonDominio | `159.89.40.217` |
| `facturacion.amephia.com` A | Hostinger (dns-parking) | `159.89.40.217` |

`facturacion.amephia.com` debe seguir existiendo: la app móvil publicada tiene esa URL
fija para la API. Bajar el TTL a 300 s antes del cambio acorta la propagación.

## 2. Sincronización final (justo antes de cambiar el DNS)

En el droplet viejo poner mantenimiento, volcar y copiar al nuevo, importar y levantar:

    # viejo
    cd /opt/factura-ec-saas && docker compose -f docker/docker-compose.production.yml exec app php artisan down
    set -a; . backend/.env; set +a
    docker exec factura-ec-mysql mysqldump -uroot -p"$DB_ROOT_PASSWORD" --single-transaction --routines --triggers factura_ec | gzip > /root/sync/factura_ec.sql.gz
    docker run --rm -v docker_app_storage:/v -v /root/sync:/b alpine tar czf /b/app_storage.tgz -C /v .
    docker run --rm -v docker_minio_data:/v -v /root/sync:/b alpine tar czf /b/minio_data.tgz -C /v .
    # copiar /root/sync/* al nuevo (scp vía la máquina local) y en el nuevo:
    cd /opt/factura-ec-saas && set -a; . backend/.env; set +a
    zcat /root/sync/factura_ec.sql.gz | docker exec -i factura-ec-mysql mysql -uroot -p"$DB_ROOT_PASSWORD" factura_ec
    docker run --rm -v docker_app_storage:/v -v /root/sync:/b alpine sh -c "cd /v && tar xzf /b/app_storage.tgz"
    docker run --rm -v docker_minio_data:/v -v /root/sync:/b alpine sh -c "cd /v && tar xzf /b/minio_data.tgz"
    docker compose -f docker/docker-compose.production.yml exec app php artisan migrate --force
    docker compose -f docker/docker-compose.production.yml exec app php artisan config:clear

## 3. Certificado (cuando el DNS ya apunte al nuevo)

    /root/issue-cert.sh

Emite con certbot en modo webroot (`backend/public`, que nginx sirve en
`/.well-known/acme-challenge/`) un solo certificado para `facturon.ec`, `www.facturon.ec` y
`facturacion.amephia.com`, lo copia a `docker/nginx/ssl/` y reinicia nginx. El hook de
renovación en `/etc/letsencrypt/renewal-hooks/deploy/facturaec.sh` repite la copia en
cada renovación automática (`certbot.timer`).

## 4. Redirección del dominio viejo (después del certificado)

En `docker/nginx/conf.d/production.conf` del nuevo, dentro de `location /` del bloque
443, antes de `try_files`:

    if ($host = facturacion.amephia.com) { return 301 https://facturon.ec$request_uri; }

Solo aplica a rutas del frontend (`$is_laravel = 0`): la API, `/admin`, `/portal`,
`/terms` y `/privacy` siguen respondiendo en el dominio viejo para la app móvil. Luego
`docker compose -f docker/docker-compose.production.yml up -d --force-recreate nginx`.

## 5. Después

- Apagar el droplet viejo tras una semana sin tráfico (conservar el snapshot).
- Cambiar la URL base de la app móvil a `https://facturon.ec` en la siguiente release.
- Recomendado: redimensionar el droplet nuevo a 4 GB (hoy usa ~1,5 GB + swap con la pila completa; los builds de Docker son lentos).
