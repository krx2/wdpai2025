<?php

require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';
require_once 'src/controllers/ProjectController.php';

class Routing {

    public static function run(string $path) {
        // Remove leading and trailing slashes
        $path = trim($path, '/');
        
        // Split path into segments
        $pathSegments = explode('/', $path);
        
        switch ($pathSegments[0]) {
            case '':
            case 'login':
                $controller = new SecurityController();
                $controller->login();
                break;
            case 'register':
                $controller = new SecurityController();
                $controller->register();
                break;
            case 'logout':
                $controller = new SecurityController();
                $controller->logout();
                break;
            case 'dashboard':
                // Get user ID from URL if present
                $userId = isset($pathSegments[1]) ? (int)$pathSegments[1] : null;
                
                if ($userId === null) {
                    // No user ID in URL, redirect to login
                    header('Location: /login');
                    exit();
                }
                
                $controller = new DashboardController();
                $controller->index($userId);
                break;
            case 'projects':
                // Handle project routes
                $action = $pathSegments[1] ?? 'index';
                $controller = new ProjectController();
                
                switch ($action) {
                    case 'create':
                        $controller->create();
                        break;
                    case 'edit':
                        $projectId = isset($pathSegments[2]) ? (int)$pathSegments[2] : null;
                        if ($projectId === null) {
                            header('Location: /dashboard/' . ($_SESSION['user_id'] ?? ''));
                            exit();
                        }
                        $controller->edit($projectId);
                        break;
                    default:
                        include 'public/views/404.html';
                        break;
                }
                break;
            default:
                include 'public/views/404.html';
                break;
        } 
    }
}