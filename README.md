# FizzBuzz API

A Symfony-based REST API that implements the FizzBuzz game with additional features like request tracking, statistics, and event handling.

## Features

- FizzBuzz sequence generation with customizable parameters
- Request tracking and statistics
- Event-driven architecture
- Asynchronous event processing
- Comprehensive logging
- Input validation
- Docker-based development environment
- Redis caching
- MySQL database for request storage

## Prerequisites

- Docker and Docker Compose
- Git

## Setup

1. Clone the repository:

   ```bash
   git clone <repository-url>
   cd fizzbuzz
   ```

2. Create environment files:

   ```bash
   cp .env.docker.example .env.docker
   ```

3. Start the containers:

   ```bash
   ./start.sh
   ```

This will:
- Build and start the Docker containers
- Install Composer dependencies
- Create the database
- Run database migrations
- Clear the cache

## API Endpoints

### Home
- **GET** `/`
- Returns a simple status message
- Response: `{"status": "API is working"}`

### FizzBuzz
- **GET** `/fizzbuzz`
- Generates a FizzBuzz sequence based on provided parameters
- Query Parameters:
  - `divisor1`: First divisor (positive integer)
  - `divisor2`: Second divisor (positive integer)
  - `limit`: Upper limit of the sequence (positive integer)
  - `str1`: String to use for multiples of divisor1 (non-empty string)
  - `str2`: String to use for multiples of divisor2 (non-empty string)
- Response: JSON object containing the sequence and request parameters

Example:
```bash
curl "http://localhost:8080/fizzbuzz?divisor1=2&divisor2=7&limit=100&str1=ola&str2=adeus"
```

### Statistics
- **GET** `/statistics`
- Returns the most frequent FizzBuzz request
- Response: JSON object containing the most frequent request parameters and hit count

## Development

### Project Structure
```
app/
├── src/
│   ├── Controller/     # API endpoints
│   ├── Entity/         # Database entities
│   ├── Event/          # Event classes
│   ├── Interface/      # Service interfaces
│   ├── Message/        # Async message handlers
│   ├── Repository/     # Database repositories
│   ├── Service/        # Business logic
│   └── Dto/            # Data Transfer Objects
├── config/             # Configuration files
├── migrations/         # Database migrations
└── tests/             # Test files
```

### Services
- `FizzBuzzService`: Core FizzBuzz logic
- `FizzBuzzStatisticsService`: Request tracking and statistics
- `FizzBuzzRequestRepository`: Database operations
- `FizzBuzzEventHandler`: Event handling

### Events
- `FizzBuzzEvent::GENERATION_COMPLETED`: Fired when sequence is generated
- `FizzBuzzEvent::INVALID_INPUT`: Fired when invalid input is detected
- `FizzBuzzEvent::ZERO_DIVISORS`: Fired when zero divisors are detected

### Logging
Logs are stored in:
- `/var/log/php-fpm.log` (PHP logs)
- `/var/log/nginx/access.log` (Nginx access logs)
- `/var/log/nginx/error.log` (Nginx error logs)

## Testing

Run the test suite:
```bash
docker-compose exec php bin/phpunit
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

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

- `GET /fizzbuzz?divisor1=3&divisor2=5&limit=100&str1=fizz&str2=buzz`
  - Parameters:
    - `divisor1`: First divisor
    - `divisor2`: Second divisor
    - `limit`: Upper limit of numbers to process
    - `str1`: String to return for numbers divisible by divisor1
    - `str2`: String to return for numbers divisible by divisor2

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
