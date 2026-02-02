<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/ProjectRepository.php';

class ProjectController extends AppController {
    private $projectRepository;

    public function __construct() {
        parent::__construct();
        $this->projectRepository = new ProjectRepository();
    }

    public function create() {
        // Require login
        $this->requireLogin();
        
        if ($this->isGet()) {
            return $this->render("project-create");
        }

        // POST - tworzenie projektu
        $title = trim($_POST["title"] ?? '');
        $subtitle = trim($_POST["subtitle"] ?? '');
        $imageUrl = trim($_POST["image_url"] ?? '');
        $completionDate = $_POST["completion_date"] ?? null;
        $description = trim($_POST["description"] ?? '');

        // Walidacja
        if (empty($title)) {
            return $this->render('project-create', ['messages' => 'Tytuł projektu jest wymagany']);
        }

        if (strlen($title) > 200) {
            return $this->render('project-create', ['messages' => 'Tytuł nie może być dłuższy niż 200 znaków']);
        }

        // Jeśli nie podano URL obrazka, użyj domyślnego
        if (empty($imageUrl)) {
            $imageUrl = 'https://images.unsplash.com/photo-1515787366009-7cbdd2dc587b?w=400&h=300&fit=crop';
        }

        try {
            $projectId = $this->projectRepository->createProject(
                $_SESSION['user_id'],
                $title,
                $subtitle,
                $imageUrl,
                $completionDate,
                $description
            );

            // Redirect do dashboardu po udanym utworzeniu
            header('Location: /dashboard/' . $_SESSION['user_id']);
            exit();
        } catch (Exception $e) {
            return $this->render('project-create', ['messages' => 'Błąd podczas tworzenia projektu: ' . $e->getMessage()]);
        }
    }

    public function edit($projectId) {
        // Require login
        $this->requireLogin();

        // Sprawdź czy projekt należy do użytkownika
        if (!$this->projectRepository->projectBelongsToUser($projectId, $_SESSION['user_id'])) {
            header('Location: /dashboard/' . $_SESSION['user_id']);
            exit();
        }

        $project = $this->projectRepository->getProjectById($projectId);
        
        if (!$project) {
            header('Location: /dashboard/' . $_SESSION['user_id']);
            exit();
        }

        if ($this->isGet()) {
            return $this->render("project-edit", ['project' => $project]);
        }

        // POST - edycja projektu
        $title = trim($_POST["title"] ?? '');
        $subtitle = trim($_POST["subtitle"] ?? '');
        $imageUrl = trim($_POST["image_url"] ?? '');
        $completionDate = $_POST["completion_date"] ?? null;
        $description = trim($_POST["description"] ?? '');

        // Walidacja
        if (empty($title)) {
            return $this->render('project-edit', [
                'project' => $project,
                'messages' => 'Tytuł projektu jest wymagany'
            ]);
        }

        if (strlen($title) > 200) {
            return $this->render('project-edit', [
                'project' => $project,
                'messages' => 'Tytuł nie może być dłuższy niż 200 znaków'
            ]);
        }

        try {
            $this->projectRepository->updateProject(
                $projectId,
                $title,
                $subtitle,
                $imageUrl,
                $completionDate,
                $description
            );

            // Redirect do dashboardu po udanej edycji
            header('Location: /dashboard/' . $_SESSION['user_id']);
            exit();
        } catch (Exception $e) {
            return $this->render('project-edit', [
                'project' => $project,
                'messages' => 'Błąd podczas aktualizacji projektu: ' . $e->getMessage()
            ]);
        }
    }
}