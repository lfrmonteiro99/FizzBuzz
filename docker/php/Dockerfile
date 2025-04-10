FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    default-mysql-client \
    libzip-dev \
    libssl-dev \
    pkg-config

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Redis extension
RUN pecl install -o -f redis \
    && rm -rf /tmp/pear \
    && docker-php-ext-enable redis

# Install Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Set up correct permissions and directories
RUN mkdir -p /var/www/app/var/cache /var/www/app/var/log \
    && chown -R www-data:www-data /var/www/app

# Create log directory for PHP
RUN mkdir -p /var/log/php && chown -R www-data:www-data /var/log/php

# Get latest Composer with environment configs
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MEMORY_LIMIT=-1
ENV COMPOSER_HOME=/tmp/composer

# Copy php.ini
COPY docker/php/php.ini /usr/local/etc/php/php.ini

# Set working directory
WORKDIR /var/www/app

# Create entrypoint script
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
cd /var/www/app\n\
\n\
echo "Setting up directories and permissions..."\n\
mkdir -p /var/www/app/var/cache /var/www/app/var/log\n\
chown -R www-data:www-data /var/www/app/var\n\
\n\
echo "Waiting for MySQL to be ready..."\n\
while ! mysqladmin ping -h"mysql" --silent; do\n\
    echo "Waiting for MySQL..."\n\
    sleep 1\n\
done\n\
echo "MySQL is up and running!"\n\
\n\
# Ensure composer dependencies are installed\n\
if [ -f "composer.json" ]; then\n\
    echo "Installing/updating Composer dependencies..."\n\
    composer install --no-interaction || echo "Composer install failed, continuing..."\n\
fi\n\
\n\
echo "Setting up development environment..."\n\
php bin/console doctrine:database:create --if-not-exists || echo "Database create failed, continuing..."\n\
php bin/console doctrine:migrations:migrate --no-interaction || echo "Migrations failed, continuing..."\n\
php bin/console cache:clear || echo "Cache clear failed, continuing..."\n\
\n\
exec "$@"\n\
' > /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
