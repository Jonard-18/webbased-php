<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    session_unset();
    session_destroy();
    header("Location: ../auth/Login.php");
    exit();
}

if (!defined('APP_RUNNING')) {
    define('APP_RUNNING', true);
}

include_once('../includes/header.php');
require_once('../config/database.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $stmt = $conn->prepare("SELECT created_at, email FROM users WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("User not found");
    }

    $userData = $result->fetch_assoc();
    $formatted_date = date('F j, Y', strtotime($userData['created_at']));
    $email = htmlspecialchars($userData['email'], ENT_QUOTES, 'UTF-8');

    $stmt->close();
} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $formatted_date = 'Date unavailable';
    $email = 'Email unavailable';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVSU-RESERVE Student Dashboard</title>
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" as="style">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {
            --primary-red: #8B0000;
            --accent-yellow: #FFD700;
            --light-gray: #f5f5f5;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: var(--light-gray);
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: var(--primary-red);
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            color: var(--accent-yellow);
            text-align: center;
            padding: 15px;
            font-weight: bold;
            margin-bottom: 20px;
            font-size: 1.2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-button {
            display: block;
            width: 85%;
            margin: 10px auto;
            padding: 12px 15px;
            background-color: var(--accent-yellow);
            border: none;
            border-radius: 5px;
            text-align: left;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }

        .nav-button:hover {
            background-color: #FFC500;
            transform: translateX(5px);
        }

        .main-content {
            flex-grow: 1;
            margin-left: 250px;
            padding: 30px;
        }

        .welcome-section {
            background-color: white;
            padding: 25px;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .welcome-header {
            color: var(--primary-red);
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }

        .creation-date,
        .email-address {
            color: #666;
            font-size: 14px;
            margin: 0;
        }

        .accordion-button:not(.collapsed) {
            background-color: var(--primary-red);
            color: white;
        }

        .accordion-button:focus {
            box-shadow: 0 0 0 0.25rem rgba(139, 0, 0, 0.25);
        }

        .newly-added-section {
            margin-top: 30px;
        }

        .newly-added-header {
            color: var(--primary-red);
            margin-bottom: 20px;
            font-weight: bold;
        }

        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .item-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .item-card:hover {
            transform: translateY(-5px);
        }

        .item-card h3 {
            color: var(--primary-red);
            margin-bottom: 15px;
        }

        .item-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .item-card p {
            color: #666;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .main-content {
                margin-left: 200px;
            }

            .items-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .sidebar {
                display: none;
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="sidebar-header">EVSU-RESERVE - STUDENT</div>
            <a href="dashboard.php" class="nav-button"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="inventory.php" class="nav-button"><i class="fas fa-box"></i> Inventory</a>
            <a href="reservation.php" class="nav-button"><i class="fas fa-calendar-alt"></i> My Reservation</a>
            <a href="payment_history.php" class="nav-button"><i class="fas fa-money-bill-wave"></i> Payment History</a>
            <a href="support.php" class="nav-button"><i class="fas fa-headset"></i> Support</a>
            <a href="../auth/Logout.php" class="nav-button" style="margin-top: auto;"><i
                    class="fas fa-sign-out-alt"></i> Exit</a>
        </div>

        <div class="main-content">
            <div class="accordion mb-4">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                            data-bs-target="#panelsStayOpen-collapseOne">
                            Recent Announcements
                        </button>
                    </h2>
                    <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse show">
                        <div class="accordion-body">
                            <strong>Important Update:</strong>
                            <p>Welcome to the new EVSU-RESERVE system! We've made several improvements:</p>
                            <ul>
                                <li>Streamlined reservation process</li>
                                <li>Real-time inventory updates</li>
                                <li>Improved notification system</li>
                                <li>New payment integration options</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="welcome-section">
                <p class="creation-date">Created at: <?= $formatted_date ?></p>
                <h1 class="welcome-header">Welcome
                    <?= htmlspecialchars($_SESSION['username'] ?? 'Student', ENT_QUOTES, 'UTF-8') ?>!</h1>
                <p class="email-address"><?= $email ?></p>

                <div class="newly-added-section">
                    <h2 class="newly-added-header">Newly Added Items</h2>
                    <div class="items-grid">
                        <?php
                        try {
                            $inventoryStmt = $conn->prepare("SELECT name, description, quantity, amount, image_url FROM inventory WHERE deleted = FALSE ORDER BY created_at DESC LIMIT 3");
                            $inventoryStmt->execute();
                            $result = $inventoryStmt->get_result();

                            while ($row = $result->fetch_assoc()) {
                                echo "<div class='item-card'>";
                                if (!empty($row['image_url']) && file_exists("../" . $row['image_url'])) {
                                    echo "<img src='../" . htmlspecialchars($row['image_url'], ENT_QUOTES, 'UTF-8') . "' 
                                         alt='" . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . "'>";
                                } else {
                                    echo "<img src='../staff/uploads/" . basename($row['image_url']) . "' 
                                    alt='" . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . "'>";
                                }
                                echo "<h3>" . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . "</h3>";
                                echo "<p>" . htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8') . "</p>";
                                echo "<p><strong>Quantity:</strong> " . intval($row['quantity']) . "</p>";
                                echo "<p><strong>Price:</strong> ₱" . number_format($row['amount'], 2) . "</p>";
                                echo "<p><strong>Status:</strong> " .
                                    (intval($row['quantity']) > 0 ?
                                        '<span class="text-success">Available</span>' :
                                        '<span class="text-danger">Out of Stock</span>') . "</p>";
                                echo "</div>";
                            }
                            $inventoryStmt->close();
                        } catch (Exception $e) {
                            error_log("Inventory Fetch Error: " . $e->getMessage());
                            echo "<p class='text-danger'>Unable to load inventory items</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>