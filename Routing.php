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
                $action = $pathSegments[1] ?? null;
                $controller = new DashboardController();
                
                // Handle dashboard actions
                if ($action === 'log-hours') {
                    // POST /dashboard/log-hours
                    $controller->logHours();
                } elseif ($action && is_numeric($action)) {
                    // GET /dashboard/{user_id}
                    $userId = (int)$action;
                    $controller->index($userId);
                } else {
                    // No user ID, redirect to login
                    header('Location: /login');
                    exit();
                }
                break;
            case 'projects':
                // Handle project routes
                $action = $pathSegments[1] ?? null;
                $controller = new ProjectController();
                
                // Jeśli jest liczba (user_id), to lista projektów
                if ($action && is_numeric($action)) {
                    $userId = (int)$action;
                    $controller->index($userId);
                } else {
                    // W przeciwnym razie obsłuż akcje
                    switch ($action) {
                        case 'create':
                            $controller->create();
                            break;
                        case 'manage':
                            // /projects/manage/{project_id}
                            $projectId = isset($pathSegments[2]) ? (int)$pathSegments[2] : null;
                            if ($projectId === null) {
                                header('Location: /projects/' . ($_SESSION['user_id'] ?? ''));
                                exit();
                            }
                            $controller->manage($projectId);
                            break;
                        case 'edit':
                            $projectId = isset($pathSegments[2]) ? (int)$pathSegments[2] : null;
                            if ($projectId === null) {
                                header('Location: /projects/' . ($_SESSION['user_id'] ?? ''));
                                exit();
                            }
                            $controller->edit($projectId);
                            break;
                        case 'update':
                            // /projects/update/{project_id}
                            $projectId = isset($pathSegments[2]) ? (int)$pathSegments[2] : null;
                            if ($projectId === null) {
                                http_response_code(400);
                                echo json_encode(['error' => 'Brak ID projektu']);
                                exit();
                            }
                            $controller->update($projectId);
                            break;
                        case 'update-status':
                            // /projects/update-status/{project_id}
                            $projectId = isset($pathSegments[2]) ? (int)$pathSegments[2] : null;
                            if ($projectId === null) {
                                http_response_code(400);
                                echo json_encode(['error' => 'Brak ID projektu']);
                                exit();
                            }
                            $controller->updateStatus($projectId);
                            break;
                        default:
                            include 'public/views/404.html';
                            break;
                    }
                }
                break;
            default:
                include 'public/views/404.html';
                break;
        } 
    }
}