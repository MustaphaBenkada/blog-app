FROM php:8.3-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    git \
    libzip-dev \
    libpq-dev \
    default-mysql-client \
    redis-tools \
    ca-certificates \
    autoconf \
    && docker-php-ext-install pdo pdo_mysql zip \
    && git clone --branch 6.0.2 https://github.com/phpredis/phpredis.git /usr/src/php/ext/redis \
    && docker-php-ext-install redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*


COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer global require laravel/installer

# Create storage folders, set permissions, and create the storage symlink
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache \
    && php artisan storage:link || true

# Copy startup script and make it executable
COPY startup.sh /usr/local/bin/startup.sh
RUN chmod +x /usr/local/bin/startup.sh

ENTRYPOINT ["/usr/local/bin/startup.sh"]
CMD ["php-fpm"]
