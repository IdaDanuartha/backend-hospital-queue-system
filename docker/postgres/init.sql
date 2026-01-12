-- PostgreSQL initialization script for Hospital Queue System
-- This script runs when the container is first created

-- Enable UUID extension (useful for Laravel UUIDs)
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Enable pg_trgm for text search optimization
CREATE EXTENSION IF NOT EXISTS "pg_trgm";

-- Grant all privileges to the application user
-- (The database and user are already created by PostgreSQL image based on environment variables)