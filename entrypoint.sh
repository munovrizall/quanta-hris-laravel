#!/bin/sh

# Wait for MySQL to be ready
until mysqladmin ping -h"$DB_HOST" --silent; do
    echo "Waiting for MySQL..."
    sleep 2
done

# Run Laravel migrations
php artisan migrate --force

# Start PHP-FPM
php-fpm

This script waits for the MySQL service to be ready before running Laravel migrations and then starts the PHP-FPM service.