#!/bin/bash

# Check if .env.docker exists
if [ ! -f .env.docker ]; then
    echo "Error: .env.docker file not found!"
    echo "Please create it by copying .env.docker.example to .env.docker and filling in your values."
    exit 1
fi

# Copy .env.docker to .env (Docker Compose automatically uses .env in the current directory)
echo "Copying environment variables from .env.docker to .env..."
cp .env.docker .env

# Create .env file in app directory too
echo "Creating .env file in app directory..."
cp .env.docker app/.env

# Delete composer.lock (don't try to delete vendor directory directly)
echo "Removing composer.lock to ensure clean installation..."
if [ -f app/composer.lock ]; then
    rm app/composer.lock
    echo "composer.lock deleted"
fi

# Fix Symfony version conflicts in composer.json
echo "Fixing Symfony version conflicts in composer.json..."
# Update the symfony extra version requirement to match the framework-bundle
sed -i 's/"require": "7\.2\.\*"/"require": "6\.4\.\*"/g' app/composer.json
# Fix the dev dependencies to use the same version
sed -i 's/"symfony\/browser-kit": "7\.2\.\*"/"symfony\/browser-kit": "6\.4\.\*"/g' app/composer.json
sed -i 's/"symfony\/css-selector": "7\.2\.\*"/"symfony\/css-selector": "6\.4\.\*"/g' app/composer.json
sed -i 's/"symfony\/debug-bundle": "7\.2\.\*"/"symfony\/debug-bundle": "6\.4\.\*"/g' app/composer.json
sed -i 's/"symfony\/phpunit-bridge": "\^7\.2"/"symfony\/phpunit-bridge": "\^6\.4"/g' app/composer.json
sed -i 's/"symfony\/var-dumper": "7\.2\.\*"/"symfony\/var-dumper": "6\.4\.\*"/g' app/composer.json

# Add the validator and twig bundle directly to composer.json
echo "Adding required packages to composer.json..."
if ! grep -q "\"symfony/validator\"" app/composer.json; then
    sed -i '/"symfony\/yaml"/i \        "symfony\/validator": "6.4.*",' app/composer.json
    echo "Added symfony/validator to composer.json"
fi
if ! grep -q "\"symfony/twig-bundle\"" app/composer.json; then
    sed -i '/"symfony\/validator"/i \        "symfony\/twig-bundle": "6.4.*",' app/composer.json
    echo "Added symfony/twig-bundle to composer.json"
fi
if ! grep -q "\"nelmio/api-doc-bundle\"" app/composer.json; then
    sed -i '/"symfony\/twig-bundle"/i \        "nelmio\/api-doc-bundle": "^5.0",' app/composer.json
    echo "Added nelmio/api-doc-bundle to composer.json"
fi
if ! grep -q "\"symfony/redis-messenger\"" app/composer.json; then
    sed -i '/"symfony\/messenger"/i \        "symfony\/redis-messenger": "6.4.*",' app/composer.json
    echo "Added symfony/redis-messenger to composer.json"
fi

echo "Updated composer.json to use Symfony 6.4.* and added required packages"

# Rebuild containers and force a clean start
echo "Rebuilding containers from scratch..."
docker-compose down
docker-compose build --no-cache

# Run Docker Compose
echo "Starting Docker containers..."
docker-compose up -d

# Wait for containers to fully start
echo "Waiting for containers to fully start..."
sleep 15

# Initialize Redis Stream for Messenger 
echo "Initializing Redis Stream for Messenger..."
docker-compose exec -T redis bash -c '
# Create the messages stream if it doesn't exist
redis-cli XGROUP CREATE messages fizzbuzz $ MKSTREAM || echo "Stream already exists or group already created"
'

# Install dependencies properly
echo "Installing dependencies (this may take a few minutes)..."
docker-compose exec -T php bash -c '
cd /var/www/app

# Make sure directory is clean
echo "Cleaning vendor directory..."
rm -rf vendor

# Install dependencies step by step
echo "Installing Composer dependencies..."
composer install --no-interaction --optimize-autoloader

# Install key packages separately to ensure they are available
echo "Ensuring key packages are installed..."
composer require symfony/validator --no-interaction
composer require symfony/twig-bundle --no-interaction
composer require nelmio/api-doc-bundle --no-interaction
composer require symfony/redis-messenger --no-interaction

# Set up database
echo "Setting up database..."
echo "Creating database..."
php bin/console doctrine:database:create --if-not-exists --no-interaction || echo "Database creation failed, but continuing..."
echo "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction || echo "Migrations failed, but continuing..."

# Fix cache directory permissions and structure
echo "Setting up cache directory structure..."
mkdir -p var/cache
rm -rf var/cache/*
chmod -R 777 var/cache
mkdir -p var/cache/local
chmod -R 777 var/cache/local

# Clear cache
echo "Clearing cache..."
APP_ENV=local php bin/console cache:clear --no-warmup || echo "Cache clear failed, but continuing..."
'

echo "Docker environment is now running!"
echo "You can access the application at: http://localhost:$(grep APP_PORT .env.docker | cut -d= -f2 || echo "8080")" 