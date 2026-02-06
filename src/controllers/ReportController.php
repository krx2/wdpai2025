<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';
require_once __DIR__.'/../database/Database.php';

class ReportController extends AppController {
    private $userRepository;
    private $database;

    public function __construct() {
        parent::__construct();
        $this->userRepository = new UserRepository();
        $this->database = new Database();
    }

    public function index($userId) {
        // Require login
        $this->requireLogin();
        
        // Check if the URL user ID matches the session user ID
        if ($_SESSION['user_id'] != $userId) {
            header('Location: /report/' . $_SESSION['user_id']);
            exit();
        }

        // Get user data
        $user = $this->userRepository->getUserById($userId);
        
        if (!$user) {
            header('Location: /login');
            exit();
        }

        return $this->render('report', [
            'user' => $user
        ]);
    }

    public function getData() {
        // Require login
        $this->requireLogin();

        // Only accept GET requests
        if (!$this->isGet()) {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit();
        }

        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));

        // Walidacja
        if ($month < 1 || $month > 12) {
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowy miesiąc']);
            exit();
        }

        if ($year < 2020 || $year > 2100) {
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowy rok']);
            exit();
        }

        try {
            // Pobierz dane z bazy danych
            $reportData = $this->getMonthlyReport($_SESSION['user_id'], $year, $month);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'year' => $year,
                'month' => $month,
                'projects' => $reportData
            ]);
            exit();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd podczas pobierania danych: ' . $e->getMessage()]);
            exit();
        }
    }

    private function getMonthlyReport(int $userId, int $year, int $month): array {
        // Pobierz połączenie do bazy
        $db = $this->database->connect();

        // Przygotuj zapytanie SQL
        // Pobieramy sumę godzin per projekt dla danego miesiąca
        $stmt = $db->prepare('
            SELECT 
                p.id as project_id,
                p.title,
                SUM(tl.hours) as total_hours
            FROM time_logs tl
            JOIN projects p ON tl.project_id = p.id
            WHERE tl.user_id = :user_id
            AND EXTRACT(YEAR FROM tl.log_date) = :year
            AND EXTRACT(MONTH FROM tl.log_date) = :month
            GROUP BY p.id, p.title
            ORDER BY p.title ASC
        ');

        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':year', $year, PDO::PARAM_INT);
        $stmt->bindParam(':month', $month, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $results ? $results : [];
    }
}