<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVSU RESERVE - Login</title>
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
            background-image:
                linear-gradient(to bottom, rgba(255, 255, 255, 0.65) 0%, rgba(255, 255, 255, 0.65)),
                url("../assets/images/evsu_bg.jpg");
            background-position: center;
            background-size: cover;
        }

        .login-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 550px;
            padding: 2.5rem;
            text-align: center;
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

        .login-title {
            color: var(--evsu-red);
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
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

        .btn-login {
            background-color: var(--evsu-red);
            color: white;
            border: none;
            height: 50px;
            transition: background-color 0.3s ease;
        }

        .btn-login:hover {
            background-color: #6B0000;
        }

        .forgot-password a {
            color: var(--evsu-red);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .alert {
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <div class="d-flex justify-content-center align-items-center min-vh-100">
        <div class="login-container">
            <div class="logo-container">
                <img src="../assets/images/logo.png" alt="EVSU Logo" class="logo w-25">
            </div>

            <h2 class="login-title">EVSU - RESERVE</h2>
            <p class="login-subtitle">Streamlining Inventory and Reservations</p>

            <?php
            if (isset($_GET['error'])) {
                switch ($_GET['error']) {
                    case 'invalid_credentials':
                        echo '<div id="error-alert" class="alert alert-danger">Invalid username or password.</div>';
                        break;
                    case 'invalid_role':
                        echo '<div id="error-alert" class="alert alert-danger">Invalid user role.</div>';
                        break;
                }
            }
            ?>

            <form action="login_process.php" method="post">
                <div class="mb-3">
                    <input type="text" class="form-control" name="username" placeholder="Email or Username" required>
                </div>
                <div class="mb-3">
                    <input type="password" class="form-control" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn btn-login w-100">Login</button>

                <div class="forgot-password mt-2 d-flex justify-content-between">
                    <a href="#">Create account?</a>
                    <a href="#">Forgot Password?</a>
                </div>

            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const errorAlert = document.getElementById('error-alert');
            if (errorAlert) {
                setTimeout(() => {
                    errorAlert.classList.add('fade');
                    setTimeout(() => {
                        errorAlert.remove();
                    }, 700);
                }, 2000);
            }
        });
    </script>
</body>

</html>