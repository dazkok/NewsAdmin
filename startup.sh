#!/bin/bash
set -e

echo "Starting application setup..."

# Check if vendor directory exists, if not install dependencies
if [ ! -d "vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-dev --optimize-autoloader
fi

echo "Running database migrations..."
php migrate.php

echo "Starting Apache server..."
exec apache2-foreground