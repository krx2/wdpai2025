<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';

class DashboardController extends AppController {
    private $userRepository;

    public function __construct() {
        parent::__construct();
        $this->userRepository = new UserRepository();
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

        return $this->render('dashboard', [
            'user' => $user
        ]);
    }
}