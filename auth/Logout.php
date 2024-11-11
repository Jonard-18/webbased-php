<?php
session_start();

// Log the logout activity if the user is logged in
if (isset($_SESSION['user_id'])) {
    include('../config/database.php');
    $user_id = $_SESSION['user_id'];
    
    // Prepare an SQL statement to log the logout activity
    $activity_stmt = $conn->prepare("INSERT INTO useractivities (user_id, action, details) VALUES (?, 'logout', 'User logged out')");
    $activity_stmt->bind_param("i", $user_id);
    $activity_stmt->execute();
    
    $activity_stmt->close();
    $conn->close();
}

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: Login.php");
exit();
