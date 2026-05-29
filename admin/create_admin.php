<?php
// Run this once to create admin user
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$username = 'admin';
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if admin exists
$check = "SELECT * FROM users WHERE username = 'admin'";
$stmt = $db->prepare($check);
$stmt->execute();

if($stmt->rowCount() == 0) {
    $query = "INSERT INTO users (username, password, role) VALUES (:username, :password, 'Admin')";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':username' => $username,
        ':password' => $hashed_password
    ]);
    echo "Admin user created successfully!<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
} else {
    // Update existing admin
    $query = "UPDATE users SET password = :password WHERE username = 'admin'";
    $stmt = $db->prepare($query);
    $stmt->execute([':password' => $hashed_password]);
    echo "Admin password updated successfully!<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
}
?>