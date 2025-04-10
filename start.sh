#!/bin/bash

set -e

# Project structure information
echo "FizzBuzz API Setup"
echo "==================="
echo "Note: Symfony application exists only in the app/ directory"
echo ""

# Function to check if environment variables are set
check_env_vars() {
    local missing_vars=()
    
    # Check if .env.docker exists
    if [ -f .env.docker ]; then
        echo "Using existing .env.docker file"
        source .env.docker
    else
        echo "Creating .env.docker from example file"
        
        if [ -f .env.docker.example ]; then
            cp .env.docker.example .env.docker
            source .env.docker
        else
            echo "Error: .env.docker.example not found!"
            return 1
        fi
    fi
    
    # Check required variables
    [ -z "$MYSQL_ROOT_PASSWORD" ] && missing_vars+=("MYSQL_ROOT_PASSWORD")
    [ -z "$MYSQL_DATABASE" ] && missing_vars+=("MYSQL_DATABASE")
    [ -z "$MYSQL_USER" ] && missing_vars+=("MYSQL_USER")
    [ -z "$MYSQL_PASSWORD" ] && missing_vars+=("MYSQL_PASSWORD")
    [ -z "$APP_SECRET" ] && missing_vars+=("APP_SECRET")
    
    # If there are missing variables
    if [ ${#missing_vars[@]} -gt 0 ]; then
        echo "Required environment variables are missing:"
        for var in "${missing_vars[@]}"; do
            echo "  - $var"
        done
        
        read -p "Set these variables now? (y/n): " choice
        if [[ $choice =~ ^[Yy]$ ]]; then
            for var in "${missing_vars[@]}"; do
                read -p "Enter value for $var: " value
                echo "$var=$value" >> .env.docker
            done
            echo "Environment variables updated"
        else
            read -p "Continue with missing variables? (y/n): " continue_anyway
            if [[ ! $continue_anyway =~ ^[Yy]$ ]]; then
                echo "Setup canceled"
                return 1
            fi
        fi
    fi
    
    return 0
}

# Function to create Symfony .env file
create_symfony_env() {
    echo "Creating Symfony environment file"
    
    # Create properly formatted Symfony .env file
    cat > app/.env << EOF
###> symfony/framework-bundle ###
APP_ENV=${APP_ENV:-dev}
APP_SECRET=${APP_SECRET}
APP_VERSION=${APP_VERSION:-1.0.0}
###< symfony/framework-bundle ###

###> doctrine/doctrine-bundle ###
# Format described at https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html#connecting-using-a-url
# IMPORTANT: You MUST configure your server version, either here or in config/packages/doctrine.yaml
DATABASE_URL=mysql://${MYSQL_USER}:${MYSQL_PASSWORD}@mysql:3306/${MYSQL_DATABASE}?serverVersion=8.0.33&charset=utf8mb4
###< doctrine/doctrine-bundle ###

###> symfony/messenger ###
# Choose one of the transports below
MESSENGER_TRANSPORT_DSN=${MESSENGER_TRANSPORT_DSN:-redis://redis:6379/messages}
###< symfony/messenger ###

###> symfony/monolog-bundle ###
MONOLOG_LEVEL=${MONOLOG_LEVEL:-debug}
LOG_LEVEL=${LOG_LEVEL:-debug}
MONOLOG_CHANNEL=${MONOLOG_CHANNEL:-app}
###< symfony/monolog-bundle ###

###> Redis configuration ###
REDIS_URL=${REDIS_URL:-redis://redis:6379}
REDIS_HOST=${REDIS_HOST:-redis}
REDIS_PORT=${REDIS_PORT:-6379}
REDIS_PASSWORD=${REDIS_PASSWORD:-}
REDIS_DB=${REDIS_DB:-0}
###< Redis configuration ###
EOF

    # Set up test environment if example file exists
    if [ -f app/.env.test.example ]; then
        echo "Setting up test environment"
        cp app/.env.test.example app/.env.test
    fi
}

# Function to install Symfony dependencies
install_symfony_dependencies() {
    echo "Installing required Symfony packages"
    
    required_packages=(
        "symfony/property-access"
        "symfony/validator"
        "symfony/twig-bundle"
        "nelmio/api-doc-bundle"
        "symfony/redis-messenger"
    )
    
    for package in "${required_packages[@]}"; do
        docker-compose exec -T php composer require $package --no-interaction || echo "Note: $package installation skipped"
    done
}

# Function to initialize Redis for Messenger
initialize_redis_messenger() {
    echo "Setting up Redis for Symfony Messenger"
    
    # Extract stream name from MESSENGER_TRANSPORT_DSN or use default
    stream_name="messages"
    if [ ! -z "$MESSENGER_TRANSPORT_DSN" ]; then
        extracted_name=$(echo "$MESSENGER_TRANSPORT_DSN" | grep -oP 'redis://[^/]+/\K[^?/]+' || echo "")
        if [ ! -z "$extracted_name" ]; then
            stream_name="$extracted_name"
        fi
    fi
    
    # Wait for Redis to be ready
    echo "Waiting for Redis..."
    max_attempts=10
    attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        if docker-compose exec -T redis redis-cli PING | grep -q "PONG"; then
            break
        else
            if [ $attempt -eq $max_attempts ]; then
                echo "Warning: Redis connectivity issues"
            else
                sleep 2
                attempt=$((attempt + 1))
            fi
        fi
    done
    
    # Create the Redis stream and consumer group
    docker-compose exec -T redis redis-cli XGROUP CREATE $stream_name fizzbuzz $ MKSTREAM || {
        # Try to destroy and recreate
        docker-compose exec -T redis redis-cli XGROUP DESTROY $stream_name fizzbuzz > /dev/null 2>&1
        docker-compose exec -T redis redis-cli XGROUP CREATE $stream_name fizzbuzz $ MKSTREAM || {
            echo "Note: Manual Redis setup might be required"
        }
    }
}

# Function to set up test environment
setup_test_env() {
    echo "Setting up testing environment"
    
    # Ensure test directories exist
    docker-compose exec -T php mkdir -p var/data
    
    # Set up test database
    docker-compose exec -T php bin/console doctrine:database:create --env=test --if-not-exists --no-interaction || {
        echo "Note: Test database already exists or could not be created"
    }
    
    docker-compose exec -T php bin/console doctrine:schema:create --env=test --no-interaction || {
        # Try dropping schema first if it fails
        docker-compose exec -T php bin/console doctrine:schema:drop --env=test --force --no-interaction
        docker-compose exec -T php bin/console doctrine:schema:create --env=test --no-interaction
    }
    
    # Fix permissions
    docker-compose exec -T php chmod -R 777 var/data
    
    echo "✅ Test environment ready"
}

# Main script execution
echo "Starting FizzBuzz setup"

# Check environment variables
if ! check_env_vars; then
    exit 1
fi

# Copy .env.docker to root .env for Docker Compose
echo "Preparing environment files"
cp .env.docker .env
create_symfony_env

# Give opportunity to edit environment files
echo ""
read -p "Review environment files before continuing? (y/n): " edit_choice
if [[ $edit_choice =~ ^[Yy]$ ]]; then
    editor=${EDITOR:-nano}
    $editor .env.docker
    cp .env.docker .env
    source .env.docker
    create_symfony_env
fi

# Set file permissions
echo "Setting file permissions"
mkdir -p app/var/{cache,log}
sudo chmod -R 777 app/var/ || echo "Warning: Permission setup may require manual intervention"

# Confirm before starting Docker
echo ""
read -p "Start Docker containers? (y/n): " start_choice
if [[ ! $start_choice =~ ^[Yy]$ ]]; then
    echo "Setup canceled"
    exit 0
fi

# Clean and start containers
echo "Setting up Docker environment"
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d --force-recreate

# Wait for containers to be ready
sleep 10

# Fix MySQL permissions
docker-compose exec -T mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "
CREATE USER IF NOT EXISTS '$MYSQL_USER'@'%' IDENTIFIED BY '$MYSQL_PASSWORD';
GRANT ALL PRIVILEGES ON *.* TO '$MYSQL_USER'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;" 2>/dev/null || echo "Note: MySQL might still be initializing"

# Wait for database to be ready
echo "Waiting for MySQL"
MAX_RETRIES=15
RETRY_COUNT=0

while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
    if docker-compose exec -T mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "SELECT 1;" > /dev/null 2>&1; then
        # Create database
        docker-compose exec -T mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS $MYSQL_DATABASE;"
        break
    fi
    
    if [ $RETRY_COUNT -eq $MAX_RETRIES ]; then
        echo "Warning: MySQL connection issues"
        read -p "Reset MySQL container? (y/n): " reset_choice
        if [[ $reset_choice =~ ^[Yy]$ ]]; then
            docker-compose rm -fsv mysql
            docker volume rm -f fizzbuzz_mysql_data
            docker-compose up -d mysql
            sleep 20
        else
            read -p "Continue anyway? (y/n): " continue_choice
            if [[ ! $continue_choice =~ ^[Yy]$ ]]; then
                echo "Setup canceled"
                exit 1
            fi
        fi
    else
        sleep 2
        RETRY_COUNT=$((RETRY_COUNT+1))
    fi
done

# Ensure database access
docker-compose exec -T mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "
GRANT ALL PRIVILEGES ON $MYSQL_DATABASE.* TO '$MYSQL_USER'@'%';
FLUSH PRIVILEGES;" 2>/dev/null || echo "Note: Database privileges setup skipped"

# Initialize Redis Stream for Messenger
initialize_redis_messenger

# Database setup
echo "Setting up database"
docker-compose exec -T php php bin/console doctrine:database:create --if-not-exists --no-interaction || {
    # Retry with direct MySQL command
    docker-compose exec -T mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "
    CREATE DATABASE IF NOT EXISTS $MYSQL_DATABASE;
    GRANT ALL PRIVILEGES ON $MYSQL_DATABASE.* TO '$MYSQL_USER'@'%';
    FLUSH PRIVILEGES;"
}

echo "Running database migrations"
docker-compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction || echo "Note: Migration issues detected"

# Install additional Symfony dependencies
install_symfony_dependencies

echo "Loading fixture data"
docker-compose exec -T php php bin/console doctrine:fixtures:load --no-interaction --append || echo "Note: Fixture loading skipped"

# Ask if user wants to set up test environment
read -p "Set up test environment? (y/n): " setup_test
if [[ $setup_test =~ ^[Yy]$ ]]; then
    setup_test_env
fi

echo ""
echo "✅ Setup complete"
echo "Access the application at: http://localhost:${NGINX_PORT:-80}" 