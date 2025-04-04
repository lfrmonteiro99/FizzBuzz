#!/bin/sh
set -e

# Copy the environment variables from Docker to Symfony (if not already configured)
if [ ! -f /var/www/app/.env.local ]; then
    echo "Creating .env.local file with environment variables"
    cat > /var/www/app/.env.local <<EOF
APP_ENV=prod
APP_SECRET=${APP_SECRET}
APP_DEBUG=${APP_DEBUG:-0}
DATABASE_URL="mysql://${MYSQL_USER}:${MYSQL_PASSWORD}@mysql:3306/${MYSQL_DATABASE}?serverVersion=8.0&charset=utf8mb4"
# Logging Configuration
LOG_LEVEL=${LOG_LEVEL:-warning}
MONOLOG_LEVEL=${MONOLOG_LEVEL:-warning}
EOF
fi

# Ensure directories exist with proper permissions
mkdir -p /var/www/app/var/cache /var/www/app/var/log
chmod -R 777 /var/www/app/var

# Wait for MySQL to be ready
echo "Waiting for MySQL..."
timeout=60
while ! nc -z mysql 3306; do
    timeout=$((timeout - 1))
    if [ $timeout -eq 0 ]; then
        echo "MySQL connection timeout - continuing anyway"
        break
    fi
    sleep 1
    echo "Waiting for MySQL connection... ($timeout seconds remaining)"
done

if [ $timeout -gt 0 ]; then
    echo "MySQL is up and running!"
fi

# Clear and warm up the cache
php /var/www/app/bin/console cache:clear --no-warmup --env=prod
php /var/www/app/bin/console cache:warmup --env=prod

# Run migrations if needed (with safety checks)
if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
    echo "Running database migrations..."
    php /var/www/app/bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=prod
fi

# Execute the main command
exec "$@" 