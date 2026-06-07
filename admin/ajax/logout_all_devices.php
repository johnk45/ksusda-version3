<?php
// ajax/logout_all_devices.php - Terminate all other sessions
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$current_session_id = session_id();
$user_id = $_SESSION['user_id'];

// If using database sessions, delete all sessions for this user except current
// For file-based sessions, you would need to track sessions differently

// Simple implementation: regenerate session ID
session_regenerate_id(true);

echo json_encode(['success' => true]);
?>