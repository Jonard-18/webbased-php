<?php
session_start(); // Start the session to access error messages
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVSU RESERVE - Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --evsu-red: #8B0000;
            --evsu-gold: #FFD700;
        }

        html,
        body {
            height: 100%;
            margin: 0;
        }

        body {
            background-color: #f4f4f4;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Arial', sans-serif;
            background-image: linear-gradient(to bottom, rgba(255, 255, 255, 0.65) 0%, rgba(255, 255, 255, 0.65)), url("../assets/images/evsu_bg.jpg");
            background-position: center;
            background-size: cover;
        }

        .registration-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 550px;
            padding: 2.5rem;
            text-align: center;
            position: relative; /* Added for positioning alert */
            min-height: 350px; /* Set a minimum height to ensure consistent size */
        }

        .logo-container {
            margin-bottom: 1rem;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo {
            max-width: 150px;
            height: auto;
        }

        .registration-title {
            color: var(--evsu-red);
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .registration-subtitle {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }

        .form-control {
            border-color: #ced4da;
            height: 50px;
            border-radius: 6px;
        }

        .form-control:focus {
            border-color: var(--evsu-gold);
            box-shadow: 0 0 0 0.25rem rgba(255, 215, 0, 0.25);
        }

        .btn-register {
            background-color: var(--evsu-red);
            color: white;
            border: none;
            height: 50px;
            transition: background-color 0.3s ease;
        }

        .btn-register:hover {
            background-color: #6B0000;
        }

        .login_instead a {
            color: var(--evsu-red);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .login_instead a:hover {
            text-decoration: underline;
        }

        .alert {
            margin-top: 15px;
            opacity: 0; /* Start hidden */
            transition: opacity 0.5s ease; /* Smooth transition */
        }

        .alert.show {
            opacity: 1; /* Fully visible when 'show' class is added */
        }
    </style>
</head>

<body>
    <div class="d-flex justify-content-center align-items-center min-vh-100">
        <div class="registration-container">
            <div class="logo-container">
                <img src="../assets/images/logo.png" alt="EVSU Logo" class="logo w-25">
            </div>

            <h2 class="registration-title">EVSU - RESERVE</h2>
            <p class="registration-subtitle">Streamlining Inventory and Reservations</p>

            <!-- Error Alert -->
            <?php
            if (isset($_GET['error'])) {
                switch ($_GET['error']) {
                    case 'duplicate':
                        echo '<div id="error-alert" class="alert alert-danger show">Username or Email already exists. Please choose another.</div>';
                        break;
                    case 'registration_failed':
                        echo '<div id="error-alert" class="alert alert-danger show">Registration failed. Please try again later.</div>';
                        break;
                    case 'connection_failed':
                        echo '<div id="error-alert" class="alert alert-danger show">Database connection failed. Please try again later.</div>';
                        break;
                }
            }
            ?>

            <form action="registration_process.php" method="post">
                <div class="mb-3">
                    <input type="text" class="form-control" name="username" placeholder="Username" required>
                </div>
                <div class="mb-3">
                    <input type="email" class="form-control" name="email" placeholder="Email" required>
                </div>
                <div class="mb-3">
                    <input type="password" class="form-control" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn btn-register w-100">Register</button>

                <div class="login_instead mt-2">
                    <a href="Login.php">Already have an account? Login</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const errorAlert = document.getElementById('error-alert');
            if (errorAlert) {
                // Remove 'show' class after a short delay to perform fade-out
                setTimeout(() => {
                    errorAlert.classList.remove('show');
                    setTimeout(() => {
                        errorAlert.remove();
                    }, 500); // Wait for fade-out transition to complete
                }, 2000); // Keep alert visible for 2 seconds
            }
        });
    </script>
</body>

</html>