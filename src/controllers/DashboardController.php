<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';
require_once __DIR__.'/../repository/ProjectRepository.php';

class DashboardController extends AppController {
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
            header('Location: /dashboard/' . $_SESSION['user_id']);
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

        return $this->render('dashboard', [
            'projects' => $projects,
            'user' => $user
        ]);
    }
}