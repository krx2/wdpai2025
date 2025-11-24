<?php
require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';


class Routing {

    public static function run(string $path) {
        switch ($path) {
            case '':
            case 'login':
                $controller = new SecurityController();
                $controller->login();
                break;
            case 'dashboard':
                $controller = new DashboardController();
                $controller->index();
                break;
            default:
                include 'public/views/404.html';
                break;
        } 
    }
}