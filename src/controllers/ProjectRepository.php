<?php

require_once 'Repository.php';

class ProjectRepository extends Repository
{
    public function getProjectsByUserId(int $userId): array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM projects 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $projects ? $projects : [];
    }

    public function getProjectById(int $id): ?array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM projects WHERE id = :id
        ');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        return $project ? $project : null;
    }

    public function createProject(int $userId, string $title, string $subtitle = '', string $imageUrl = '', ?string $completionDate = null, string $description = ''): int
    {
        try {
            $stmt = $this->database->connect()->prepare('
                INSERT INTO projects (user_id, title, subtitle, image_url, completion_date, description, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
                RETURNING id
            ');
            $status = 'active';
            $stmt->execute([
                $userId,
                $title,
                $subtitle,
                $imageUrl,
                $completionDate,
                $description,
                $status
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['id'];
        } catch (PDOException $e) {
            throw new Exception("Error creating project: " . $e->getMessage());
        }
    }

    public function updateProject(int $id, string $title, string $subtitle = '', string $imageUrl = '', ?string $completionDate = null, string $description = ''): bool
    {
        try {
            $stmt = $this->database->connect()->prepare('
                UPDATE projects 
                SET title = ?, subtitle = ?, image_url = ?, completion_date = ?, description = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ');
            return $stmt->execute([
                $title,
                $subtitle,
                $imageUrl,
                $completionDate,
                $description,
                $id
            ]);
        } catch (PDOException $e) {
            throw new Exception("Error updating project: " . $e->getMessage());
        }
    }

    public function deleteProject(int $id): bool
    {
        try {
            $stmt = $this->database->connect()->prepare('
                DELETE FROM projects WHERE id = ?
            ');
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            throw new Exception("Error deleting project: " . $e->getMessage());
        }
    }

    public function getUserProjectCount(int $userId): int
    {
        $stmt = $this->database->connect()->prepare('
            SELECT COUNT(*) as count FROM projects WHERE user_id = :user_id
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }

    public function projectBelongsToUser(int $projectId, int $userId): bool
    {
        $stmt = $this->database->connect()->prepare('
            SELECT COUNT(*) as count FROM projects 
            WHERE id = :project_id AND user_id = :user_id
        ');
        $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'] > 0;
    }
}