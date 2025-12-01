<?php

require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';

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
            default:
                include 'public/views/404.html';
                break;
        } 
    }
}