set -e

echo "🏥 Hospital Queue Management System - Setup Script"
echo "=================================================="

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

echo "✅ Docker and Docker Compose are installed"

# Copy environment file if not exists
if [ ! -f .env ]; then
    echo "📝 Creating .env file..."
    cp .env.example .env
else
    echo "⚠️  .env file already exists, skipping..."
fi

# Create required directories
echo "📁 Creating required directories..."
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache
mkdir -p backups

# Start Docker containers
echo "🐳 Starting Docker containers..."
docker-compose up -d --build

# Wait for database to be ready
echo "⏳ Waiting for database to be ready..."
sleep 10

# Install dependencies
echo "📦 Installing Composer dependencies..."
docker-compose exec app composer install

# Generate application key
echo "🔑 Generating application key..."
docker-compose exec app php artisan key:generate

# Generate JWT secret
echo "🔐 Generating JWT secret..."
docker-compose exec app php artisan jwt:secret

# Run migrations
echo "🗄️  Running database migrations..."
docker-compose exec app php artisan migrate

# Seed database
echo "🌱 Seeding database..."
docker-compose exec app php artisan db:seed

# Clear and cache config
echo "🔄 Optimizing application..."
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache

echo ""
echo "✅ Setup completed successfully!"
echo ""
echo "📍 Access points:"
echo "   - API: http://localhost:8000"
echo "   - API Docs: http://localhost:8000/docs/api"
echo "   - pgAdmin: http://localhost:5050"
echo ""
echo "🔐 Default credentials:"
echo "   Admin - username: admin, password: password123"
echo "   Staff - username: staff_umum, password: password123"
echo ""
echo "📚 Useful commands:"
echo "   - View logs: docker-compose logs -f app"
echo "   - Stop containers: docker-compose down"
echo "   - Restart: docker-compose restart"
echo ""
echo "Or use 'make help' for more commands"