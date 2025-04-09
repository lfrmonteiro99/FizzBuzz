#!/bin/bash

echo "Starting Symfony messenger consumer..."
docker-compose exec php bin/console messenger:consume async -vv 