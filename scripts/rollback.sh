set -e

echo "🔄 Hospital Queue API - Rollback Script"
echo "========================================"

# Check if backup file is provided
if [ -z "$1" ]; then
    echo "❌ Error: Backup file not specified"
    echo "Usage: ./rollback.sh <backup_file>"
    echo "Available backups:"
    ls -lh /var/www/hospital-queue-api/backups/*.sql.gz | tail -5
    exit 1
fi

BACKUP_FILE=$1
APP_DIR="/var/www/hospital-queue-api"

cd $APP_DIR

echo "⚠️  WARNING: This will restore the database to a previous state"
echo "Backup file: $BACKUP_FILE"
read -p "Are you sure you want to continue? (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo "❌ Rollback cancelled"
    exit 0
fi

# Stop application (keep database running)
echo "⏸️  Stopping application..."
docker-compose -f docker-compose.prod.yml stop app nginx

# Restore database
echo "💾 Restoring database..."
gunzip < $BACKUP_FILE | docker-compose -f docker-compose.prod.yml exec -T postgres psql -U hospital_user_prod hospital_queue_prod

# Start application
echo "▶️  Starting application..."
docker-compose -f docker-compose.prod.yml start app nginx

# Wait and health check
echo "⏳ Waiting for application..."
sleep 10

HEALTH_CHECK=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/v1/health)

if [ "$HEALTH_CHECK" == "200" ]; then
    echo "✅ Rollback completed successfully!"
else
    echo "⚠️  Rollback completed but health check failed!"
    echo "Please check the logs"
fi