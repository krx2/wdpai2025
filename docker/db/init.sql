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

-- Projects table
CREATE TABLE IF NOT EXISTS projects (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(200) NOT NULL,
    subtitle VARCHAR(255),
    image_url VARCHAR(500),
    completion_date DATE,
    description TEXT,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_projects_user_id ON projects(user_id);

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
    '$2y$10$MEoMVVOq1UcvlXC6XqNVke6VaWmnEOHZgT1qeKyMTXnyRIfdiGLoK'
) ON CONFLICT (email) DO NOTHING;

-- Function to create default project for new users
CREATE OR REPLACE FUNCTION create_default_project()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO projects (user_id, title, subtitle, image_url, status)
    VALUES (
        NEW.id,
        'Twój pierwszy projekt',
        'Kliknij "Zarządzaj" żeby zapoznać się z funkcjonalnościami aplikacji',
        'https://images.unsplash.com/photo-1542621334-a254cf47733d?w=400&h=300&fit=crop',
        'active'
    );
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger to automatically create default project when user is created
DROP TRIGGER IF EXISTS trigger_create_default_project ON users;
CREATE TRIGGER trigger_create_default_project
    AFTER INSERT ON users
    FOR EACH ROW
    EXECUTE FUNCTION create_default_project();

-- Create default project for existing test user (if not exists)
INSERT INTO projects (user_id, title, subtitle, image_url, status)
SELECT 
    u.id,
    'Twój pierwszy projekt',
    'Kliknij "Zarządzaj" żeby zapoznać się z funkcjonalnościami aplikacji',
    'https://images.unsplash.com/photo-1542621334-a254cf47733d?w=400&h=300&fit=crop',
    'active'
FROM users u
WHERE u.email = 'jan.kowalski@example.com'
AND NOT EXISTS (
    SELECT 1 FROM projects p WHERE p.user_id = u.id
);