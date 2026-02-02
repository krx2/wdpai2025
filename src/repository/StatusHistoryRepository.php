<?php

require_once __DIR__ . '/Repository.php';

class StatusHistoryRepository extends Repository
{
    public function getProjectHistory(int $projectId): array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT 
                psh.*,
                ps.name as status_name,
                ps.color as status_color,
                u.firstname as changed_by_firstname,
                u.lastname as changed_by_lastname
            FROM project_status_history psh
            JOIN project_statuses ps ON psh.status_id = ps.id
            LEFT JOIN users u ON psh.changed_by = u.id
            WHERE psh.project_id = :project_id
            ORDER BY psh.actual_change_date DESC
        ');
        $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addStatusChange(
        int $projectId, 
        int $statusId, 
        int $changedBy,
        ?string $deadlineDate = null,
        ?string $notes = null
    ): int {
        try {
            $stmt = $this->database->connect()->prepare('
                INSERT INTO project_status_history 
                (project_id, status_id, deadline_date, changed_by, notes)
                VALUES (:project_id, :status_id, :deadline_date, :changed_by, :notes)
                RETURNING id
            ');
            $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
            $stmt->bindParam(':status_id', $statusId, PDO::PARAM_INT);
            $stmt->bindParam(':deadline_date', $deadlineDate);
            $stmt->bindParam(':changed_by', $changedBy, PDO::PARAM_INT);
            $stmt->bindParam(':notes', $notes);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['id'];
        } catch (PDOException $e) {
            throw new Exception("Error adding status change: " . $e->getMessage());
        }
    }

    public function getLatestStatus(int $projectId): ?array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT 
                psh.*,
                ps.name as status_name,
                ps.color as status_color
            FROM project_status_history psh
            JOIN project_statuses ps ON psh.status_id = ps.id
            WHERE psh.project_id = :project_id
            ORDER BY psh.actual_change_date DESC
            LIMIT 1
        ');
        $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->execute();
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        return $status ? $status : null;
    }
}