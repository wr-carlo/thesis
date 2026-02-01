#!/bin/sh
set -e

echo "🚀 Starting application..."

# Wait for database to be ready (if DATABASE_URL is set)
if [ -n "$DATABASE_URL" ] || [ -n "$DB_HOST" ]; then
    echo "⏳ Waiting for database connection..."
    
    # Extract host and port from DATABASE_URL or use DB_HOST/DB_PORT
    if [ -n "$DATABASE_URL" ]; then
        # Parse DATABASE_URL (mysql://user:pass@host:port/dbname)
        DB_HOST_PARSED=$(echo $DATABASE_URL | sed -e 's|.*@\(.*\):.*|\1|' -e 's|/.*||')
        DB_PORT_PARSED=$(echo $DATABASE_URL | sed -e 's|.*:\([0-9]*\)/.*|\1|')
    else
        DB_HOST_PARSED=${DB_HOST:-127.0.0.1}
        DB_PORT_PARSED=${DB_PORT:-3306}
    fi
    
    # Wait up to 30 seconds for database
    TRIES=0
    MAX_TRIES=30
    until nc -z "$DB_HOST_PARSED" "$DB_PORT_PARSED" 2>/dev/null || [ $TRIES -eq $MAX_TRIES ]; do
        echo "   Waiting for database at $DB_HOST_PARSED:$DB_PORT_PARSED... ($TRIES/$MAX_TRIES)"
        TRIES=$((TRIES + 1))
        sleep 1
    done
    
    if [ $TRIES -eq $MAX_TRIES ]; then
        echo "⚠️  Database connection timeout, proceeding anyway..."
    else
        echo "✅ Database is ready!"
    fi
fi

# Run database migrations
if [ "$RUN_MIGRATIONS" = "true" ] || [ "$RUN_MIGRATIONS" = "1" ]; then
    echo "📦 Running database migrations..."
    php artisan migrate --force
fi

# Clear and rebuild caches for production
echo "🔧 Optimizing for production..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link if it doesn't exist
if [ ! -L "public/storage" ]; then
    echo "🔗 Creating storage link..."
    php artisan storage:link 2>/dev/null || true
fi

echo "✨ Application ready!"

# Execute the main command
exec "$@"
