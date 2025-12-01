-- Initialization script for PostgreSQL database
-- This script runs automatically when the database is first created

CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL DEFAULT '',
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    bio TEXT,
    enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Test user credentials:
-- Email: jan.kowalski@example.com
-- Password: test123
-- Hash generated with: password_hash('test123', PASSWORD_BCRYPT)
INSERT INTO users (firstname, lastname, email, bio, enabled, password)
VALUES (
    'Jan',
    'Kowalski',
    'jan.kowalski@example.com',
    'Lubi programować w JS i PL/SQL.',
    TRUE,
    '$2y$10$4Wls1KLwKJDs8A3zEcG8d.E8RLz9eT7sOZhPz6FqL6HQ2qVVLPdVS'
) ON CONFLICT (email) DO NOTHING;