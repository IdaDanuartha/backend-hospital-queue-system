set -e

echo "🚀 Hospital Queue API - Production Deployment"
echo "=============================================="

# Configuration
APP_DIR="/var/www/hospital-queue-api"
BACKUP_DIR="$APP_DIR/backups"
DATE=$(date +%Y%m%d_%H%M%S)

cd $APP_DIR

# Pull latest code
echo "📥 Pulling latest code from repository..."
git pull origin main

# Backup database before deployment
echo "💾 Creating database backup..."
docker-compose -f docker-compose.prod.yml exec -T backup /backup.sh

# Build new Docker image
echo "🔨 Building new Docker image..."
docker-compose -f docker-compose.prod.yml build app

# Deploy with zero downtime
echo "🚢 Deploying new version..."
docker-compose -f docker-compose.prod.yml up -d --no-deps --build app

# Wait for application to be ready
echo "⏳ Waiting for application to be ready..."
sleep 15

# Run database migrations
echo "🗄️  Running database migrations..."
docker-compose -f docker-compose.prod.yml exec -T app php artisan migrate --force

# Optimize application
echo "⚡ Optimizing application..."
docker-compose -f docker-compose.prod.yml exec -T app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec -T app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec -T app php artisan view:cache
docker-compose -f docker-compose.prod.yml exec -T app php artisan optimize

# Graceful reload nginx
echo "🔄 Reloading nginx..."
docker-compose -f docker-compose.prod.yml exec nginx nginx -s reload

# Health check
echo "🏥 Running health check..."
sleep 5
HEALTH_CHECK=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/v1/health)

if [ "$HEALTH_CHECK" == "200" ]; then
    echo "✅ Deployment completed successfully!"
    echo "📊 Health check: PASSED"
else
    echo "⚠️  Deployment completed but health check failed!"
    echo "📊 Health check: FAILED (HTTP $HEALTH_CHECK)"
    echo "Please check the logs: docker-compose -f docker-compose.prod.yml logs -f app"
fi

# Show deployment info
echo ""
echo "📋 Deployment Information:"
echo "   Date: $DATE"
echo "   Commit: $(git rev-parse --short HEAD)"
echo "   Branch: $(git rev-parse --abbrev-ref HEAD)"
echo ""
echo "📍 Application is running at:"
echo "   https://api.yourdomain.com"