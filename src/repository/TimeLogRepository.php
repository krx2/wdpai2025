<?php

require_once __DIR__ . '/Repository.php';

class TimeLogRepository extends Repository
{
    public function getProjectTimeLogs(int $projectId): array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT 
                tl.*,
                u.firstname,
                u.lastname
            FROM time_logs tl
            JOIN users u ON tl.user_id = u.id
            WHERE tl.project_id = :project_id
            ORDER BY tl.log_date DESC, tl.created_at DESC
        ');
        $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserTimeLogs(int $userId, ?int $projectId = null): array
    {
        if ($projectId) {
            $stmt = $this->database->connect()->prepare('
                SELECT tl.*, p.title as project_name
                FROM time_logs tl
                JOIN projects p ON tl.project_id = p.id
                WHERE tl.user_id = :user_id AND tl.project_id = :project_id
                ORDER BY tl.log_date DESC
            ');
            $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
        } else {
            $stmt = $this->database->connect()->prepare('
                SELECT tl.*, p.title as project_name
                FROM time_logs tl
                JOIN projects p ON tl.project_id = p.id
                WHERE tl.user_id = :user_id
                ORDER BY tl.log_date DESC
            ');
        }
        
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Dodaj lub zaktualizuj godziny pracy dla użytkownika w danym dniu
     * Jeśli wpis już istnieje, aktualizuje godziny (dodaje do istniejących)
     */
    public function addOrUpdateTimeLog(
        int $projectId,
        int $userId,
        float $hours,
        string $logDate,
        ?string $description = null
    ): int {
        try {
            // Sprawdź czy wpis już istnieje
            $stmt = $this->database->connect()->prepare('
                SELECT id, hours FROM time_logs 
                WHERE user_id = :user_id 
                AND project_id = :project_id 
                AND log_date = :log_date
            ');
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
            $stmt->bindParam(':log_date', $logDate);
            $stmt->execute();
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Aktualizuj istniejący wpis - ZASTĄP godziny (nie dodawaj)
                $stmt = $this->database->connect()->prepare('
                    UPDATE time_logs 
                    SET hours = :hours, description = :description, updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                    RETURNING id
                ');
                $stmt->bindParam(':hours', $hours);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':id', $existing['id'], PDO::PARAM_INT);
                $stmt->execute();
                return $existing['id'];
            } else {
                // Utwórz nowy wpis
                $stmt = $this->database->connect()->prepare('
                    INSERT INTO time_logs (project_id, user_id, hours, log_date, description)
                    VALUES (:project_id, :user_id, :hours, :log_date, :description)
                    RETURNING id
                ');
                $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmt->bindParam(':hours', $hours);
                $stmt->bindParam(':log_date', $logDate);
                $stmt->bindParam(':description', $description);
                $stmt->execute();
                
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $result['id'];
            }
        } catch (PDOException $e) {
            throw new Exception("Error adding/updating time log: " . $e->getMessage());
        }
    }

    /**
     * Pobierz dzisiejsze godziny użytkownika dla wszystkich projektów
     */
    public function getTodayLogs(int $userId): array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT 
                tl.*,
                p.title as project_name,
                p.image_url as project_image,
                pcs.status_name as current_status,
                pcs.status_color as status_color
            FROM time_logs tl
            JOIN projects p ON tl.project_id = p.id
            LEFT JOIN project_current_status pcs ON p.id = pcs.project_id
            WHERE tl.user_id = :user_id 
            AND tl.log_date = CURRENT_DATE
            ORDER BY tl.created_at DESC
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Pobierz dzisiejsze godziny dla konkretnego projektu
     */
    public function getTodayLogForProject(int $userId, int $projectId): ?array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM time_logs 
            WHERE user_id = :user_id 
            AND project_id = :project_id 
            AND log_date = CURRENT_DATE
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->execute();
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        return $log ? $log : null;
    }

    /**
     * Pobierz sumę dzisiejszych godzin użytkownika
     */
    public function getTodayTotalHours(int $userId): float
    {
        $stmt = $this->database->connect()->prepare('
            SELECT COALESCE(SUM(hours), 0) as total
            FROM time_logs
            WHERE user_id = :user_id 
            AND log_date = CURRENT_DATE
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)$result['total'];
    }

    public function addTimeLog(
        int $projectId,
        int $userId,
        float $hours,
        string $logDate,
        ?string $description = null
    ): int {
        try {
            $stmt = $this->database->connect()->prepare('
                INSERT INTO time_logs (project_id, user_id, hours, log_date, description)
                VALUES (:project_id, :user_id, :hours, :log_date, :description)
                RETURNING id
            ');
            $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':hours', $hours);
            $stmt->bindParam(':log_date', $logDate);
            $stmt->bindParam(':description', $description);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['id'];
        } catch (PDOException $e) {
            throw new Exception("Error adding time log: " . $e->getMessage());
        }
    }

    public function updateTimeLog(int $id, float $hours, string $logDate, ?string $description = null): bool
    {
        try {
            $stmt = $this->database->connect()->prepare('
                UPDATE time_logs 
                SET hours = :hours, log_date = :log_date, description = :description
                WHERE id = :id
            ');
            $stmt->bindParam(':hours', $hours);
            $stmt->bindParam(':log_date', $logDate);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Error updating time log: " . $e->getMessage());
        }
    }

    public function deleteTimeLog(int $id): bool
    {
        try {
            $stmt = $this->database->connect()->prepare('
                DELETE FROM time_logs WHERE id = :id
            ');
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Error deleting time log: " . $e->getMessage());
        }
    }

    public function getProjectTotalHours(int $projectId): float
    {
        $stmt = $this->database->connect()->prepare('
            SELECT COALESCE(SUM(hours), 0) as total
            FROM time_logs
            WHERE project_id = :project_id
        ');
        $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)$result['total'];
    }

    public function getUserTotalHours(int $userId, ?int $projectId = null): float
    {
        if ($projectId) {
            $stmt = $this->database->connect()->prepare('
                SELECT COALESCE(SUM(hours), 0) as total
                FROM time_logs
                WHERE user_id = :user_id AND project_id = :project_id
            ');
            $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
        } else {
            $stmt = $this->database->connect()->prepare('
                SELECT COALESCE(SUM(hours), 0) as total
                FROM time_logs
                WHERE user_id = :user_id
            ');
        }
        
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)$result['total'];
    }
}