#!/bin/bash

# Wait for MySQL to be ready and the database to exist
echo "Waiting for MySQL to be ready and database to exist..."
until mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "USE $DB_DATABASE;" 2>/dev/null; do
    sleep 2
done
echo "MySQL and database $DB_DATABASE are ready!"

# Wait for Redis to be ready
echo "Waiting for Redis to be ready..."
while ! redis-cli -h "$REDIS_HOST" ping > /dev/null 2>&1; do
    sleep 2
done
echo "Redis is ready!"

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

# Rebuild search index
echo "Rebuilding search index..."
php artisan posts:rebuild-search-index

# Warm up Laravel caches
echo "Warming up Laravel caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure storage and cache permissions and symlink
chmod -R 777 storage bootstrap/cache
php artisan storage:link || true

# Start the application (handled by php-fpm in Docker CMD)
# echo "Starting Laravel application..."
# php artisan serve --host=0.0.0.0 --port=8000 

exec "$@" 