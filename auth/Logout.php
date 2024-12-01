<?php
session_start();

if (isset($_SESSION['user_id'])) {
    include('../config/database.php');
    $user_id = $_SESSION['user_id'];
    
    $activity_stmt = $conn->prepare("INSERT INTO useractivities (user_id, action, details) VALUES (?, 'logout', 'User logged out')");
    $activity_stmt->bind_param("i", $user_id);
    $activity_stmt->execute();
    
    $activity_stmt->close();
    $conn->close();
}

$_SESSION = [];

session_destroy();

header("Location: Login.php");
exit();
