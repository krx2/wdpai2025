<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';
require_once __DIR__.'/../repository/ProjectRepository.php';

class ProjectController extends AppController {
    private $userRepository;
    private $projectRepository;

    public function __construct() {
        parent::__construct();
        $this->userRepository = new UserRepository();
        $this->projectRepository = new ProjectRepository();
    }

    public function index($userId) {
        // Require login
        $this->requireLogin();
        
        // Check if the URL user ID matches the session user ID
        if ($_SESSION['user_id'] != $userId) {
            header('Location: /projects/' . $_SESSION['user_id']);
            exit();
        }

        // Get user data
        $user = $this->userRepository->getUserById($userId);
        
        if (!$user) {
            header('Location: /login');
            exit();
        }

        // Get user's projects from database
        $projects = $this->projectRepository->getProjectsByUserId($userId);

        return $this->render('projects', [
            'projects' => $projects,
            'user' => $user
        ]);
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
            http_response_code(400);
            echo json_encode(['error' => 'Tytuł projektu jest wymagany']);
            exit();
        }

        if (strlen($title) > 200) {
            http_response_code(400);
            echo json_encode(['error' => 'Tytuł nie może być dłuższy niż 200 znaków']);
            exit();
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

            // Zwróć sukces
            http_response_code(200);
            echo json_encode(['success' => true, 'id' => $projectId]);
            exit();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd podczas tworzenia projektu: ' . $e->getMessage()]);
            exit();
        }
    }

    public function manage($projectId) {
        // Require login
        $this->requireLogin();

        // Sprawdź czy projekt należy do użytkownika
        if (!$this->projectRepository->projectBelongsToUser($projectId, $_SESSION['user_id'])) {
            header('Location: /projects/' . $_SESSION['user_id']);
            exit();
        }

        // Pobierz dane projektu
        $project = $this->projectRepository->getProjectById($projectId);
        
        if (!$project) {
            header('Location: /projects/' . $_SESSION['user_id']);
            exit();
        }

        // Pobierz dane użytkownika
        $user = $this->userRepository->getUserById($_SESSION['user_id']);

        // TODO: Pobierz aktualny status i przepracowane godziny z bazy
        // $currentStatus = $this->statusRepository->getCurrentStatus($projectId);
        // $totalHours = $this->timeLogRepository->getTotalHours($projectId);

        return $this->render('project-manage', [
            'project' => $project,
            'user' => $user,
            // 'currentStatus' => $currentStatus,
            // 'totalHours' => $totalHours
        ]);
    }

    public function edit($projectId) {
        // Require login
        $this->requireLogin();

        // Sprawdź czy projekt należy do użytkownika
        if (!$this->projectRepository->projectBelongsToUser($projectId, $_SESSION['user_id'])) {
            header('Location: /projects/' . $_SESSION['user_id']);
            exit();
        }

        $project = $this->projectRepository->getProjectById($projectId);
        
        if (!$project) {
            header('Location: /projects/' . $_SESSION['user_id']);
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

            // Redirect do projektów po udanej edycji
            header('Location: /projects/' . $_SESSION['user_id']);
            exit();
        } catch (Exception $e) {
            return $this->render('project-edit', [
                'project' => $project,
                'messages' => 'Błąd podczas aktualizacji projektu: ' . $e->getMessage()
            ]);
        }
    }
}