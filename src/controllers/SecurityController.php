<?php

require_once 'AppController.php';

class SecurityController extends AppController {

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            // TODO: validate and check credentials against database
            // For now, just redirect to dashboard
            header('Location: /dashboard');
            exit();
        }
        
        return $this->render("login");
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // TODO: Validate data
            if (empty($email) || empty($password)) {
                return $this->render("register", ["message" => "Wszystkie pola są wymagane"]);
            }
            
            if ($password !== $confirmPassword) {
                return $this->render("register", ["message" => "Hasła nie są zgodne"]);
            }
            
            // TODO: Hash password
            // $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // TODO: Insert to database
            
            // Success - show message or redirect
            return $this->render("login", ["message" => "Zarejestrowano użytkownika pomyślnie"]);
        }
        
        return $this->render("register");
    }
}