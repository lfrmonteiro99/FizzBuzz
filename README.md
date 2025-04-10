# FizzBuzz API

A Symfony-based REST API that implements the FizzBuzz game with additional features like request tracking, statistics, and event handling.

## ⚠️ Important Notes ⚠️

### Project Structure
This project has a specific structure:
- The Symfony application exists **ONLY** in the `app/` directory
- Files in the root directory are for Docker and deployment configuration only
- All development should be done inside the `app/` directory
- Do NOT edit Symfony files that might exist in the root directory

### Environment Variables
**Before running this application, you MUST properly configure the environment variables!**

The application requires certain environment variables to be properly set:
- `MYSQL_ROOT_PASSWORD`
- `MYSQL_DATABASE`
- `MYSQL_USER`
- `MYSQL_PASSWORD`
- `APP_SECRET`
- `REDIS_PASSWORD` (if using password protection for Redis)

The good news is that you have two easy ways to set these variables:

1. **Using the improved start.sh script (recommended):**
   - Run `./start.sh` and it will check for missing variables
   - If variables are missing, it will prompt you to enter them
   - It automatically creates both the Docker `.env` and Symfony `.env` files

2. **Manually setting variables:**
   - Set these in your shell environment before running start.sh:
     ```bash
     export MYSQL_ROOT_PASSWORD=secure_root_password
     export MYSQL_DATABASE=symfony
     export MYSQL_USER=symfony_user
     export MYSQL_PASSWORD=symfony_password
     export APP_SECRET=your_secure_app_secret
     ```
   - Or edit `.env.docker` directly with your values
   
See the [Environment Variables](#environment-variables) section for more details.

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
   git clone https://github.com/lfrmonteiro99/FizzBuzz.git && cd FizzBuzz
   ```

2. Run the setup script:

   ```bash
   ./start.sh
   ```

   The script will:
   - Check for missing environment variables and prompt you to enter them
   - Create all necessary configuration files
   - Build and start the Docker containers
   - Install Composer dependencies
   - Create the database and run migrations
   - Set proper permissions for cache and log directories

3. That's it! Your application will be available at:

   ```
   http://localhost:8080
   ```

If you encounter any issues, see the [Troubleshooting](#troubleshooting) section.

## API Documentation

The API is documented using OpenAPI/Swagger. You can access the interactive API documentation at:

```
http://localhost:8080/api/doc
```

This provides:
- Interactive endpoint testing
- Request/response examples
- Schema definitions
- Authentication requirements
- Detailed parameter descriptions

The raw OpenAPI specification is available at:
```
http://localhost:8080/api/doc.json
```

You can also import this specification into tools like Postman or Insomnia for testing.

### API Endpoints

> 💡 **Tip**: For the most up-to-date API documentation, please visit the Swagger UI at `http://localhost:8080/api/doc`

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

## Environment Variables

The application uses environment variables for configuration. These are managed through `.env` files:

- `.env.docker.example` - Template file with example values
- `.env.docker` - Your local configuration (do not commit this file)

### Required Variables

| Variable | Description | Example |
|----------|-------------|---------|
| MYSQL_ROOT_PASSWORD | Root password for MySQL | `your-secure-password` |
| MYSQL_DATABASE | Database name | `fizzbuzz` |
| MYSQL_USER | Database user | `fizzbuzz_user` |
| MYSQL_PASSWORD | Database password | `your-secure-password` |
| APP_SECRET | Symfony application secret | `your-32-char-secret` |

### Setting Up Environment

1. Copy the example file:
   ```bash
   cp .env.docker.example .env.docker
   ```

2. Generate a secure APP_SECRET:
   ```bash
   openssl rand -hex 16
   ```

3. Update `.env.docker` with your values:
   - Use strong passwords for database credentials
   - Never commit `.env.docker` to version control
   - Keep your APP_SECRET secure and unique per environment

> 💡 **Note**: The `start.sh` script will guide you through setting required variables if they're missing.

### Optional Variables

| Variable | Description | Default |
|----------|-------------|---------|
| APP_ENV | Application environment | `dev` |
| APP_PORT | HTTP port for the application | `8080` |
| XDEBUG_MODE | PHP debugging configuration | `develop,debug` |
| MESSENGER_TRANSPORT_DSN | Message queue configuration | `doctrine://default` |

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

The application uses Monolog for comprehensive logging with different channels for various aspects of the application:

### Log Files

Log files are stored in the `app/var/log/` directory:

- `app/var/log/dev.log` - General application logs
- `app/var/log/request.log` - Request-specific logs
- `app/var/log/messenger.log` - Message queue processing logs
- `app/var/log/error.log` - Error logs

### Log Levels

Log levels can be configured through environment variables:

```bash
# Set log level (default: debug)
LOG_LEVEL=debug  # Options: debug, info, notice, warning, error, critical, alert, emergency

# Set Monolog level (default: debug)
MONOLOG_LEVEL=debug  # Options: debug, info, notice, warning, error, critical, alert, emergency
```

### Viewing Logs

To view logs in real-time:

```bash
# View all logs
docker-compose exec php tail -f var/log/dev.log

# View request logs
docker-compose exec php tail -f var/log/request.log

# View error logs
docker-compose exec php tail -f var/log/error.log

# View messenger logs
docker-compose exec php tail -f var/log/messenger.log
```

### Log Channels

The application uses different log channels for different purposes:

- `app` - General application logs
- `request` - Request-specific logs
- `messenger` - Message queue processing logs
- `error` - Error logs

Each channel can be configured independently in the Monolog configuration.

## Testing

The application uses PHPUnit for testing. To run the tests:

```bash
# Run all tests
docker-compose exec php sh -c "cd /var/www/app && bin/phpunit"

# Run specific test file
docker-compose exec php sh -c "cd /var/www/app && bin/phpunit tests/Controller/FizzBuzzControllerTest.php"

# Run tests with coverage report
docker-compose exec php sh -c "cd /var/www/app && bin/phpunit --coverage-html var/coverage"

# Run tests with specific filter
docker-compose exec php sh -c "cd /var/www/app && bin/phpunit --filter testGetFizzBuzzWithValidParameters"
```

### Test Environment

The test environment is automatically configured with:
- A separate test database
- Test-specific environment variables
- Proper permissions for test directories

### Test Structure

Tests are organized in the `app/tests/` directory:
- `Controller/` - API endpoint tests
- `Service/` - Business logic tests
- `Factory/` - Factory tests
- `Repository/` - Database interaction tests

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

## Troubleshooting

### Common Issues

1. **Permission errors in log or cache directories**
   - Run `./start.sh` with appropriate permissions
   - The script will request sudo access if needed to fix permissions

2. **Redis connection errors**
   - Check that the Redis password is correctly set in both .env files
   - Ensure that the Redis service is running: `docker-compose ps`

3. **Database connection issues**
   - Verify your DATABASE_URL matches your MYSQL_* variables
   - Wait a few seconds after initial startup for the database to initialize

### Viewing Logs

To see application logs:
```bash
docker-compose logs php
```

To see web server logs:
```bash
docker-compose logs nginx
```

To follow logs in real-time:
```bash
docker-compose logs -f php
```

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

## Message Queue Management

The application uses Redis as a message broker for asynchronous processing. Here's how to manage the message queue:

### Checking Queue Messages

To check messages in the queue:

```bash
# Connect to Redis CLI
docker-compose exec redis redis-cli

# List all streams
127.0.0.1:6379> XINFO STREAM messages

# Read messages from the stream
127.0.0.1:6379> XREAD COUNT 10 STREAMS messages 0

# Get stream information
127.0.0.1:6379> XINFO STREAM messages
```

### Managing the Consumer

The consumer is responsible for processing messages from the queue. By default, it runs in a separate container (`messenger-worker`), but you can also run it manually:

```bash
# Check if the consumer is running
docker-compose ps messenger-worker

# Start the consumer manually (if not running)
docker-compose exec php bin/console messenger:consume async

# Start the consumer with specific options
docker-compose exec php bin/console messenger:consume async \
    --time-limit=3600 \  # Run for 1 hour
    --memory-limit=512M \  # Memory limit
    --limit=1000  # Process 1000 messages

# Stop the consumer
docker-compose exec php bin/console messenger:stop-workers
```

### Troubleshooting

If messages are not being processed:

1. Check if the consumer is running:
   ```bash
   docker-compose ps messenger-worker
   ```

2. Check the consumer logs:
   ```bash
   docker-compose logs messenger-worker
   ```

3. Restart the consumer:
   ```bash
   docker-compose restart messenger-worker
   ```

4. If needed, clear the queue:
   ```bash
   docker-compose exec redis redis-cli DEL messages
   ```
