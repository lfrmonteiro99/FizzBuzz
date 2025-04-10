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

# Function to install Symfony dependencies
install_symfony_dependencies() {
    echo "Installing Symfony dependencies..."
    
    # List of packages that we must ensure are installed
    required_packages=(
        "symfony/property-access"
        "symfony/validator"
        "symfony/twig-bundle"
        "nelmio/api-doc-bundle"
        "symfony/redis-messenger"
    )
    
    # Install each package if not already installed
    for package in "${required_packages[@]}"; do
        echo "Ensuring $package is installed..."
        docker-compose exec -T php composer require $package --no-interaction || echo "Failed to install $package, continuing anyway"
    done
}

# Function to initialize Redis for Messenger
initialize_redis_messenger() {
    echo "Initializing Redis Stream for Symfony Messenger..."
    
    # Extract the stream name from MESSENGER_TRANSPORT_DSN
    # Default to 'messages' if not found or can't be parsed
    stream_name="messages"
    if [ ! -z "$MESSENGER_TRANSPORT_DSN" ]; then
        # Try to extract the stream name from DSN
        extracted_name=$(echo "$MESSENGER_TRANSPORT_DSN" | grep -oP 'redis://[^/]+/\K[^?/]+' || echo "")
        if [ ! -z "$extracted_name" ]; then
            stream_name="$extracted_name"
        fi
    fi
    
    echo "Using Redis stream name: $stream_name"
    
    # Wait for Redis to be ready
    echo "Waiting for Redis to be ready..."
    max_attempts=10
    attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        echo "Checking Redis connectivity (attempt $attempt/$max_attempts)..."
        if docker-compose exec -T redis redis-cli PING | grep -q "PONG"; then
            echo "✅ Redis is ready!"
            break
        else
            if [ $attempt -eq $max_attempts ]; then
                echo "❌ Redis is not responding after $max_attempts attempts. Continuing anyway..."
            else
                echo "Redis not ready yet. Waiting 3 seconds..."
                sleep 3
                attempt=$((attempt + 1))
            fi
        fi
    done
    
    # Create the Redis stream and consumer group
    echo "Creating Redis stream '$stream_name' and consumer group 'fizzbuzz'..."
    docker-compose exec -T redis redis-cli XGROUP CREATE $stream_name fizzbuzz $ MKSTREAM || {
        # If the command fails, let's check if it's because the group already exists
        echo "Failed to create stream/group. It might already exist or there might be an error."
        echo "Trying to destroy and recreate to be sure..."
        
        # Try to destroy the group if it exists
        docker-compose exec -T redis redis-cli XGROUP DESTROY $stream_name fizzbuzz > /dev/null 2>&1
        
        # Create the stream and group again
        docker-compose exec -T redis redis-cli XGROUP CREATE $stream_name fizzbuzz $ MKSTREAM || {
            echo "❌ Failed to create Redis stream and consumer group. Messenger might not work correctly."
            echo "You can manually create it with: docker-compose exec redis redis-cli XGROUP CREATE $stream_name fizzbuzz $ MKSTREAM"
        }
    }
    
    # Verify the stream exists
    echo "Verifying Redis stream..."
    docker-compose exec -T redis redis-cli TYPE $stream_name | grep -q "stream" && {
        echo "✅ Redis stream '$stream_name' exists!"
    } || {
        echo "❌ Redis stream doesn't exist or is of wrong type!"
    }
    
    # Verify the consumer group exists
    echo "Verifying consumer group..."
    docker-compose exec -T redis redis-cli XINFO GROUPS $stream_name | grep -q "fizzbuzz" && {
        echo "✅ Consumer group 'fizzbuzz' exists!"
    } || {
        echo "❌ Consumer group 'fizzbuzz' doesn't exist!"
    }
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

# Give opportunity to edit environment files
echo ""
echo "Environment files have been prepared:"
echo "  - .env.docker and .env in root (for Docker)"
echo "  - app/.env (for Symfony)"
echo ""
read -p "Would you like to review/edit these files before continuing? (y/n): " edit_choice
if [[ $edit_choice =~ ^[Yy]$ ]]; then
    editor=${EDITOR:-nano}
    echo "Opening .env.docker with $editor..."
    $editor .env.docker
    echo "Updating .env from .env.docker..."
    cp .env.docker .env
    echo "Recreating app/.env with updated values..."
    source .env.docker
    create_symfony_env
fi

# Make sure var directory exists with proper permissions
echo "Setting file permissions..."
mkdir -p app/var/{cache,log}
echo "Using sudo to set permissions (may prompt for your password)..."
sudo chmod -R 777 app/var/ || {
    echo "Warning: Could not set permissions with sudo. Continuing anyway..."
}

# Confirm before starting Docker
echo ""
echo "Ready to start Docker containers."
read -p "Continue? (y/n): " start_choice
if [[ ! $start_choice =~ ^[Yy]$ ]]; then
    echo "Exiting without starting containers."
    exit 0
fi

# Clean and build containers without cache
echo "Stopping any running containers and removing volumes..."
docker-compose down -v

echo "Building Docker containers without cache..."
docker-compose build --no-cache

# Start Docker containers with force-recreate to ensure fresh start
echo "Starting Docker containers with fresh instances..."
docker-compose up -d --force-recreate

# Print MySQL config for debugging
echo ""
echo "*** MySQL Connection Details ***"
echo "Host: mysql (Docker service name)"
echo "Database: $MYSQL_DATABASE"
echo "User: $MYSQL_USER"
echo "Password: $MYSQL_PASSWORD"
echo "ROOT Password: $MYSQL_ROOT_PASSWORD"
echo ""

# Wait for containers to be fully up
echo "Waiting for containers to be ready..."
sleep 10

# Fix MySQL Access Denied issues (common problem)
echo "Checking MySQL user accounts and permissions..."
docker-compose exec -T mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "SELECT User, Host FROM mysql.user;" || {
    echo "Could not connect with root user - MySQL might still be initializing"
    echo "Waiting another 10 seconds..."
    sleep 10
}

# Attempt to fix MySQL access denied issues by recreating the user with proper permissions
echo "Attempting to fix MySQL user permissions..."
docker-compose exec -T mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "
CREATE USER IF NOT EXISTS '$MYSQL_USER'@'%' IDENTIFIED BY '$MYSQL_PASSWORD';
GRANT ALL PRIVILEGES ON *.* TO '$MYSQL_USER'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;
SHOW GRANTS FOR '$MYSQL_USER'@'%';
" || echo "Could not set MySQL permissions - will try to continue anyway"

# Wait for database to be ready
echo "Waiting for database to be ready..."
MAX_RETRIES=30
RETRY_COUNT=0

while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
    echo "Attempt $((RETRY_COUNT+1))/$MAX_RETRIES: Checking MySQL connection..."
    
    # Try to connect with root user first
    if docker-compose exec -T mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "SELECT 1;" > /dev/null 2>&1; then
        echo "✅ MySQL is reachable with root user!"
        
        # Try creating the database manually if it doesn't exist
        docker-compose exec -T mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS $MYSQL_DATABASE;"
        echo "✅ Ensured database $MYSQL_DATABASE exists!"
        
        break
    fi
    
    # If root fails, try the application user
    if docker-compose exec -T mysql mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" -e "SELECT 1;" > /dev/null 2>&1; then
        echo "✅ MySQL is reachable with application user!"
        break
    fi
    
    RETRY_COUNT=$((RETRY_COUNT+1))
    
    if [ $RETRY_COUNT -eq $MAX_RETRIES ]; then
        echo "❌ Maximum attempts reached. MySQL connection failed."
        echo "Debugging information:"
        echo "1. Checking MySQL container status:"
        docker-compose ps mysql
        echo ""
        echo "2. MySQL container logs:"
        docker-compose logs mysql
        
        read -p "MySQL connection failed. Try resetting and recreating MySQL container? (y/n): " reset_choice
        if [[ $reset_choice =~ ^[Yy]$ ]]; then
            echo "Recreating MySQL container with clean volume..."
            docker-compose rm -fsv mysql
            docker volume rm -f fizzbuzz_mysql_data
            docker-compose up -d mysql
            echo "Waiting for MySQL container to initialize..."
            sleep 20
        else
            read -p "Continue anyway? (y/n): " continue_choice
            if [[ ! $continue_choice =~ ^[Yy]$ ]]; then
                echo "Exiting due to MySQL connection failure."
                exit 1
            fi
        fi
    else
        echo "MySQL not ready yet. Waiting 3 seconds..."
        sleep 3
    fi
done

# Try to create database directly if it doesn't exist
echo "Ensuring database exists using root account..."
docker-compose exec -T mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS $MYSQL_DATABASE;"

# Grant permissions again to be sure
docker-compose exec -T mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "
GRANT ALL PRIVILEGES ON $MYSQL_DATABASE.* TO '$MYSQL_USER'@'%';
FLUSH PRIVILEGES;
"

# Initialize Redis Stream for Messenger
initialize_redis_messenger

# Check if Symfony connection works from within PHP container
echo "Testing Symfony database connection from PHP container..."
docker-compose exec -T php bin/console doctrine:schema:validate --skip-sync || {
    echo "⚠️ Warning: Symfony database connection test failed!"
    echo "This might be due to configuration issues or missing schema."
    echo "Let's check the DATABASE_URL in the PHP container:"
    docker-compose exec -T php bash -c 'echo "DATABASE_URL in container: $DATABASE_URL"'
    echo ""
    echo "Let's test direct MySQL connection from PHP:"
    docker-compose exec -T php bash -c "php -r \"try { new PDO('mysql:host=mysql;dbname=$MYSQL_DATABASE', '$MYSQL_USER', '$MYSQL_PASSWORD'); echo 'PDO Connection successful!\n'; } catch (\\PDOException \$e) { echo 'Connection failed: ' . \$e->getMessage() . \n'; }\""
}

# Initialize database
echo "Creating database if it doesn't exist..."
docker-compose exec -T php php bin/console doctrine:database:create --if-not-exists --no-interaction || {
    echo "❌ Failed to create database. This is likely due to MySQL connection issues."
    echo "Let's try one more approach - connecting directly to MySQL and creating database + user:"
    
    docker-compose exec -T mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "
    CREATE DATABASE IF NOT EXISTS $MYSQL_DATABASE;
    CREATE USER IF NOT EXISTS '$MYSQL_USER'@'%' IDENTIFIED BY '$MYSQL_PASSWORD';
    GRANT ALL PRIVILEGES ON $MYSQL_DATABASE.* TO '$MYSQL_USER'@'%';
    FLUSH PRIVILEGES;
    "
    
    echo "Retrying database commands..."
    docker-compose exec -T php php bin/console doctrine:database:create --if-not-exists --no-interaction || echo "Still failing - please check MySQL configuration manually"
}

echo "Running migrations inside app/ directory..."
docker-compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction || {
    echo "❌ Migrations failed. This is likely due to database connection issues."
    echo "Please check your database credentials in .env.docker and app/.env"
}

# Install additional Symfony dependencies
install_symfony_dependencies

echo "Loading fixtures..."
docker-compose exec -T php php bin/console doctrine:fixtures:load --no-interaction --append || {
    echo "❌ Fixtures failed to load."
}

echo "Setup complete! Your application is now running."
echo "Access the application at: http://localhost:${NGINX_PORT:-80}"
echo ""
echo "⚠️  Remember: All Symfony development should be done in the app/ directory!" 