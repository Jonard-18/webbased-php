<?php
session_start();
include('../config/database.php');

// Function to sanitize input
function sanitize_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize user input
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];

    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password
        // Verify password without hashing for testing purposes
        if ($password === $user['password']) {
            // Password is correct, create session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Debugging output
            echo "User ID: " . $_SESSION['user_id'] . "<br>";
            echo "Username: " . $_SESSION['username'] . "<br>";
            echo "Role: " . $_SESSION['role'] . "<br>";

            // Log the successful login
            $activity_stmt = $conn->prepare("INSERT INTO useractivities (user_id, action, details) VALUES (?, 'login', 'Successful login')");
            $activity_stmt->bind_param("i", $user['user_id']);
            $activity_stmt->execute();

            // Redirect based on role
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

        } else {
            // Invalid password
            echo "Invalid password for user: " . $username;
            exit();
        }

    } else {
        // User not found
        echo "User not found: " . $username;
        // header("Location: ../login.php?error=invalid_credentials");
        exit();
    }

    $stmt->close();
} else {
    // If someone tries to access this file directly
    echo "Direct access is not allowed.";
    // header("Location: ../login.php");
    exit();
}

$conn->close();
?>