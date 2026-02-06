<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';
require_once __DIR__.'/../repository/ProjectRepository.php';
require_once __DIR__.'/../repository/TimeLogRepository.php';

class DashboardController extends AppController {
    private $userRepository;
    private $projectRepository;
    private $timeLogRepository;

    public function __construct() {
        parent::__construct();
        $this->userRepository = new UserRepository();
        $this->projectRepository = new ProjectRepository();
        $this->timeLogRepository = new TimeLogRepository();
    }

    public function index($userId) {
        // Require login
        $this->requireLogin();
        
        // Check if the URL user ID matches the session user ID
        if ($_SESSION['user_id'] != $userId) {
            header('Location: /dashboard/' . $_SESSION['user_id']);
            exit();
        }

        // Get user data
        $user = $this->userRepository->getUserById($userId);
        
        if (!$user) {
            header('Location: /login');
            exit();
        }

        // Pobierz aktywne projekty użytkownika
        $projects = $this->projectRepository->getProjectsByUserId($userId);
        
        // Pobierz dzisiejsze logi
        $todayLogs = $this->timeLogRepository->getTodayLogs($userId);
        
        // Pobierz sumę dzisiejszych godzin
        $todayTotalHours = $this->timeLogRepository->getTodayTotalHours($userId);

        return $this->render('dashboard', [
            'user' => $user,
            'projects' => $projects,
            'todayLogs' => $todayLogs,
            'todayTotalHours' => $todayTotalHours
        ]);
    }

    public function logHours() {
        // Require login
        $this->requireLogin();

        // Only accept POST requests
        if (!$this->isPost()) {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit();
        }

        $projectId = (int)($_POST['project_id'] ?? 0);
        $hours = (float)($_POST['hours'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $logDate = $_POST['log_date'] ?? date('Y-m-d');

        // Walidacja
        if ($projectId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Wybierz projekt']);
            exit();
        }

        if ($hours <= 0 || $hours > 24) {
            http_response_code(400);
            echo json_encode(['error' => 'Podaj prawidłową liczbę godzin (0-24)']);
            exit();
        }

        // Sprawdź czy projekt należy do użytkownika
        if (!$this->projectRepository->projectBelongsToUser($projectId, $_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień do tego projektu']);
            exit();
        }

        try {
            $logId = $this->timeLogRepository->addOrUpdateTimeLog(
                $projectId,
                $_SESSION['user_id'],
                $hours,
                $logDate,
                $description
            );

            // Pobierz zaktualizowaną sumę dzisiejszych godzin
            $todayTotalHours = $this->timeLogRepository->getTodayTotalHours($_SESSION['user_id']);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'id' => $logId,
                'todayTotalHours' => $todayTotalHours,
                'message' => 'Godziny zostały zapisane'
            ]);
            exit();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd podczas zapisywania godzin: ' . $e->getMessage()]);
            exit();
        }
    }
}