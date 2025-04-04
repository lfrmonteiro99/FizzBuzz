FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git zip unzip curl libpq-dev libonig-dev libxml2-dev libzip-dev \
    netcat-openbsd \
    && docker-php-ext-install pdo pdo_mysql zip

# Install Xdebug for code coverage
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Configure Xdebug for code coverage
RUN echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set up directory for app
WORKDIR /var/www/app

# Create cache, log, and test directories with proper permissions
RUN mkdir -p /var/www/app/var/cache /var/www/app/var/log \
    && chown -R www-data:www-data /var/www/app \
    && chmod -R 777 /var/www/app/var

# Copy composer files first to leverage Docker cache
COPY app/composer.json app/composer.lock* /var/www/app/

# Set proper permissions before running composer
RUN chown -R www-data:www-data /var/www/app

# Switch to www-data user for composer operations
USER www-data

# Install dependencies
RUN composer install --prefer-dist --no-scripts --no-progress --no-interaction

# Switch back to root for entrypoint
USER root

# Set up custom entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]
