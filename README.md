# Symfony Challenge

## Prerequisites

- Docker and Docker Compose
- Git

## Installation for Development

1. Clone the repository:

   ```bash
   git clone https://github.com/yourusername/symfony_challenge.git
   cd symfony_challenge
   ```

2. Set up Docker environment variables:

   ```bash
   cp .env.docker.example .env.docker
   ```

3. Modify the environment variables in `.env.docker` if needed:

   ```
   # MySQL Configuration
   MYSQL_ROOT_PASSWORD=your_root_password
   MYSQL_DATABASE=your_database_name
   MYSQL_USER=your_username
   MYSQL_PASSWORD=your_password

   # Application Configuration
   APP_ENV=dev
   APP_PORT=8080
   NGINX_PORT=80
   APP_SECRET=your_app_secret # Optional - will be auto-generated if not provided
   ```

4. Start the Docker containers:

   ```bash
   # Option 1: Using the convenience script (recommended)
   ./start.sh

   # Option 2: Manually
   cp .env.docker .env
   docker-compose up -d
   ```

> **Note:** The Docker setup automatically:
>
> - Installs Composer dependencies
> - Creates `.env.local` and `.env.test.local` files with the correct configuration
> - Sets up proper permissions for cache and log directories
> - Generates a secure APP_SECRET if not provided
> - Creates database schema for both development and test environments
> - Optimizes the autoloader for production performance
> - Configures comprehensive logging with rotation capabilities

## Production Deployment

1. Set up production environment variables:

   ```bash
   cp .env.docker.prod.example .env.docker.prod
   ```

2. Edit `.env.docker.prod` with secure production values:

   ```
   # MySQL Configuration - USE STRONG PASSWORDS!
   MYSQL_ROOT_PASSWORD=secure_root_password
   MYSQL_DATABASE=symfony
   MYSQL_USER=symfony
   MYSQL_PASSWORD=secure_user_password

   # Application Configuration
   APP_ENV=prod
   APP_DEBUG=0
   APP_PORT=80
   NGINX_PORT=80
   APP_SECRET=generated_secure_secret  # Use: openssl rand -hex 16

   # Application Domain - For SSL configuration
   APP_DOMAIN=your-domain.com
   ```

3. Run the deployment script (requires root permissions for SSL setup):

   ```bash
   sudo ./deploy.sh
   ```

   The script will:

   - Set up SSL certificates using Let's Encrypt
   - Configure HTTPS
   - Start the production containers
   - Verify application health

4. Your application will be available at: `https://your-domain.com`

5. Monitor the application using:

   ```bash
   # View logs
   docker-compose -f docker-compose.prod.yml logs -f

   # Check application health
   curl https://your-domain.com/health/detailed
   ```

### Production Security Features

The production environment includes:

- HTTPS with SSL certificates auto-renewed by Let's Encrypt
- Secure headers (HSTS, Content-Security-Policy, XSS Protection)
- Database port not exposed to the outside world
- PHP running as non-root user
- OPcache enabled for performance
- Multi-stage Docker builds for smaller image size
- Container health checks for monitoring
- Database optimization for performance and security

## Running the Application

- The application will be available at: http://localhost:8080 (or the port you specified in APP_PORT)

## API Endpoints

### FizzBuzz Endpoint

- `GET /fizzbuzz?int1=3&int2=5&limit=100&str1=fizz&str2=buzz`
  - Parameters:
    - `int1`: First divisor
    - `int2`: Second divisor
    - `limit`: Upper limit of numbers to process
    - `str1`: String to return for numbers divisible by int1
    - `str2`: String to return for numbers divisible by int2

### Statistics Endpoint

- `GET /statistics`
  - Returns the most frequently used FizzBuzz request parameters

## Running Tests

### Executing Tests

Since the test environment is automatically configured, you can simply run:

```bash
docker-compose exec php bin/phpunit
```

For test coverage report:

```bash
docker-compose exec php bin/phpunit --coverage-text
```

## Development

### Adding New Dependencies

If you need to add new packages to your project:

```bash
docker-compose exec php composer require package-name
```

For development dependencies:

```bash
docker-compose exec php composer require --dev package-name
```

### Database Migrations

If you change entity structure, create and run migrations:

```bash
# Generate a migration
docker-compose exec php bin/console doctrine:migrations:diff

# Execute migrations
docker-compose exec php bin/console doctrine:migrations:migrate
```

### Clearing Cache

If you experience issues after configuration changes:

```bash
docker-compose exec php bin/console cache:clear
```

### Logging

The application is configured with comprehensive logging:

1. **Application Logs**: Located in `app/var/log/`

   - `dev.log` - All logs in development environment
   - `app.log` - Application-specific logs
   - `test.log` - Test environment logs

2. **Docker Logs**: View container logs with:

   ```bash
   # View PHP container logs
   docker-compose logs php

   # View Nginx logs
   docker-compose logs nginx

   # Follow logs in real-time
   docker-compose logs -f php
   ```

3. **Log Rotation**: A log rotation script is available to prevent logs from growing too large:
   ```bash
   docker-compose exec php /var/www/app/bin/rotate-logs.sh
   ```
4. **Logging Levels**: Set the logging level in `.env.docker`:
   ```
   LOG_LEVEL=debug # Options: debug, info, notice, warning, error, critical, alert, emergency
   ```

## Troubleshooting

### Database Connection Issues

If you have issues connecting to the database:

1. Ensure Docker containers are running: `docker-compose ps`
2. Check the environment variables are correctly set in `.env.docker`
3. Try restarting the containers: `docker-compose restart`

### WSL Users

If using Windows with WSL, make sure the project is located in the WSL filesystem, not the Windows filesystem, for better performance.

## Version Control & Security

### Files Excluded from Version Control

For security reasons, the following files are excluded from version control:

- `.env.docker` - Contains local Docker environment variables
- `.env.docker.prod` - Contains production Docker environment variables
- `.env` - Generated environment file
- `certbot/` - SSL certificates and Let's Encrypt data
- Various log files and data directories

Always create these files from the provided example templates:

```bash
# For development
cp .env.docker.example .env.docker

# For production
cp .env.docker.prod.example .env.docker.prod
```

Never commit sensitive information such as:

- Database passwords
- Application secrets
- SSL private keys
- API tokens

### Docker Ignore

The `.dockerignore` file prevents sensitive files from being included in Docker images, keeping them secure and smaller in size.
