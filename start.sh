#!/bin/bash

# Check if .env.docker exists
if [ ! -f .env.docker ]; then
    echo "Error: .env.docker file not found!"
    echo "Please create it by copying .env.docker.example to .env.docker and filling in your values."
    exit 1
fi

# Copy .env.docker to .env (Docker Compose automatically uses .env in the current directory)
echo "Copying environment variables from .env.docker to .env..."
cp .env.docker .env

# Run Docker Compose
echo "Starting Docker containers..."
docker-compose up -d

echo "Docker environment is now running!"
echo "You can access the application at: http://localhost:$(grep APP_PORT .env.docker | cut -d= -f2 || echo "8080")" 