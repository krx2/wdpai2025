<?php

require_once __DIR__ . '/Repository.php';

class StatusHistoryRepository extends Repository
{
    public function getProjectHistory(int $projectId): array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT 
                psh.*,
                ups.name as status_name,
                ups.color as status_color,
                u.firstname as changed_by_firstname,
                u.lastname as changed_by_lastname
            FROM project_status_history psh
            JOIN user_project_statuses ups ON psh.status_id = ups.id
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
                ups.name as status_name,
                ups.color as status_color
            FROM project_status_history psh
            JOIN user_project_statuses ups ON psh.status_id = ups.id
            WHERE psh.project_id = :project_id
            ORDER BY psh.actual_change_date DESC
            LIMIT 1
        ');
        $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->execute();
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        return $status ? $status : null;
    }

    /**
     * Pobierz najbliższy deadline dla użytkownika
     * Zwraca datę i listę projektów z tym deadline'em
     */
    public function getUpcomingDeadlines(int $userId): ?array
    {
        // Najpierw znajdź najbliższy deadline
        $stmt = $this->database->connect()->prepare('
            SELECT DISTINCT psh.deadline_date
            FROM project_status_history psh
            JOIN projects p ON psh.project_id = p.id
            WHERE p.user_id = :user_id
            AND psh.deadline_date >= CURRENT_DATE
            AND psh.id IN (
                -- Tylko ostatni status dla każdego projektu
                SELECT DISTINCT ON (project_id) id
                FROM project_status_history
                ORDER BY project_id, actual_change_date DESC
            )
            ORDER BY psh.deadline_date ASC
            LIMIT 1
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || !$result['deadline_date']) {
            return null;
        }
        
        $nearestDeadline = $result['deadline_date'];
        
        // Teraz pobierz wszystkie projekty z tym deadline'em
        $stmt = $this->database->connect()->prepare('
            SELECT 
                p.*,
                psh.deadline_date,
                psh.notes,
                ups.name as status_name,
                ups.color as status_color
            FROM projects p
            JOIN (
                SELECT DISTINCT ON (project_id)
                    project_id,
                    deadline_date,
                    status_id,
                    notes,
                    actual_change_date
                FROM project_status_history
                ORDER BY project_id, actual_change_date DESC
            ) psh ON p.id = psh.project_id
            JOIN user_project_statuses ups ON psh.status_id = ups.id
            WHERE p.user_id = :user_id
            AND psh.deadline_date = :deadline_date
            ORDER BY p.title ASC
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':deadline_date', $nearestDeadline);
        $stmt->execute();
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'deadline_date' => $nearestDeadline,
            'projects' => $projects
        ];
    }
}