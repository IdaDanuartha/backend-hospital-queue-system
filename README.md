# 🏥 Hospital Queue Management System API

RESTful API untuk sistem antrian rumah sakit dengan fitur real-time monitoring, geofencing, dan reporting yang komprehensif.

## 📋 Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [API Documentation](#api-documentation)
- [Architecture](#architecture)
- [Testing](#testing)
- [Deployment](#deployment)

## ✨ Features

### 👤 Customer (Pasien)
- ✅ Ambil nomor antrian tanpa login
- ✅ Monitoring status antrian real-time
- ✅ Estimasi waktu tunggu
- ✅ Informasi jadwal dokter & poli
- ✅ Geofencing (opsional)

### 👨‍⚕️ Staff
- ✅ Dashboard antrian per poli
- ✅ Panggil antrian berikutnya
- ✅ Skip antrian (pasien tidak hadir)
- ✅ Recall antrian
- ✅ Update status pelayanan
- ✅ Audit trail semua aksi

### 👨‍💼 Admin
- ✅ Dashboard monitoring seluruh poli
- ✅ CRUD Master Data (Poli, Dokter, Jadwal, Staff)
- ✅ Manajemen jenis antrian
- ✅ Laporan & statistik lengkap
- ✅ User management

## 🛠 Tech Stack

- **Framework**: Laravel 12
- **Database**: PostgreSQL 15+
- **Authentication**: JWT (tymon/jwt-auth)
- **API Documentation**: Scramble
- **Architecture**: Repository Pattern + Service Layer
- **PHP Version**: 8.2+

## 📦 Installation

### Prerequisites

```bash
# Check requirements
php -v    # Should be 8.2+
composer --version
psql --version
```

### Step 1: Clone & Install Dependencies

```bash
# Clone repository
git clone https://github.com/IdaDanuartha/backend-hospital-queue-system.git
cd backend-hospital-queue-system

# Install dependencies
composer install
```

### Step 2: Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret
```

### Step 3: Database Configuration

Edit `.env` file:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=hospital_queue
DB_USERNAME=your_username
DB_PASSWORD=your_password

JWT_SECRET=your_secret_key
JWT_TTL=60
JWT_REFRESH_TTL=20160
```

### Step 4: Run Migrations & Seeders

```bash
# Create database (if not exists)
createdb hospital_queue

# Run migrations
php artisan migrate

# Seed initial data
php artisan db:seed
```

### Step 5: Start Development Server

```bash
php artisan serve
```

API akan berjalan di: `http://localhost:8000`

## ⚙️ Configuration

### JWT Configuration

```env
JWT_TTL=60              # Access token lifetime (minutes)
JWT_REFRESH_TTL=20160   # Refresh token lifetime (minutes)
JWT_ALGO=HS256          # Algorithm
```

### Geofencing Configuration

Update di database table `system_settings`:

```sql
UPDATE system_settings SET value = 'true' WHERE key = 'GEOFENCE_ENABLED';
UPDATE system_settings SET value = '100' WHERE key = 'MAX_DISTANCE_METER';
UPDATE system_settings SET value = '-8.670458' WHERE key = 'HOSPITAL_LAT';
UPDATE system_settings SET value = '115.212629' WHERE key = 'HOSPITAL_LNG';
```

### Rate Limiting

Konfigurasi di `app/Providers/RouteServiceProvider.php`:

- General API: 60 requests/minute
- Queue Taking: 5 requests/minute
- Authentication: 10 requests/minute

## 🗄 Database Setup

### Schema Overview

**Core Tables:**
- `users` - User authentication
- `admins` - Admin profiles
- `staff` - Staff profiles
- `polys` - Polyclinics
- `doctors` - Doctor data
- `doctor_schedules` - Doctor schedules
- `queue_types` - Queue/service types
- `queue_tickets` - Main queue data
- `queue_events` - Audit trail
- `poly_service_hours` - Service hours
- `system_settings` - System configuration
- `public_queue_tokens` - Public access tokens

### Default Credentials

```
Admin:
Username: admin
Password: 123456

Staff:
Username: staff_umum
Password: 123456
```

## 📚 API Documentation

### Access Documentation

Setelah setup, akses dokumentasi API di:
```
http://localhost:8000/docs/api
```

### Quick Start Examples

#### 1. Login

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "123456"
  }'
```

#### 2. Take Queue (Public)

```bash
curl -X POST http://localhost:8000/api/v1/customer/queue/take \
  -H "Content-Type: application/json" \
  -d '{
    "queue_type_id": 1,
    "latitude": -8.670458,
    "longitude": 115.212629
  }'
```

#### 3. Check Queue Status (Public)

```bash
curl -X GET http://localhost:8000/api/v1/customer/queue/status/{token}
```

#### 4. Staff Call Next Queue (Protected)

```bash
curl -X POST http://localhost:8000/api/v1/staff/queue/call-next \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "queue_type_id": 1
  }'
```

### Endpoints Summary

| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| POST | `/auth/login` | Public | User login |
| POST | `/auth/refresh` | All | Refresh token |
| GET | `/auth/me` | All | Get user profile |
| POST | `/customer/queue/take` | Public | Take queue number |
| GET | `/customer/queue/status/{token}` | Public | Check queue status |
| GET | `/customer/info/polys` | Public | Get polyclinics |
| GET | `/customer/info/doctors` | Public | Get doctor schedules |
| GET | `/staff/dashboard` | Staff | Staff dashboard |
| POST | `/staff/queue/call-next` | Staff | Call next queue |
| POST | `/staff/queue/{id}/skip` | Staff | Skip queue |
| GET | `/admin/dashboard` | Admin | Admin dashboard |
| GET | `/admin/polys` | Admin | List polyclinics |
| GET | `/admin/reports/statistics` | Admin | Queue statistics |

*Lihat dokumentasi lengkap di `/docs/api`*

## 🏗 Architecture

### Repository Pattern

```
Controller → Service → Repository → Model → Database
```

**Benefits:**
- Separation of concerns
- Easier testing
- Code reusability
- Maintainability

### Key Components

```
app/
├── Http/
│   ├── Controllers/     # Handle HTTP requests
│   ├── Middleware/      # Auth & role checks
│   └── Requests/        # Form validation
├── Models/              # Eloquent models
├── Repositories/        # Data access layer
│   ├── Contracts/       # Interfaces
│   └── Eloquent/        # Implementations
├── Services/            # Business logic
└── Enums/               # Status enums
```

### Service Layer Example

```php
// QueueService handles all queue business logic
public function takeQueue($queueTypeId, $lat, $lng)
{
    // 1. Validate geofencing
    // 2. Get next queue number (with DB transaction)
    // 3. Create queue ticket
    // 4. Generate public token
    // 5. Return result
}
```

## 🧪 Testing

### Run Tests

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter=QueueServiceTest

# Generate coverage report
php artisan test --coverage
```

### Test Structure

```
tests/
├── Feature/
│   ├── Auth/
│   ├── Customer/
│   ├── Staff/
│   └── Admin/
└── Unit/
    ├── Services/
    └── Repositories/
```

## 🚀 Deployment

### Production Setup

1. **Environment Variables**

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com

# Use strong secrets in production
JWT_SECRET=your_production_secret
```

2. **Optimize Performance**

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

3. **Database Migration**

```bash
# Production migration
php artisan migrate --force
```

4. **Queue Workers**

```bash
# For background jobs
php artisan queue:work
```

## 🔒 Security Features

- ✅ JWT Authentication with refresh tokens
- ✅ Role-based access control (RBAC)
- ✅ Rate limiting on sensitive endpoints
- ✅ Database transactions for critical operations
- ✅ Input validation & sanitization
- ✅ CORS configuration
- ✅ SQL injection prevention (Eloquent ORM)
- ✅ Password hashing (bcrypt)
- ✅ Audit trail (queue_events table)

## 🐛 Troubleshooting

### Common Issues

**1. JWT Token Invalid**
```bash
# Regenerate JWT secret
php artisan jwt:secret --force
```

**2. Database Connection Failed**
```bash
# Check PostgreSQL is running
sudo systemctl status postgresql

# Test connection
psql -U postgres -d hospital_queue
```

**3. Migrations Failed**
```bash
# Reset database
php artisan migrate:fresh --seed
```

**4. Permission Denied**
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
```

## 📞 Support

Untuk pertanyaan atau issue, silakan hubungi:
- Email: support@hospital.com
- GitHub Issues: [Create Issue](https://github.com/your-repo/issues)

## 📄 License

This project is licensed under the MIT License.

## 👥 Credits

Developed by: **Ida Danuartha**

---

**Version:** 1.0.0  
**Last Updated:** January 2026