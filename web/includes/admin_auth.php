<?php
// Admin authentication helper
// Checks if the logged-in user is an admin

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_config.php';

function is_admin() {
    if (!is_logged_in()) {
        return false;
    }
    
    global $conn;
    
    // Check if is_admin column exists
    $checkCol = $conn->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
    if ($checkCol->num_rows === 0) {
        // Column doesn't exist, no admins yet
        return false;
    }
    
    // Check if user has is_admin flag set to 1
    $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $user = $result->fetch_assoc();
    return $user['is_admin'] == 1;
}

function require_admin() {
    if (!is_admin()) {
        header('Location: /web/');
        exit;
    }
}

?>
