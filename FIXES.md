# Database Record Storage Fixes

## Problem

The FizzBuzz application was experiencing issues with storing records in the database due to several factors:

1. **Doctrine Cache Configuration Error**: The `metadata_cache` cache pool was configured to use the `array` type, which is deprecated.
2. **Messenger Queue Issues**: The asynchronous message processing wasn't reliably storing records due to setup issues with the messenger-worker container.
3. **Composer Dependencies**: Composer was failing to extract packages due to memory limitations.
4. **SQL Query Parameter Issues**: The SQL query in the `CreateFizzBuzzRequestHandler` had incorrect parameter references for the `ON DUPLICATE KEY UPDATE` clause.

## Fixes Implemented

### 1. Doctrine Cache Configuration

Updated the cache configuration in `app/config/packages/doctrine.yaml` to use pool-based cache:

```yaml
when@test:
    doctrine:
        orm:
            metadata_cache_driver:
                type: pool
                pool: doctrine.system_cache_pool
            query_cache_driver:
                type: pool
                pool: doctrine.system_cache_pool
            result_cache_driver:
                type: pool
                pool: doctrine.result_cache_pool
```

### 2. Direct Database Insertion

- Modified `FizzBuzzService` to directly insert records into the database using DBAL in addition to dispatching async messages.
- Fixed the SQL query in `CreateFizzBuzzRequestHandler` to correctly reference parameters in the `ON DUPLICATE KEY UPDATE` clause.

```php
// Direct database insertion with proper parameter binding
$sql = "INSERT INTO fizz_buzz_requests
        (start, limit_value, divisor1, divisor2, str1, str2, hits, version, tracking_state, created_at, updated_at, processed_at)
        VALUES
        (:start, :limit, :divisor1, :divisor2, :str1, :str2, 1, 1, 'processed', :now, :now, :now)
        ON DUPLICATE KEY UPDATE
        hits = hits + 1,
        updated_at = :now,
        processed_at = :now,
        version = version + 1";
```

### 3. Docker Environment Improvements

- Added Composer cache volume for better dependency management.
- Configured higher memory limits for PHP and Composer:

```yaml
volumes:
  mysql_data:
  redis_data:
  composer_cache:  # Added for persistent composer cache
```

```dockerfile
# Increase memory for Composer
ENV COMPOSER_MEMORY_LIMIT=-1
ENV COMPOSER_ALLOW_SUPERUSER=1
```

- Improved container dependencies using healthchecks:

```yaml
depends_on:
  mysql:
    condition: service_healthy
  redis:
    condition: service_healthy
```

### 4. Error Handling

- Enhanced error logging in both the FizzBuzzService and CreateFizzBuzzRequestHandler.
- Added graceful error handling to ensure service continuity even when parts of the system fail.

### 5. Testing Tools

- Created a standalone PHP script (`direct_insert.php`) for directly testing database connectivity and insertions.

## How To Verify The Fix

1. Restart the Docker environment:

```bash
docker-compose down && docker-compose up -d
```

2. Make a FizzBuzz request:

```bash
curl "http://localhost:8080/fizzbuzz?divisor1=3&divisor2=5&limit=15&str1=fizz&str2=buzz"
```

3. Check the database for records:

```bash
docker-compose exec mysql mysql -uroot -psymfony symfony -e "SELECT * FROM fizz_buzz_requests"
```

4. You can also run the direct insert script to test:

```bash
docker-compose exec php php /var/www/app/direct_insert.php
``` 