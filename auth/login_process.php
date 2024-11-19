<?php
session_start();
include('../config/database.php');

function sanitize_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_input = sanitize_input($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, username, email, password, role FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $login_input, $login_input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            $activity_stmt = $conn->prepare("INSERT INTO useractivities (user_id, action, details) VALUES (?, 'login', 'Successful login')");
            $activity_stmt->bind_param("i", $user['user_id']);
            $activity_stmt->execute();

            switch ($user['role']) {
                case 'Student':
                    header("Location: ../student/dashboard.php");
                    break;
                case 'Staff':
                    header("Location: ../staff/dashboard.php");
                    break;
                default:
                    header("Location: ../login.php?error=invalid_role");
            }
            exit();

        } else {
            header("Location: login.php?error=invalid_credentials");
            exit();
        }

    } else {
        header("Location: login.php?error=invalid_credentials");
        exit();
    }

    $stmt->close();
} else {
    header("Location: login.php");
    exit();
}
$conn->close();
?>