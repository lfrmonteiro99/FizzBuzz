#!/bin/bash

set -e

# Warning about Symfony file structure
echo "⚠️  Warning: This project has a specific structure!"
echo "  - The Symfony application should be ONLY in the 'app/' directory"
echo "  - Files in the root directory are for Docker and deployment configuration only"
echo "  - Do not work with Symfony files in the root directory"
echo ""

# Function to check if environment variables are set
check_env_vars() {
    local missing_vars=()
    
    # Check if .env.docker exists
    if [ -f .env.docker ]; then
        echo "Found .env.docker file, sourcing variables..."
        source .env.docker
    else
        echo "WARNING: .env.docker file not found!"
        echo "Creating it from .env.docker.example..."
        
        if [ -f .env.docker.example ]; then
            cp .env.docker.example .env.docker
            echo "Created .env.docker from example file. Please review and update values as needed."
            source .env.docker
        else
            echo "ERROR: .env.docker.example not found!"
            echo "Cannot create configuration files."
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
        echo "The following required environment variables are missing or empty:"
        for var in "${missing_vars[@]}"; do
            echo "  - $var"
        done
        
        read -p "Would you like to set these variables now? (y/n): " choice
        if [[ $choice =~ ^[Yy]$ ]]; then
            for var in "${missing_vars[@]}"; do
                read -p "Enter value for $var: " value
                echo "$var=$value" >> .env.docker
            done
            echo "Updated .env.docker with new values."
        else
            echo "Warning: Continuing with missing environment variables may cause issues!"
            read -p "Do you want to continue anyway? (y/n): " continue_anyway
            if [[ ! $continue_anyway =~ ^[Yy]$ ]]; then
                echo "Exiting..."
                return 1
            fi
        fi
    fi
    
    return 0
}

# Function to create Symfony .env file
create_symfony_env() {
    echo "Creating Symfony .env file in app/ directory..."
    
    # Create properly formatted Symfony .env file
    cat > app/.env << EOF
###> symfony/framework-bundle ###
APP_ENV=${APP_ENV:-dev}
APP_SECRET=${APP_SECRET}
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
    
    echo "Symfony .env file created successfully."
}

# Main script execution
echo "Starting FizzBuzz application setup..."

# Check for duplicate Symfony files in root
if [ -f "composer.json" ] && [ -f "app/composer.json" ]; then
    echo "⚠️  Warning: Found duplicate composer.json files in root and app/ directories."
    echo "    The correct location for composer.json is ONLY in the app/ directory."
    echo "    Files in the root should only be for Docker configuration."
fi

# Check and process environment variables
if ! check_env_vars; then
    exit 1
fi

# Copy .env.docker to root .env for Docker Compose
echo "Copying .env.docker to .env for Docker Compose..."
cp .env.docker .env

# Create properly formatted Symfony .env
create_symfony_env

# Make sure var directory exists with proper permissions
echo "Setting file permissions..."
mkdir -p app/var/{cache,log}
chmod -R 777 app/var/

# Start Docker containers
echo "Starting Docker containers with docker-compose..."
docker-compose up -d

# Wait for database to be ready
echo "Waiting for database to be ready..."
docker-compose exec -T php /bin/bash -c "
    set -e
    echo 'Waiting for MySQL connection...'
    until php -r \"mysqli_connect('mysql', getenv('MYSQL_USER'), getenv('MYSQL_PASSWORD'), getenv('MYSQL_DATABASE'));\" > /dev/null 2>&1; do
        echo 'MySQL connection unavailable - sleeping'
        sleep 1
    done
    echo 'MySQL connection established'
"

# Initialize database
echo "Running migrations inside app/ directory..."
docker-compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction || echo "Migrations failed, but continuing..."
echo "Loading fixtures..."
docker-compose exec -T php php bin/console doctrine:fixtures:load --no-interaction --append || echo "Fixtures failed, but continuing..."

echo "Setup complete! Your application is now running."
echo "Access the application at: http://localhost:${NGINX_PORT:-80}"
echo ""
echo "⚠️  Remember: All Symfony development should be done in the app/ directory!" 