#!/bin/bash
set -e

# Copy the environment variables from Docker to Symfony
if [ ! -f /var/www/app/.env.local ]; then
    echo "Creating .env.local file with environment variables"
    cat > /var/www/app/.env.local <<EOF
APP_ENV=${APP_ENV:-dev}
APP_SECRET=${APP_SECRET:-$(openssl rand -hex 16)}
DATABASE_URL="mysql://${MYSQL_USER:-symfony}:${MYSQL_PASSWORD:-symfony}@mysql:3306/${MYSQL_DATABASE:-symfony}?serverVersion=8.0&charset=utf8mb4"
# Logging Configuration
LOG_LEVEL=${LOG_LEVEL:-debug}
MONOLOG_LEVEL=${MONOLOG_LEVEL:-debug}
EOF
fi

# Create test environment file if it doesn't exist
if [ ! -f /var/www/app/.env.test.local ]; then
    echo "Creating .env.test.local file for test environment"
    cat > /var/www/app/.env.test.local <<EOF
APP_ENV=test
APP_SECRET=test_secret
APP_DEBUG=1
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data/test.db"
# Test logging - typically higher level to reduce noise
LOG_LEVEL=notice
EOF
fi

# Set proper permissions for Symfony directories
echo "Setting up directories and permissions..."
mkdir -p /var/www/app/var/cache /var/www/app/var/log /var/www/app/var/data
chown -R www-data:www-data /var/www/app/var
chmod -R 777 /var/www/app/var

# Create log files if they don't exist
touch /var/www/app/var/log/dev.log
touch /var/www/app/var/log/app.log
chown www-data:www-data /var/www/app/var/log/*.log
chmod 666 /var/www/app/var/log/*.log

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
while ! mysqladmin ping -h"mysql" -u"root" -p"root" --silent; do
    sleep 1
done

echo "MySQL is up and running!"

# Remove existing vendor directory and composer.lock if they exist
if [ -d "/var/www/app/vendor" ]; then
    rm -rf /var/www/app/vendor
fi
if [ -f "/var/www/app/composer.lock" ]; then
    rm /var/www/app/composer.lock
fi

# Copy .env file if it doesn't exist
if [ ! -f "/var/www/app/.env" ]; then
    cp /var/www/app/.env.docker /var/www/app/.env
fi

# Install dependencies
cd /var/www/app && composer install --no-interaction

# Create and set up the database
if [ "$APP_ENV" = "test" ]; then
    echo "Setting up test environment..."
    # For SQLite, we just need to ensure the directory exists
    mkdir -p /var/www/app/var/data
    touch /var/www/app/var/data/test.db
    chmod 777 /var/www/app/var/data/test.db
    
    # Drop existing schema if it exists
    php bin/console doctrine:schema:drop --env=test --force --no-interaction || true
    
    # Create schema
    php bin/console doctrine:schema:create --env=test --no-interaction
else
    echo "Setting up development environment..."
    # Create database if it doesn't exist
    php bin/console doctrine:database:create --if-not-exists
    
    # Run migrations
    php bin/console doctrine:migrations:migrate --no-interaction
fi

# Clear cache
php bin/console cache:clear

# Start PHP-FPM
exec php-fpm 