#!/bin/bash
set -e  # Exit immediately if ANY command fails

# Fix permissions for Laravel
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Wait for database to be ready
echo "Waiting for database connection..."
MAX_TRIES=30
COUNT=0
until php artisan db:show 2>/dev/null; do
    COUNT=$((COUNT+1))
    if [ $COUNT -ge $MAX_TRIES ]; then
        echo "ERROR: Database connection timeout after 60 seconds"
        exit 1
    fi
    echo "Database not ready, retrying... ($COUNT/$MAX_TRIES)"
    sleep 2
done

echo "✓ Database connected!"

# Run migrations with verbose output
echo "Running migrations..."
if php artisan migrate --force --verbose; then
    echo "✓ Migrations completed successfully"
else
    echo "ERROR: Migration failed! Check the error above."
    exit 1
fi

# Optional: Cache optimization for production
if [ "$APP_ENV" = "production" ]; then
    echo "Optimizing for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    echo "✓ Production optimization complete"
fi

echo "========================================="
echo "Application is ready!"
echo "========================================="

# Execute the main command (php-fpm)
exec "$@"
