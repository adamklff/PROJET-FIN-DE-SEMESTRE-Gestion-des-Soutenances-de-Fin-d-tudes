<?php
// Database configuration - XAMPP default settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // ← empty password on default XAMPP
define('DB_NAME', 'gestion_soutenances');

// Connect to DB
function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simple redirect if not logged in
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}
?>