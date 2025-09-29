<?php
// csrf_token_generator.php - AJAX endpoint for getting CSRF token
session_start();

// Set content type
header('Content-Type: application/json');

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Return token as JSON
echo json_encode([
    'csrf_token' => $_SESSION['csrf_token'],
    'timestamp' => time()
]);
?>
