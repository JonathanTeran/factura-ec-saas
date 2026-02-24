FROM php:8.3-fpm-alpine

# Argumentos
ARG user=factura
ARG uid=1000

# Instalar dependencias del sistema
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    openssl-dev \
    libxslt-dev \
    $PHPIZE_DEPS

# Configurar e instalar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        soap \
        xsl \
        opcache

# Instalar Redis
RUN pecl install redis && docker-php-ext-enable redis

# Copiar configuración de PHP
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Crear usuario del sistema
RUN addgroup -g $uid $user && adduser -u $uid -G $user -s /bin/sh -D $user

# Crear directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos del proyecto
COPY --chown=$user:$user . .

# Instalar dependencias de Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Permisos
RUN chown -R $user:$user /var/www/html/storage /var/www/html/bootstrap/cache

# Cambiar al usuario no root
USER $user

EXPOSE 9000

CMD ["php-fpm"]
