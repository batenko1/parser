#!/bin/bash

docker network create global_net || true

set -e

set -a
source .env
set +a


# Stop the containers if they're running
docker compose down || docker-compose down

# Build the Docker containers
docker compose build || docker-compose build

# Start the Docker containers in detached mode
docker compose up -d || docker compose up -d

echo "Installing Composer dependencies..."
docker exec -it app composer install \
  --no-interaction \
  --prefer-dist \
  --optimize-autoloader

# Run the database migrations
docker exec -it app php artisan migrate --force

docker exec -it app php artisan db:seed --force

echo "Clearing cache..."
docker exec -it app php artisan optimize:clear

docker exec app php artisan config:cache
docker exec app php artisan route:cache
