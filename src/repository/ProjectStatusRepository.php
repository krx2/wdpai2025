<?php

require_once __DIR__ . '/Repository.php';

class ProjectStatusRepository extends Repository
{
    // Pobierz wszystkie statusy danego użytkownika
    public function getUserStatuses(int $userId): array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM user_project_statuses 
            WHERE user_id = :user_id
            ORDER BY usage_count DESC, name ASC
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Pobierz status po ID
    public function getStatusById(int $id): ?array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM user_project_statuses WHERE id = :id
        ');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        return $status ? $status : null;
    }

    // Pobierz lub utwórz status po nazwie (dla danego usera)
    public function getOrCreateStatus(int $userId, string $name, string $color = '#CCCCCC', bool $isFinal = false): array
    {
        // Najpierw spróbuj znaleźć
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM user_project_statuses 
            WHERE user_id = :user_id AND LOWER(name) = LOWER(:name)
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($status) {
            return $status;
        }
        
        // Jeśli nie istnieje, utwórz
        return $this->createStatus($userId, $name, $color, $isFinal);
    }

    // Utwórz nowy status
    public function createStatus(int $userId, string $name, string $color = '#CCCCCC', bool $isFinal = false, ?string $description = null): array
    {
        try {
            $stmt = $this->database->connect()->prepare('
                INSERT INTO user_project_statuses (user_id, name, color, is_final, description)
                VALUES (:user_id, :name, :color, :is_final, :description)
                ON CONFLICT (user_id, name) DO UPDATE 
                SET color = EXCLUDED.color, is_final = EXCLUDED.is_final
                RETURNING *
            ');
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':color', $color, PDO::PARAM_STR);
            $stmt->bindParam(':is_final', $isFinal, PDO::PARAM_BOOL);
            $stmt->bindParam(':description', $description);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error creating status: " . $e->getMessage());
        }
    }

    // Aktualizuj status
    public function updateStatus(int $id, string $name, string $color, bool $isFinal, ?string $description = null): bool
    {
        try {
            $stmt = $this->database->connect()->prepare('
                UPDATE user_project_statuses 
                SET name = :name, color = :color, is_final = :is_final, 
                    description = :description, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ');
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':color', $color, PDO::PARAM_STR);
            $stmt->bindParam(':is_final', $isFinal, PDO::PARAM_BOOL);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Error updating status: " . $e->getMessage());
        }
    }

    // Usuń status (tylko jeśli nie jest używany)
    public function deleteStatus(int $id): bool
    {
        try {
            // Sprawdź czy status jest używany
            $stmt = $this->database->connect()->prepare('
                SELECT COUNT(*) as count FROM project_status_history WHERE status_id = :id
            ');
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                throw new Exception("Cannot delete status that is in use");
            }
            
            $stmt = $this->database->connect()->prepare('
                DELETE FROM user_project_statuses WHERE id = :id
            ');
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Error deleting status: " . $e->getMessage());
        }
    }

    // Pobierz sugestie statusów (najczęściej używane)
    public function getStatusSuggestions(int $userId, int $limit = 10): array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM user_status_suggestions
            WHERE user_id = :user_id
            LIMIT :limit
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Pobierz domyślny status dla nowego projektu
    public function getDefaultStatus(int $userId): ?array
    {
        // Zwróć najczęściej używany, a jeśli brak to pierwszy alfabetycznie
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM user_project_statuses 
            WHERE user_id = :user_id
            ORDER BY usage_count DESC, name ASC
            LIMIT 1
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        return $status ? $status : null;
    }
}