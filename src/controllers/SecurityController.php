<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';

class SecurityController extends AppController {
    private $userRepository;

    public function __construct() {
        parent::__construct(); // WAŻNE: wywołanie konstruktora rodzica
        $this->userRepository = new UserRepository();
    }

    public function login() {
        // If already logged in, redirect to dashboard
        if ($this->isLoggedIn()) {
            header('Location: /dashboard/' . $_SESSION['user_id']);
            exit();
        }

        if($this->isGet()) {
            return $this->render("login");
        } 

        $email = $_POST["email"] ?? '';
        $password = $_POST["password"] ?? '';

        if (empty($email) || empty($password)) {
            return $this->render('login', ['messages' => 'Fill all fields']);
        }

        $user = $this->userRepository->getUserByEmail($email);

        if (!$user) {
            return $this->render('login', ['messages' => 'User not found!']);
        }

        if (!password_verify($password, $user['password'])) {
            return $this->render('login', ['messages' => 'Wrong password!']);
        }

        // Successful login - set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['firstname'] = $user['firstname'];

        // Redirect to dashboard
        header('Location: /dashboard/' . $user['id']);
        exit();
    }

    public function register() {
        // If already logged in, redirect to dashboard
        if ($this->isLoggedIn()) {
            header('Location: /dashboard/' . $_SESSION['user_id']);
            exit();
        }

        if ($this->isGet()) {
            return $this->render("register");
        }

        $email = $_POST["email"] ?? '';
        $password1 = $_POST["password1"] ?? '';
        $password2 = $_POST["password2"] ?? '';
        $firstname = $_POST["firstname"] ?? '';

        if (empty($email) || empty($password1) || empty($firstname)) {
            return $this->render('register', ['messages' => 'Fill all fields']);
        }

        if ($password1 !== $password2) {
            return $this->render('register', ['messages' => 'Passwords should be the same!']);
        }

        // Check if user with this email already exists
        $existingUser = $this->userRepository->getUserByEmail($email);
        if ($existingUser) {
            return $this->render('register', ['messages' => 'User with this email already exists!']);
        }

        $hashedPassword = password_hash($password1, PASSWORD_BCRYPT);
        
        try {
            $this->userRepository->createUser(
                $email,
                $hashedPassword,
                $firstname
            );
            return $this->render("login", ["messages" => "User registered successfully! You can now login."]);
        } catch (Exception $e) {
            return $this->render('register', ['messages' => 'Error during registration: ' . $e->getMessage()]);
        }
    }

    public function logout() {
        // Destroy session
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        header('Location: /login');
        exit();
    }
}