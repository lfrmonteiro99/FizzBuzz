#!/bin/bash

# Production deployment script
set -e

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "Please run as root"
    exit 1
fi

# Check if .env.docker.prod exists
if [ ! -f .env.docker.prod ]; then
    echo "Error: .env.docker.prod file not found!"
    echo "Please create it by copying .env.docker.prod.example to .env.docker.prod and filling in your secure values."
    exit 1
fi

# Create necessary directories
echo "Creating directories..."
mkdir -p certbot/conf certbot/www docker/mysql

# Copy .env.docker.prod to .env
echo "Copying environment variables from .env.docker.prod to .env..."
cp .env.docker.prod .env

# Check if we need to initialize SSL
APP_DOMAIN=$(grep APP_DOMAIN .env.docker.prod | cut -d= -f2)
if [ ! -d "certbot/conf/live/$APP_DOMAIN" ]; then
    echo "Setting up SSL certificates for $APP_DOMAIN..."
    
    # Start nginx temporarily for certbot
    docker-compose -f docker-compose.prod.yml up -d nginx
    sleep 5
    
    # Get SSL certificate
    docker-compose -f docker-compose.prod.yml run --rm certbot certonly --webroot \
        --webroot-path=/var/www/certbot \
        --email admin@${APP_DOMAIN} \
        --agree-tos \
        --no-eff-email \
        -d ${APP_DOMAIN}
    
    # Stop nginx
    docker-compose -f docker-compose.prod.yml stop nginx
fi

# Run Docker Compose
echo "Starting production Docker environment..."
docker-compose -f docker-compose.prod.yml up -d

echo "Waiting for the application to initialize..."
sleep 15

# Check if application is healthy
HEALTH_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health)
if [ "$HEALTH_STATUS" == "200" ]; then
    echo "✅ Application is running and healthy!"
    echo "🌎 Your application is available at: https://$APP_DOMAIN"
else
    echo "⚠️ Application may not be fully initialized. Please check the logs:"
    echo "docker-compose -f docker-compose.prod.yml logs"
fi

echo ""
echo "Useful commands:"
echo "- View logs: docker-compose -f docker-compose.prod.yml logs -f"
echo "- Stop application: docker-compose -f docker-compose.prod.yml down"
echo "- Update application: git pull && docker-compose -f docker-compose.prod.yml up -d --build"
echo "- Detailed health check: curl http://localhost/health/detailed"
echo ""
echo "🚀 Deployment completed!" 