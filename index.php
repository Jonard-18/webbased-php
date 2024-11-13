<?php
session_start();
if (isset($_SESSION['user_role'])) {
    header('Location: ' . ($_SESSION['user_role'] === 'student' ? 'student/dashboard.php' : 'staff/dashboard.php'));
    exit();
} else {
    header('Location: auth/login.php');
    exit();
}
?>