CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL DEFAULT '',
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    bio TEXT,
    enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Test user with password: test123
INSERT INTO users (firstname, lastname, email, bio, enabled, password)
VALUES (
    'Jan',
    'Kowalski',
    'jan.kowalski@example.com',
    'Lubi programować w JS i PL/SQL.',
    TRUE,
    '$2y$10$ZbzQrqD1vDhLJpYe/vzSbeDJHTUnVPCpwlXclkiFa8dO5gOAfg8tq'
);