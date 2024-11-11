<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: ../auth/Login.php");
    exit();
}
include('../includes/header.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVSU-RESERVE Student Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

        /* Sidebar Styles */
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

        /* Main Content Styles */
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

        .creation-date {
            color: #666;
            font-size: 14px;
            margin-top: 8px;
        }

        .accordion {
            margin-bottom: 30px;
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
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">EVSU-RESERVE - STUDENT</div>
            <a href="dashboard.php" class="nav-button">📊 Dashboard</a>
            <a href="inventory.php" class="nav-button">📦 Inventory</a>
            <a href="reservation.php" class="nav-button">📅 My Reservation</a>
            <a href="payment_history.php" class="nav-button">💰 Payment History</a>
            <a href="support.php" class="nav-button">📞 Support</a>
            <a href="../auth/Logout.php" class="nav-button" style="margin-top: auto;">🚪 Exit</a>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            
        <div class="accordion" id="accordionPanelsStayOpenExample">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                            data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true"
                            aria-controls="panelsStayOpen-collapseOne">
                            Recent Announcements
                        </button>
                    </h2>
                    <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse show">
                        <div class="accordion-body">
                            <strong>Important Update:</strong>
                            <p>Welcome to the new EVSU-RESERVE system! We've made several improvements to enhance your reservation experience:</p>
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
                <h1 class="welcome-header">Welcome <?php echo htmlspecialchars($_SESSION['username'] ?? 'Student'); ?>!</h1>
                <p class="creation-date">Created at: February 19, 2023</p>
            </div>


            <div class="newly-added-section">
                <h2 class="newly-added-header">Newly Added Items</h2>
                <div class="items-grid">
                    <div class="item-card">
                        <h3>Laboratory Equipment</h3>
                        <img src="/api/placeholder/400/320" alt="Laboratory Equipment">
                        <p>New microscope set available for reservation</p>
                        <p><strong>Status:</strong> Available</p>
                    </div>
                    <div class="item-card">
                        <h3>Audio Visual Equipment</h3>
                        <img src="/api/placeholder/400/320" alt="Audio Visual Equipment">
                        <p>Professional projector with 4K resolution</p>
                        <p><strong>Status:</strong> Available</p>
                    </div>
                    <div class="item-card">
                        <h3>Sports Equipment</h3>
                        <img src="/api/placeholder/400/320" alt="Sports Equipment">
                        <p>New volleyball and basketball sets</p>
                        <p><strong>Status:</strong> Available</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>