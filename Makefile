# Makefile

.PHONY: help

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# ==============================================================================
# LOCAL DEVELOPMENT
# ==============================================================================

.PHONY: help install setup build up down restart logs clean migrate seed fresh test

help: ## Show this help message
	@echo "Hospital Queue API - Available Commands:"
	@echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

install: ## Install application (first time setup)
	@echo "🚀 Installing Hospital Queue API..."
	@echo "📦 Step 1/7: Creating required directories..."
	@mkdir -p docker/nginx/conf.d
	@mkdir -p docker/php
	@echo "✅ Directories created"
	@echo ""
	@echo "📦 Step 2/7: Installing composer dependencies locally..."
	@composer install --ignore-platform-reqs --no-interaction || echo "⚠️  Local composer install failed, will install in container"
	@echo "✅ Dependencies check complete"
	@echo ""
	@echo "📋 Step 3/7: Setting up environment file..."
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo "✅ .env file created from .env.example"; \
	else \
		echo "⚠️  .env file already exists, skipping copy"; \
	fi
	@echo ""
	@echo "🐳 Step 4/7: Building and starting Docker containers..."
	@docker-compose up -d --build
	@echo "✅ Containers started"
	@echo ""
	@echo "⏳ Step 5/7: Waiting for database to be ready..."
	@timeout=60; while ! docker-compose exec -T postgres pg_isready -U hospital_user -d hospital_queue > /dev/null 2>&1; do \
		timeout=$$((timeout - 1)); \
		if [ $$timeout -le 0 ]; then \
			echo "❌ Database failed to start in time"; \
			exit 1; \
		fi; \
		echo "Waiting for database... ($$timeout seconds remaining)"; \
		sleep 1; \
	done
	@echo "✅ Database is ready!"
	@echo ""
	@echo "📦 Step 6/7: Installing composer dependencies in container..."
	@docker-compose exec app composer install --no-interaction
	@echo "✅ Container dependencies installed"
	@echo ""
	@echo "🔑 Step 7/7: Generating application keys..."
	@docker-compose exec app php artisan key:generate
	@docker-compose exec app php artisan jwt:secret
	@echo ""
	@echo "🗄️  Step 8/7: Running migrations and seeders..."
	@docker-compose exec app php artisan migrate --force
	@docker-compose exec app php artisan db:seed --force
	@echo ""
	@echo "🔒 Setting permissions..."
	@docker-compose exec -u root app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
	@docker-compose exec -u root app chmod -R 775 /var/www/storage /var/www/bootstrap/cache
	@echo "✅ Permissions set"
	@echo ""
	@echo "✅ Installation complete!"
	@echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
	@echo "📍 Application URLs:"
	@echo "   API: http://localhost:8000"
	@echo "   API Docs: http://localhost:8000/docs/api"
	@echo "   pgAdmin: http://localhost:5050"
	@echo "      Email: admin@mail.com"
	@echo "      Password: admin123"
	@echo ""
	@echo "🗄️  Database Info:"
	@echo "   Host: localhost:5432 (or 'postgres' inside containers)"
	@echo "   Database: hospital_queue"
	@echo "   Username: hospital_user"
	@echo "   Password: hospital_pass"
	@echo ""
	@echo "📦 Redis Info:"
	@echo "   Host: localhost:6379 (or 'redis' inside containers)"
	@echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

up: ## Start all containers
	docker-compose up -d

down: ## Stop all containers
	docker-compose down

restart: ## Restart all containers
	docker-compose restart

rebuild: ## Rebuild and restart containers
	docker-compose down
	docker-compose up -d --build --force-recreate

ps: ## Show container status
	docker-compose ps

logs: ## Show all logs
	docker-compose logs -f

logs-app: ## Show app logs
	docker-compose logs -f app

logs-nginx: ## Show nginx logs
	docker-compose logs -f nginx

logs-db: ## Show database logs
	docker-compose logs -f postgres

shell: ## Access app container shell
	docker-compose exec app bash

shell-db: ## Access database shell
	docker-compose exec postgres psql -U hospital_user -d hospital_queue

clean: ## Remove all containers, volumes, and images
	docker-compose down -v
	docker system prune -f

# ==============================================================================
# LARAVEL COMMANDS
# ==============================================================================

migrate: ## Run database migrations
	docker-compose exec app php artisan migrate

migrate-fresh: ## Fresh migration (drops all tables)
	docker-compose exec app php artisan migrate:fresh --seed

migrate-rollback: ## Rollback last migration
	docker-compose exec app php artisan migrate:rollback

seed: ## Run database seeders
	docker-compose exec app php artisan db:seed

composer-install: ## Install composer dependencies
	docker-compose exec app composer install

composer-update: ## Update composer dependencies
	docker-compose exec app composer update

optimize: ## Optimize application (cache config, routes, views)
	docker-compose exec app php artisan optimize

clear-cache: ## Clear all caches
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

test: ## Run tests
	docker-compose exec app php artisan test

test-coverage: ## Run tests with coverage
	docker-compose exec app php artisan test --coverage

phpstan: ## Run PHPStan static analysis
	docker-compose exec app vendor/bin/phpstan analyse

fix-code: ## Fix code style with PHP CS Fixer
	docker-compose exec app vendor/bin/php-cs-fixer fix

# ==============================================================================
# DATABASE MANAGEMENT
# ==============================================================================

db-backup: ## Backup database
	@echo "📦 Creating database backup..."
	docker-compose exec -T postgres pg_dump -U hospital_user hospital_queue > backups/backup_$$(date +%Y%m%d_%H%M%S).sql
	@echo "✅ Backup created in backups/ directory"

db-restore: ## Restore database (Usage: make db-restore FILE=backup_20250107.sql)
	@echo "📥 Restoring database from $(FILE)..."
	docker-compose exec -T postgres psql -U hospital_user hospital_queue < backups/$(FILE)
	@echo "✅ Database restored"

db-reset: ## Reset database (fresh migration + seed)
	docker-compose exec app php artisan migrate:fresh --seed

# ==============================================================================
# PRODUCTION DEPLOYMENT
# ==============================================================================

prod-build: ## Build production Docker image
	docker build -f Dockerfile.prod -t hospital-queue-api:latest .

prod-up: ## Start production containers
	docker-compose -f docker-compose.prod.yml up -d

prod-down: ## Stop production containers
	docker-compose -f docker-compose.prod.yml down

prod-logs: ## Show production logs
	docker-compose -f docker-compose.prod.yml logs -f

prod-migrate: ## Run production migrations
	docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

prod-optimize: ## Optimize production application
	docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
	docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
	docker-compose -f docker-compose.prod.yml exec app php artisan view:cache
	docker-compose -f docker-compose.prod.yml exec app php artisan optimize

prod-backup: ## Backup production database
	docker-compose -f docker-compose.prod.yml exec -T backup /backup.sh

prod-deploy: ## Full production deployment
	@echo "🚀 Starting production deployment..."
	git pull origin main
	docker-compose -f docker-compose.prod.yml build app
	docker-compose -f docker-compose.prod.yml exec -T backup /backup.sh
	docker-compose -f docker-compose.prod.yml up -d --no-deps --build app
	sleep 10
	docker-compose -f docker-compose.prod.yml exec -T app php artisan migrate --force
	docker-compose -f docker-compose.prod.yml exec -T app php artisan optimize
	docker-compose -f docker-compose.prod.yml exec nginx nginx -s reload
	@echo "✅ Production deployment complete!"

# ==============================================================================
# MONITORING & HEALTH
# ==============================================================================

health: ## Check application health
	@echo "🏥 Checking API health..."
	@curl -f http://localhost/api/v1/health || echo "❌ API is not responding"
	@echo "\n🗄️  Checking database health..."
	@docker-compose exec postgres pg_isready || echo "❌ Database is not responding"
	@echo "\n🔴 Checking Redis health..."
	@docker-compose exec redis redis-cli ping || echo "❌ Redis is not responding"

stats: ## Show container resource usage
	docker stats --no-stream

# ==============================================================================
# DEVELOPMENT HELPERS
# ==============================================================================

create-controller: ## Create new controller (Usage: make create-controller NAME=TestController)
	docker-compose exec app php artisan make:controller $(NAME)

create-model: ## Create new model (Usage: make create-model NAME=Test)
	docker-compose exec app php artisan make:model $(NAME) -m

create-migration: ## Create new migration (Usage: make create-migration NAME=create_tests_table)
	docker-compose exec app php artisan make:migration $(NAME)

create-seeder: ## Create new seeder (Usage: make create-seeder NAME=TestSeeder)
	docker-compose exec app php artisan make:seeder $(NAME)

create-request: ## Create new form request (Usage: make create-request NAME=TestRequest)
	docker-compose exec app php artisan make:request $(NAME)

create-resource: ## Create new resource (Usage: make create-resource NAME=TestResource)
	docker-compose exec app php artisan make:resource $(NAME)

# ==============================================================================
# UTILITY
# ==============================================================================

check-env: ## Check environment configuration
	@echo "📋 Environment Configuration:"
	@docker-compose exec app php artisan about

generate-key: ## Generate new application key
	docker-compose exec app php artisan key:generate

generate-jwt: ## Generate new JWT secret
	docker-compose exec app php artisan jwt:secret

fix-permissions: ## Fix storage permissions
	docker-compose exec app chmod -R 775 storage bootstrap/cache
	docker-compose exec app chown -R www-data:www-data storage bootstrap/cache

update-deps: ## Update all dependencies
	docker-compose exec app composer update
	@echo "✅ Dependencies updated. Don't forget to test!"

push-github: ## Push changes to GitHub
	git push origin main
	git checkout production
	git pull origin production
	git merge main
	git push origin production
	git checkout main