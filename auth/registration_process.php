<?php
session_start(); // Start the session

// Database configuration
$host = 'localhost'; // your database host
$db = 'inventory_system'; // your database name
$user = 'root'; // your database user
$pass = ''; // your database password

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    header("Location: Registration.php?error=connection_failed");
    exit();
}

// Only proceed with form submission if method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if the username or email already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        header("Location: Registration.php?error=duplicate");
        exit();
    }

    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashed_password);

    // Execute the statement
    if ($stmt->execute()) {
        // Registration successful
        header("Location: Login.php");
        exit();
    } else {
        // Set error message in the session and redirect back to the form
        header("Location: Registration.php?error=registration_failed");
        exit();
    }

    // Close statement
    $stmt->close();
}

// Close connection
$conn->close();
?>