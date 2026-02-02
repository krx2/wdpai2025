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
                SELECT tl.*, p.name as project_name
                FROM time_logs tl
                JOIN projects p ON tl.project_id = p.id
                WHERE tl.user_id = :user_id AND tl.project_id = :project_id
                ORDER BY tl.log_date DESC
            ');
            $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
        } else {
            $stmt = $this->database->connect()->prepare('
                SELECT tl.*, p.name as project_name
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