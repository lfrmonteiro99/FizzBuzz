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
DATABASE_URL="sqlite:///%kernel.project_dir%/var/test.db"
# Test logging - typically higher level to reduce noise
LOG_LEVEL=notice
EOF
fi

# Set directory permissions
echo "Setting proper permissions for Symfony directories"
mkdir -p /var/www/app/var/cache /var/www/app/var/log
chmod -R 777 /var/www/app/var

# Create log configuration file if it doesn't exist
if [ ! -f /var/www/app/config/packages/monolog.yaml ]; then
    echo "Creating monolog configuration for logging"
    mkdir -p /var/www/app/config/packages
    cat > /var/www/app/config/packages/monolog.yaml <<EOF
monolog:
    channels: ['app', 'request', 'security']
    handlers:
        main:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: "%env(LOG_LEVEL)%"
            channels: ["!event"]
        console:
            type: console
            process_psr_3_messages: false
            channels: ["!event", "!doctrine", "!console"]
        app:
            type: stream
            path: "%kernel.logs_dir%/app.log"
            level: debug
            channels: ["app"]

when@dev:
    monolog:
        handlers:
            main:
                type: stream
                path: "%kernel.logs_dir%/%kernel.environment%.log"
                level: debug
                channels: ["!event"]

when@test:
    monolog:
        handlers:
            main:
                type: fingers_crossed
                action_level: error
                handler: nested
                excluded_http_codes: [404, 405]
                channels: ["!event"]
            nested:
                type: stream
                path: "%kernel.logs_dir%/%kernel.environment%.log"
                level: debug

when@prod:
    monolog:
        handlers:
            main:
                type: fingers_crossed
                action_level: error
                handler: nested
                excluded_http_codes: [404, 405]
                buffer_size: 50
            nested:
                type: stream
                path: php://stderr
                level: debug
                formatter: monolog.formatter.json
EOF
    chown www-data:www-data /var/www/app/config/packages/monolog.yaml
fi

# Switch to www-data user for Symfony operations
if [ "$1" = "php-fpm" ]; then
    # Wait for MySQL to be ready
    echo "Waiting for MySQL..."
    until nc -z -v -w30 mysql 3306
    do
        echo "Waiting for MySQL connection..."
        sleep 2
    done
    echo "MySQL is up and running!"

    # Run as www-data user
    su www-data -s /bin/bash -c "cd /var/www/app && composer dump-autoload --optimize"
    
    # Run database migrations if in dev environment
    if [ "${APP_ENV}" = "dev" ]; then
        echo "Running database migrations..."
        su www-data -s /bin/bash -c "cd /var/www/app && php bin/console doctrine:database:create --if-not-exists"
        su www-data -s /bin/bash -c "cd /var/www/app && php bin/console doctrine:migrations:migrate --no-interaction || php bin/console doctrine:schema:update --force"
    fi
    
    # Clear cache
    echo "Clearing cache..."
    su www-data -s /bin/bash -c "cd /var/www/app && php bin/console cache:clear"
    
    # Create test database schema if needed
    echo "Setting up test environment..."
    su www-data -s /bin/bash -c "cd /var/www/app && php bin/console --env=test doctrine:database:create --if-not-exists"
    su www-data -s /bin/bash -c "cd /var/www/app && php bin/console --env=test doctrine:schema:update --force"
    
    # Create log rotation script
    echo "Creating log rotation script..."
    mkdir -p /var/www/app/bin
    cat > /var/www/app/bin/rotate-logs.sh <<EOF
#!/bin/bash
LOG_DIR="/var/www/app/var/log"
TIMESTAMP=\$(date +"%Y%m%d-%H%M%S")
RETENTION_DAYS=7

# Rotate logs
for logfile in \$LOG_DIR/*.log; do
  if [ -f "\$logfile" ] && [ -s "\$logfile" ]; then
    filename=\$(basename "\$logfile")
    cp "\$logfile" "\$LOG_DIR/\$filename.\$TIMESTAMP"
    cat /dev/null > "\$logfile"
    echo "[\$TIMESTAMP] Log file \$filename rotated" >> "\$LOG_DIR/rotation.log"
  fi
done

# Clean up old logs
find \$LOG_DIR -name "*.log.*" -type f -mtime +\$RETENTION_DAYS -delete
EOF
    chmod +x /var/www/app/bin/rotate-logs.sh
    
    echo "Symfony application ready!"
fi

# First arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
    set -- php-fpm "$@"
fi

exec "$@" 