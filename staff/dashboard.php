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

    $low_stock_query = "SELECT COUNT(*) as low_stock FROM inventory WHERE quantity < 10";
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
    <!-- [Keep your existing head content] -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVSU-RESERVE Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* [Keep your existing styles] */
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
        .low-stock { background-color: #ffdddd; }
        .zero-stock { background-color: #ff9999; }

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
            background-color: #ffffff;
        }

                /* New Card Styles */
      /* Card Styles */
.dashboard-card {
    background: white;
    border-radius: 15px;
    padding: 28px;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 20px;
    border: 1px solid rgba(139, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
}

.card-header {
    display: flex;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid rgba(139, 0, 0, 0.1);
}

.card-header i {
    font-size: 18px;
    margin-right: 16px;
    color: #8B0000;
    background: rgba(139, 0, 0, 0.1);
    padding: 12px;
    border-radius: 12px;
}

.card-header h3 {
    font-size: 17px;
    margin: 0;
    color: #333;
    font-weight: 600;
}

.status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    margin: 12px 0;
    border-radius: 12px;
    font-size: 16px;
    transition: all 0.2s ease;
}

.status-item:hover {
    transform: translateX(5px);
}

.status-item.default {
    background: #f8f9fa;
    border-left: 4px solid #6c757d;
}

.status-item.warning {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
}

.status-item.danger {
    background: #ffe0e0;
    border-left: 4px solid #dc3545;
}

.status-item.info {
    background: #e7f1ff;
    border-left: 4px solid #0d6efd;
}

.status-item.success {
    background: #e8f5e9;
    border-left: 4px solid #198754;
}

.status-number {
    font-weight: 700;
    font-size: 20px;
    padding: 6px 12px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.5);
}
    </style>
</head>
<body>
    <div class="dashboard-container">
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


        <div class="main-content container-fluid">
            <h2 class="my-4">Dashboard</h2>

            <div class="row">
                <!-- Inventory Status Card -->
                <div class="col-md-6">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <i class="fas fa-box"></i>
                            <h3>Inventory Status</h3>
                        </div>
                        <div class="status-list">
                            <div class="status-item default">
                                <span>Total Items</span>
                                <span class="status-number"><?php echo $inventory_stats['total_items']; ?></span>
                            </div>
                            <div class="status-item warning">
                                <span>Low Stock Items</span>
                                <span class="status-number"><?php echo $inventory_stats['low_stock_items']; ?></span>
                            </div>
                            <div class="status-item danger">
                                <span>Out of Stock Items</span>
                                <span class="status-number"><?php echo $inventory_stats['out_of_stock_items']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reservation Status Card -->
                <div class="col-md-6">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <i class="fas fa-calendar-check"></i>
                            <h3>Reservation Status</h3>
                        </div>
                        <div class="status-list">
                            <div class="status-item default">
                                <span>Total Reservations</span>
                                <span class="status-number"><?php echo $reservation_stats['total_reservations']; ?></span>
                            </div>
                            <div class="status-item info">
                                <span>Pending Reservations</span>
                                <span class="status-number"><?php echo $reservation_stats['pending_reservations']; ?></span>
                            </div>
                            <div class="status-item success">
                                <span>Fulfilled Reservations</span>
                                <span class="status-number"><?php echo $reservation_stats['fulfilled_reservations']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>

        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>