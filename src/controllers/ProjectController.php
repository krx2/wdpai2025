<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';
require_once __DIR__.'/../repository/ProjectRepository.php';
require_once __DIR__.'/../repository/StatusHistoryRepository.php';
require_once __DIR__.'/../repository/TimeLogRepository.php';
require_once __DIR__.'/../repository/ProjectStatusRepository.php';

class ProjectController extends AppController {
    private $userRepository;
    private $projectRepository;
    private $statusHistoryRepository;
    private $timeLogRepository;
    private $projectStatusRepository;

    public function __construct() {
        parent::__construct();
        $this->userRepository = new UserRepository();
        $this->projectRepository = new ProjectRepository();
        $this->statusHistoryRepository = new StatusHistoryRepository();
        $this->timeLogRepository = new TimeLogRepository();
        $this->projectStatusRepository = new ProjectStatusRepository();
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

        // Pobierz aktualny status projektu
        $currentStatus = $this->statusHistoryRepository->getLatestStatus($projectId);

        // Pobierz przepracowane godziny
        $totalHours = $this->timeLogRepository->getProjectTotalHours($projectId);

        // Pobierz historię statusów
        $statusHistory = $this->statusHistoryRepository->getProjectHistory($projectId);

        // Pobierz logi czasu
        $timeLogs = $this->timeLogRepository->getProjectTimeLogs($projectId);

        // Pobierz wszystkie statusy użytkownika
        $userStatuses = $this->projectStatusRepository->getUserStatuses($_SESSION['user_id']);

        return $this->render('project-manage', [
            'project' => $project,
            'user' => $user,
            'currentStatus' => $currentStatus,
            'totalHours' => $totalHours,
            'statusHistory' => $statusHistory,
            'timeLogs' => $timeLogs,
            'userStatuses' => $userStatuses
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

            // Redirect do zarządzania projektem po udanej edycji
            header('Location: /projects/manage/' . $projectId);
            exit();
        } catch (Exception $e) {
            return $this->render('project-edit', [
                'project' => $project,
                'messages' => 'Błąd podczas aktualizacji projektu: ' . $e->getMessage()
            ]);
        }
    }

    public function update($projectId) {
        // Require login
        $this->requireLogin();

        // Only accept POST requests
        if (!$this->isPost()) {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit();
        }

        // Sprawdź czy projekt należy do użytkownika
        if (!$this->projectRepository->projectBelongsToUser($projectId, $_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień do tego projektu']);
            exit();
        }

        // Pobierz dane z formularza
        $title = trim($_POST["title"] ?? '');
        $subtitle = trim($_POST["subtitle"] ?? '');
        $imageUrl = trim($_POST["image_url"] ?? '');
        $startDate = $_POST["start_date"] ?? null;
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

        try {
            $this->projectRepository->updateProject(
                $projectId,
                $title,
                $subtitle,
                $imageUrl,
                $completionDate,
                $description
            );

            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Projekt zaktualizowany pomyślnie']);
            exit();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd podczas aktualizacji projektu: ' . $e->getMessage()]);
            exit();
        }
    }

    public function updateStatus($projectId) {
        // Require login
        $this->requireLogin();

        // Only accept POST requests
        if (!$this->isPost()) {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit();
        }

        // Sprawdź czy projekt należy do użytkownika
        if (!$this->projectRepository->projectBelongsToUser($projectId, $_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień do tego projektu']);
            exit();
        }

        // Pobierz dane z formularza
        $statusId = (int)($_POST['status_id'] ?? 0);
        $deadlineDate = $_POST['deadline_date'] ?? null;
        $notes = trim($_POST['notes'] ?? '');

        // Walidacja
        if ($statusId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Wybierz status']);
            exit();
        }

        // Sprawdź czy status należy do użytkownika
        $status = $this->projectStatusRepository->getStatusById($statusId);
        if (!$status || $status['user_id'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Nieprawidłowy status']);
            exit();
        }

        try {
            // Dodaj wpis do historii statusów
            $historyId = $this->statusHistoryRepository->addStatusChange(
                $projectId,
                $statusId,
                $_SESSION['user_id'],
                $deadlineDate,
                $notes
            );

            // Pobierz zaktualizowany status
            $currentStatus = $this->statusHistoryRepository->getLatestStatus($projectId);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Status projektu został zaktualizowany',
                'currentStatus' => $currentStatus
            ]);
            exit();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd podczas aktualizacji statusu: ' . $e->getMessage()]);
            exit();
        }
    }

    public function createStatus() {
        // Require login
        $this->requireLogin();

        // Only accept POST requests
        if (!$this->isPost()) {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit();
        }

        // Pobierz dane z formularza
        $name = trim($_POST['name'] ?? '');
        $color = trim($_POST['color'] ?? '#3B82F6');
        $description = trim($_POST['description'] ?? '');
        $isFinal = isset($_POST['is_final']) ? (bool)$_POST['is_final'] : false;

        // Walidacja
        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nazwa statusu jest wymagana']);
            exit();
        }

        if (strlen($name) > 100) {
            http_response_code(400);
            echo json_encode(['error' => 'Nazwa statusu nie może być dłuższa niż 100 znaków']);
            exit();
        }

        // Walidacja koloru (hex format)
        if (!preg_match('/^#[0-9A-F]{6}$/i', $color)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowy format koloru']);
            exit();
        }

        try {
            // Utwórz nowy status
            $status = $this->projectStatusRepository->createStatus(
                $_SESSION['user_id'],
                $name,
                $color,
                $isFinal,
                $description
            );

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Nowy status został utworzony',
                'status' => $status
            ]);
            exit();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd podczas tworzenia statusu: ' . $e->getMessage()]);
            exit();
        }
    }
}