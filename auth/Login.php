<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FVSU - RESERVE Login</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
        }
        
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .main-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            background-image: linear-gradient(
                rgba(255, 255, 255, 0.70), 
                rgba(255, 255, 255, 0.70)
            ), url(../assets/images/evsu_bg.jpg);
            background-position: center;
            background-size: cover;
        }

        .login-container {
            background-color: #8B0000;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
            padding: 2rem;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .login-title {
            color: white;
            text-align: center;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        .login-subtitle {
            color: #f8f9fa;
            text-align: center;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .form-control {
            margin-bottom: 1rem;
            height: 45px;
            border-radius: 5px;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(255, 215, 0, 0.25);
            border-color: #FFD700;
        }

        .btn-login {
            background-color: #FFD700 !important;
            border: none;
            color: #000;
            font-weight: 500;
            width: 100%;
            height: 45px;
            border-radius: 5px;
            margin-top: 0.5rem;
        }

        .btn-login:hover {
            background-color: #FFC800;
        }

        .forgot-password {
            text-align: center;
            margin-top: 1rem;
        }

        .forgot-password a {
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .alert {
            margin-bottom: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php
    include('../includes/header.php');
    session_start();

    // Redirect if already logged in
    if (isset($_SESSION['user_id'])) {
        switch ($_SESSION['role']) {
            case 'Student':
                header("Location: ../student/dashboard.php");
                break;
            case 'Staff':
                header("Location: ../staff/dashboard.php");
                break;
            case 'Admin':
                header("Location: ../admin/dashboard.php");
                break;
        }
        exit();
    }
    ?>

    <div class="main-container">
        <div class="login-container">
            <h2 class="login-title">EVSU - RESERVE</h2>
            <p class="login-subtitle">Streamlining Inventory and Reservations</p>

            <?php
            // Display error messages
            if (isset($_GET['error'])) {
                switch ($_GET['error']) {
                    case 'invalid_credentials':
                        echo '<div class="alert alert-danger" role="alert">Invalid username or password.</div>';
                        break;
                    case 'invalid_role':
                        echo '<div class="alert alert-danger" role="alert">Invalid user role.</div>';
                        break;
                }
            }
            ?>

            <form action="login_process.php" method="post" class="needs-validation" novalidate>
                <div class="mb-3">
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Student ID" required>
                    <div class="invalid-feedback">
                        Please enter your Student ID.
                    </div>
                </div>

                <div class="mb-3">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Password" required>
                    <div class="invalid-feedback">
                        Please enter your password.
                    </div>
                </div>

                <button type="submit" class="btn btn-login">Login</button>

                <div class="forgot-password">
                    <a href="#">Forgot Password?</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Form validation script -->
    <script>
        (function () {
            'use strict'
            
            // Fetch all forms that need validation
            var forms = document.querySelectorAll('.needs-validation')

            // Loop over them and prevent submission
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html>