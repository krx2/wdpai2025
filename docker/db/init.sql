-- ================================================================
-- DATABASE INITIALIZATION SCRIPT
-- ================================================================
-- Autor: krx
-- Data: 2026-02-06
-- Opis: Kompletny schemat bazy danych z danymi testowymi
-- ================================================================

-- ================================================================
-- 1. TABELA UŻYTKOWNIKÓW
-- ================================================================
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL DEFAULT '',
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    bio TEXT,
    avatar_url VARCHAR(500),
    enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);

-- ================================================================
-- 2. TABELA PROJEKTÓW
-- ================================================================
CREATE TABLE IF NOT EXISTS projects (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(200) NOT NULL,
    subtitle VARCHAR(255),
    description TEXT,
    image_url VARCHAR(500),
    
    -- Daty projektu
    start_date DATE,
    completion_date DATE,
    
    -- Status będzie uzupełniany przez ostatni wpis w historii statusów
    current_status_id INTEGER,
    
    -- Metadane
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_projects_user_id ON projects(user_id);
CREATE INDEX IF NOT EXISTS idx_projects_status ON projects(status);
CREATE INDEX IF NOT EXISTS idx_projects_current_status_id ON projects(current_status_id);

-- ================================================================
-- 3. TABELA CZŁONKÓW PROJEKTU
-- ================================================================
CREATE TABLE IF NOT EXISTS project_members (
    id SERIAL PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role VARCHAR(50) DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(project_id, user_id)
);

CREATE INDEX IF NOT EXISTS idx_project_members_project ON project_members(project_id);
CREATE INDEX IF NOT EXISTS idx_project_members_user ON project_members(user_id);

-- ================================================================
-- 4. TABELA STATUSÓW PROJEKTÓW
-- ================================================================
CREATE TABLE IF NOT EXISTS user_project_statuses (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#CCCCCC',
    description TEXT,
    is_final BOOLEAN DEFAULT FALSE,
    usage_count INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(user_id, name)
);

CREATE INDEX IF NOT EXISTS idx_user_statuses_user_id ON user_project_statuses(user_id);
CREATE INDEX IF NOT EXISTS idx_user_statuses_usage ON user_project_statuses(usage_count DESC);

-- ================================================================
-- 5. TABELA HISTORII STATUSÓW
-- ================================================================
CREATE TABLE IF NOT EXISTS project_status_history (
    id SERIAL PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    status_id INTEGER NOT NULL REFERENCES user_project_statuses(id) ON DELETE RESTRICT,
    
    deadline_date DATE,
    actual_change_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    changed_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    notes TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_status_history_project ON project_status_history(project_id);
CREATE INDEX IF NOT EXISTS idx_status_history_status ON project_status_history(status_id);
CREATE INDEX IF NOT EXISTS idx_status_history_date ON project_status_history(actual_change_date DESC);

-- ================================================================
-- 6. TABELA LOGOWANIA CZASU
-- ================================================================
CREATE TABLE IF NOT EXISTS time_logs (
    id SERIAL PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    
    log_date DATE NOT NULL DEFAULT CURRENT_DATE,
    hours DECIMAL(5,2) NOT NULL CHECK (hours > 0 AND hours <= 24),
    
    description TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- NOWY: Użytkownik może mieć tylko jeden wpis dziennie dla danego projektu
    UNIQUE(user_id, project_id, log_date)
);

CREATE INDEX IF NOT EXISTS idx_time_logs_project ON time_logs(project_id);
CREATE INDEX IF NOT EXISTS idx_time_logs_user ON time_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_time_logs_date ON time_logs(log_date DESC);
CREATE INDEX IF NOT EXISTS idx_time_logs_project_user_date ON time_logs(project_id, user_id, log_date);

-- ================================================================
-- 7. DODANIE FOREIGN KEY DO PROJECTS
-- ================================================================
ALTER TABLE projects 
    DROP CONSTRAINT IF EXISTS fk_projects_current_status;

ALTER TABLE projects 
    ADD CONSTRAINT fk_projects_current_status 
    FOREIGN KEY (current_status_id) 
    REFERENCES user_project_statuses(id) 
    ON DELETE SET NULL;

-- ================================================================
-- 8. WIDOKI POMOCNICZE
-- ================================================================

-- Widok: Sugestie statusów dla użytkownika
CREATE OR REPLACE VIEW user_status_suggestions AS
SELECT 
    ups.user_id,
    ups.id as status_id,
    ups.name,
    ups.color,
    ups.usage_count,
    ups.is_final
FROM user_project_statuses ups
WHERE ups.usage_count > 0
ORDER BY ups.user_id, ups.usage_count DESC, ups.name ASC;

-- Widok: Aktualny status projektu
CREATE OR REPLACE VIEW project_current_status AS
SELECT DISTINCT ON (psh.project_id)
    psh.project_id,
    psh.id as history_id,
    psh.status_id,
    ups.name as status_name,
    ups.color as status_color,
    ups.is_final,
    psh.deadline_date,
    psh.actual_change_date,
    psh.changed_by,
    psh.notes
FROM project_status_history psh
JOIN user_project_statuses ups ON psh.status_id = ups.id
ORDER BY psh.project_id, psh.actual_change_date DESC;

-- Widok: Przepracowane godziny per projekt
CREATE OR REPLACE VIEW project_total_hours AS
SELECT 
    tl.project_id,
    SUM(tl.hours) as total_hours,
    COUNT(DISTINCT tl.user_id) as contributors_count,
    MIN(tl.log_date) as first_log_date,
    MAX(tl.log_date) as last_log_date
FROM time_logs tl
GROUP BY tl.project_id;

-- Widok: Przepracowane godziny per użytkownik per projekt
CREATE OR REPLACE VIEW user_project_hours AS
SELECT 
    tl.project_id,
    tl.user_id,
    u.firstname,
    u.lastname,
    SUM(tl.hours) as total_hours,
    COUNT(*) as log_entries,
    MIN(tl.log_date) as first_log,
    MAX(tl.log_date) as last_log
FROM time_logs tl
JOIN users u ON tl.user_id = u.id
GROUP BY tl.project_id, tl.user_id, u.firstname, u.lastname;

-- ================================================================
-- 9. TRIGGERY
-- ================================================================

-- Trigger: Automatyczna aktualizacja updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_projects_updated_at BEFORE UPDATE ON projects
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_user_statuses_updated_at BEFORE UPDATE ON user_project_statuses
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_time_logs_updated_at BEFORE UPDATE ON time_logs
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Trigger: Aktualizacja licznika użycia statusu
CREATE OR REPLACE FUNCTION increment_status_usage()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE user_project_statuses 
    SET usage_count = usage_count + 1
    WHERE id = NEW.status_id;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_increment_status_usage
    AFTER INSERT ON project_status_history
    FOR EACH ROW
    EXECUTE FUNCTION increment_status_usage();

-- Trigger: Aktualizacja current_status_id w projekcie
CREATE OR REPLACE FUNCTION update_project_current_status()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE projects 
    SET current_status_id = NEW.status_id
    WHERE id = NEW.project_id;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_project_status
    AFTER INSERT ON project_status_history
    FOR EACH ROW
    EXECUTE FUNCTION update_project_current_status();

-- Trigger: Automatyczne dodanie właściciela jako członka projektu
CREATE OR REPLACE FUNCTION add_owner_as_member()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO project_members (project_id, user_id, role)
    VALUES (NEW.id, NEW.user_id, 'owner')
    ON CONFLICT (project_id, user_id) DO NOTHING;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_add_owner_as_member
    AFTER INSERT ON projects
    FOR EACH ROW
    EXECUTE FUNCTION add_owner_as_member();

-- Trigger: Tworzenie domyślnego projektu dla nowego użytkownika
CREATE OR REPLACE FUNCTION create_default_project()
RETURNS TRIGGER AS $$
DECLARE
    new_project_id INTEGER;
    default_status_id INTEGER;
BEGIN
    -- Utwórz domyślny status "Nowy" dla użytkownika
    INSERT INTO user_project_statuses (user_id, name, color, description)
    VALUES (NEW.id, 'Nowy', '#3B82F6', 'Projekt został utworzony')
    ON CONFLICT (user_id, name) DO UPDATE SET name = EXCLUDED.name
    RETURNING id INTO default_status_id;
    
    -- Utwórz domyślny projekt
    INSERT INTO projects (user_id, title, subtitle, image_url, status, current_status_id)
    VALUES (
        NEW.id,
        'Twój pierwszy projekt',
        'Kliknij "Zarządzaj" żeby zapoznać się z funkcjonalnościami aplikacji',
        'https://images.unsplash.com/photo-1542621334-a254cf47733d?w=400&h=300&fit=crop',
        'active',
        default_status_id
    )
    RETURNING id INTO new_project_id;
    
    -- Dodaj pierwszy wpis w historii statusów
    INSERT INTO project_status_history (project_id, status_id, changed_by, notes)
    VALUES (
        new_project_id,
        default_status_id,
        NEW.id,
        'Projekt został automatycznie utworzony'
    );
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_create_default_project
    AFTER INSERT ON users
    FOR EACH ROW
    EXECUTE FUNCTION create_default_project();

-- ================================================================
-- 10. DANE TESTOWE
-- ================================================================

-- Testowy użytkownik (hasło: test123)
INSERT INTO users (firstname, lastname, email, bio, enabled, password)
VALUES (
    'Jan',
    'Kowalski',
    'jan.kowalski@example.com',
    'Lubi programować w JS i PL/SQL.',
    TRUE,
    '$2y$10$MEoMVVOq1UcvlXC6XqNVke6VaWmnEOHZgT1qeKyMTXnyRIfdiGLoK'
) ON CONFLICT (email) DO NOTHING;

-- Dodatkowe statusy dla użytkownika testowego
DO $$
DECLARE
    test_user_id INTEGER;
    project_id INTEGER;
    status_w_trakcie INTEGER;
    status_wstrzymany INTEGER;
    status_zakonczony INTEGER;
BEGIN
    -- Pobierz ID użytkownika testowego
    SELECT id INTO test_user_id FROM users WHERE email = 'jan.kowalski@example.com';
    
    IF test_user_id IS NOT NULL THEN
        -- Dodaj dodatkowe statusy
        INSERT INTO user_project_statuses (user_id, name, color, description) VALUES
            (test_user_id, 'W trakcie', '#F59E0B', 'Projekt jest aktualnie realizowany'),
            (test_user_id, 'Wstrzymany', '#EF4444', 'Projekt został czasowo wstrzymany'),
            (test_user_id, 'Zakończony', '#10B981', 'Projekt został ukończony'),
            (test_user_id, 'Anulowany', '#6B7280', 'Projekt został anulowany')
        ON CONFLICT (user_id, name) DO NOTHING;
        
        -- Dodaj przykładowe logi czasu do pierwszego projektu
        SELECT id INTO project_id FROM projects WHERE user_id = test_user_id LIMIT 1;
        
        IF project_id IS NOT NULL THEN
            -- Dodaj przykładowe przepracowane godziny (różne dni)
            INSERT INTO time_logs (project_id, user_id, log_date, hours, description) VALUES
                (project_id, test_user_id, CURRENT_DATE - INTERVAL '7 days', 3.5, 'Analiza wymagań'),
                (project_id, test_user_id, CURRENT_DATE - INTERVAL '6 days', 4.0, 'Projektowanie architektury'),
                (project_id, test_user_id, CURRENT_DATE - INTERVAL '5 days', 5.5, 'Implementacja modułu A'),
                (project_id, test_user_id, CURRENT_DATE - INTERVAL '4 days', 6.0, 'Implementacja modułu B'),
                (project_id, test_user_id, CURRENT_DATE - INTERVAL '3 days', 4.5, 'Testy jednostkowe'),
                (project_id, test_user_id, CURRENT_DATE - INTERVAL '2 days', 3.0, 'Code review'),
                (project_id, test_user_id, CURRENT_DATE - INTERVAL '1 day', 2.5, 'Dokumentacja')
            ON CONFLICT (user_id, project_id, log_date) DO NOTHING;
            
            -- Dodaj kilka zmian statusu do historii
            SELECT id INTO status_w_trakcie FROM user_project_statuses 
                WHERE user_id = test_user_id AND name = 'W trakcie';
            
            SELECT id INTO status_wstrzymany FROM user_project_statuses 
                WHERE user_id = test_user_id AND name = 'Wstrzymany';
            
            IF status_w_trakcie IS NOT NULL THEN
                INSERT INTO project_status_history (project_id, status_id, changed_by, deadline_date, notes)
                VALUES (
                    project_id,
                    status_w_trakcie,
                    test_user_id,
                    CURRENT_DATE + INTERVAL '30 days',
                    'Rozpoczęto prace nad projektem'
                );
            END IF;
        END IF;
    END IF;
END $$;

-- ================================================================
-- KONIEC INICJALIZACJI
-- ================================================================