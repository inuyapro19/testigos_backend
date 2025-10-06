# Usa una imagen de PHP con FPM y extensiones necesarias
FROM php:8.3-fpm

# Instalar dependencias necesarias y extensiones adicionales
RUN apt-get update && apt-get install -y \
    git unzip curl libpq-dev libzip-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libxslt1-dev libpq-dev \
    libicu-dev libbz2-dev libreadline-dev libgmp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_pgsql zip bcmath intl opcache soap xsl bz2 gmp

# Configurar OPcache para mejor rendimiento
RUN echo "opcache.memory_consumption=256" > /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=60" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/conf.d/opcache.ini

# Instalar Composer dentro del contenedor
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Instalar Node.js y npm para compilar assets (opcional)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm

# Configurar usuario para evitar problemas de permisos
RUN useradd -m -d /home/laravel laravel
WORKDIR /var/www

# Permitir subida de archivos grandes en PHP y aumentar tiempo de ejecución
RUN echo "upload_max_filesize=128M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=128M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/uploads.ini

CMD ["php-fpm"]

