<?php
require_once 'src/controllers/SecurityController.php';


class Routing {

    public static function run(string $path) {
        //TODO na podstawie sciezki sprawdzamy jaki HTML zwrocic
        switch ($path) {
            case '':
            case 'login':
                $controller = new SecurityController();
                $controller->login();
                break;
            case 'dashboard':
                include 'public/views/dashboard.html';
                break;
            default:
                include 'public/views/404.html';
                break;
        } 
    }
}