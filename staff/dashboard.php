<?php
require_once 'db_connect.php';
session_start();
// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Staff')) {
    header("Location: ../auth/Login.php");
    exit();
}
function getInventoryStatistics($conn) {
    $stats = [
        'total_items' => 0,
        'low_stock_items' => 0,
        'out_of_stock_items' => 0
    ];

    $total_query = "SELECT COUNT(*) as total FROM inventory";
    $total_result = $conn->query($total_query);
    $stats['total_items'] = $total_result->fetch_assoc()['total'];

    $low_stock_query = "SELECT COUNT(*) as low_stock FROM inventory WHERE quantity < 5";
    $low_stock_result = $conn->query($low_stock_query);
    $stats['low_stock_items'] = $low_stock_result->fetch_assoc()['low_stock'];

    $out_of_stock_query = "SELECT COUNT(*) as out_of_stock FROM inventory WHERE quantity = 0";
    $out_of_stock_result = $conn->query($out_of_stock_query);
    $stats['out_of_stock_items'] = $out_of_stock_result->fetch_assoc()['out_of_stock'];

    return $stats;
}

function getReservationStatistics($conn) {
    $stats = [
        'total_reservations' => 0,
        'pending_reservations' => 0,
        'fulfilled_reservations' => 0
    ];

    $total_query = "SELECT COUNT(*) as total FROM reservations";
    $total_result = $conn->query($total_query);
    $stats['total_reservations'] = $total_result->fetch_assoc()['total'];

    $pending_query = "SELECT COUNT(*) as pending FROM reservations WHERE status = 'Pending'";
    $pending_result = $conn->query($pending_query);
    $stats['pending_reservations'] = $pending_result->fetch_assoc()['pending'];

    $fulfilled_query = "SELECT COUNT(*) as fulfilled FROM reservations WHERE status = 'Fulfilled'";
    $fulfilled_result = $conn->query($fulfilled_query);
    $stats['fulfilled_reservations'] = $fulfilled_result->fetch_assoc()['fulfilled'];

    return $stats;
}

// Get statistics
$inventory_stats = getInventoryStatistics($conn);
$reservation_stats = getReservationStatistics($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVSU-RESERVE Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-red: #8B0000;
            --accent-yellow: #FFD700;
            --light-gray: #f4f6f9;
            --white: #ffffff;
            --soft-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            background-color: var(--light-gray);
            color: #333;
        }

        .dashboard-container {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, var(--primary-red), #6D0000);
            padding: 20px 0;
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-yellow);
            padding: 15px;
            margin-bottom: 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header img {
            height: 40px;
            margin-right: 10px;
        }

        .nav-button {
            display: flex;
            align-items: center;
            width: 85%;
            margin: 10px auto;
            padding: 12px 15px;
            background-color: rgba(255, 215, 0, 0.1);
            border-radius: 8px;
            color: var(--white);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .nav-button:hover {
            background-color: var(--accent-yellow);
            color: var(--primary-red);
            transform: translateX(10px);
        }

        .nav-button i {
            margin-right: 10px;
            opacity: 0.8;
        }

        .main-content {
            flex-grow: 1;
            overflow-y: auto;
            padding: 30px;
            background-color: var(--light-gray);
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--soft-shadow);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: scale(1.02);
        }

        .card-header {
            background: linear-gradient(45deg, #007bff, #3498db);
            color: white;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            display: flex;
            align-items: center;
        }

        .card-header i {
            margin-right: 10px;
        }

        .statistic-value {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .statistic-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                EVSU-RESERVE
            </div>
            <a href="dashboard.php" class="nav-button">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="inventory.php" class="nav-button">
                <i class="fas fa-box"></i> Inventory
            </a>
            <a href="reservation.php" class="nav-button">
                <i class="fas fa-calendar-alt"></i> Reservations
            </a>
            <a href="../auth/Logout.php" class="nav-button" style="margin-top: auto;">
                <i class="fas fa-sign-out-alt"></i> Exit
            </a>

        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="container-fluid">
                <h1 class="mb-4">Dashboard Overview</h1>
                
                <div class="row">
                    <!-- Inventory Statistics Card -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-box"></i>Inventory Statistics
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="statistic-value"><?php echo $inventory_stats['total_items']; ?></div>
                                        <div class="statistic-label">Total Items</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="statistic-value text-warning"><?php echo $inventory_stats['low_stock_items']; ?></div>
                                        <div class="statistic-label">Low Stock</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="statistic-value text-danger"><?php echo $inventory_stats['out_of_stock_items']; ?></div>
                                        <div class="statistic-label">Out of Stock</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reservation Statistics Card -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success">
                                <i class="fas fa-calendar-alt"></i>Reservation Statistics
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="statistic-value"><?php echo $reservation_stats['total_reservations']; ?></div>
                                        <div class="statistic-label">Total Reservations</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="statistic-value text-warning"><?php echo $reservation_stats['pending_reservations']; ?></div>
                                        <div class="statistic-label">Pending</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="statistic-value text-success"><?php echo $reservation_stats['fulfilled_reservations']; ?></div>
                                        <div class="statistic-label">Fulfilled</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>