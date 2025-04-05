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

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/app

# Copy composer files first to leverage Docker cache
COPY app/composer.json /var/www/app/

# Install dependencies
RUN if [ -f "composer.lock" ]; then \
        composer install --no-interaction --no-dev --optimize-autoloader; \
    else \
        composer update --no-interaction --no-dev --optimize-autoloader; \
    fi

# Copy the rest of the application
COPY app/ /var/www/app/

# Set proper permissions for Symfony
RUN chown -R www-data:www-data /var/www/app/var
RUN chown -R www-data:www-data /var/www/app/public

# Configure PHP-FPM
RUN echo "pm.max_children = 50" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.start_servers = 5" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.min_spare_servers = 5" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_spare_servers = 35" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_requests = 500" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "request_terminate_timeout = 300" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "max_input_time = 300" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Copy entrypoint script
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
