<?php
session_start();
include('../config/database.php');
include('../includes/header.php');

// Fetch payment history with item details through reservations
$user_id = $_SESSION['user_id'];
$query = "SELECT 
    p.payment_id,
    p.amount,
    p.payment_date,
    p.payment_status,
    i.name as item_name,
    r.reserved_quantity,
    r.status as reservation_status
    FROM payments p
    INNER JOIN reservations r ON p.reservation_id = r.reservation_id
    INNER JOIN inventory i ON r.item_id = i.item_id
    WHERE p.user_id = ?
    ORDER BY p.payment_date DESC";

if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVSU-RESERVE Payment History</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
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
            background-color: var(--light-gray);
        }

        .page-header {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
        }

        .payment-history-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: var(--primary-red);
            color: white;
            border: none;
            padding: 12px 15px;
        }

        .table tbody tr:hover {
            background-color: rgba(139, 0, 0, 0.05);
        }

        .badge {
            padding: 8px 12px;
            border-radius: 15px;
        }

        .payment-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-completed {
            background-color: #198754;
            color: white;
        }

        .status-failed {
            background-color: #dc3545;
            color: white;
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
            <!-- Page Header -->
            <div class="page-header d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">Payment History</h1>
            </div>

            <!-- Payment History Table Card -->
            <div class="payment-history-card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['reserved_quantity']); ?></td>
                                        <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                                        <td>
                                            <span class="payment-status <?php echo $row['payment_status'] === 'Completed' ? 'status-completed' : 'status-failed'; ?>">
                                                <?php echo htmlspecialchars($row['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($row['payment_date'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No payment history found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>