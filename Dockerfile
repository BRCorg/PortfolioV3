# Dockerfile pour Portfolio V3 - Production
FROM php:8.2-fpm-alpine

# Métadonnées
LABEL maintainer="Berancan Guven"
LABEL description="Portfolio V3 - PHP 8.2 avec Nginx"

# Installation des dépendances système et extensions PHP
RUN apk update && apk add --no-cache \
    nginx \
    supervisor \
    git \
    unzip \
    libzip-dev \
    mysql-client \
    && docker-php-ext-install pdo pdo_mysql zip opcache

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration PHP optimisée pour production
RUN echo "memory_limit = 256M" > /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "upload_max_filesize = 10M" > /usr/local/etc/php/conf.d/upload-limit.ini \
    && echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/upload-limit.ini \
    && echo "max_execution_time = 60" > /usr/local/etc/php/conf.d/execution-time.ini

# Configuration OPcache pour production
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=4000'; \
        echo 'opcache.revalidate_freq=60'; \
        echo 'opcache.fast_shutdown=1'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Créer les répertoires nécessaires
RUN mkdir -p /var/www/html /var/log/nginx /var/log/supervisor /run/nginx \
    /var/lib/nginx/tmp/client_body \
    /var/lib/nginx/tmp/proxy \
    /var/lib/nginx/tmp/fastcgi \
    /var/lib/nginx/tmp/uwsgi \
    /var/lib/nginx/tmp/scgi

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers du projet
COPY . /var/www/html

# Installation des dépendances Composer (production uniquement)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Configuration Nginx
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Configuration Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/public \
    && chmod -R 775 /var/www/html/public/uploads \
    && chmod 600 /var/www/html/.env 2>/dev/null || true \
    && chown -R www-data:www-data /var/lib/nginx/tmp \
    && chmod -R 777 /var/lib/nginx/tmp

# Exposer le port HTTP
EXPOSE 80

# Démarrer Supervisor (gère Nginx + PHP-FPM)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
