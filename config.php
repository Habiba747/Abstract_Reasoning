<?php
// ---- Database settings (XAMPP/WAMP defaults) ----
const DB_HOST = '127.0.0.1';
const DB_PORT = '3307';
const DB_NAME = 'abstract_reasoning';
const DB_USER = 'root';
const DB_PASS = '';          // XAMPP default is empty. Change if you set a password.

const QUESTIONS_PER_QUIZ = 10;

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

try {
    $pdo = new PDO(
       'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}  catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());

}

function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): void {
    $ok = isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
    if (!$ok) {
        http_response_code(400);
        exit('Invalid request. Please go back and try again.');
    }
}

function require_login(): int {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    return (int)$_SESSION['user_id'];
}
