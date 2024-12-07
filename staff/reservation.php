<?php
require_once 'db_connect.php';
session_start();


if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Staff')) {
    header("Location: ../auth/Login.php");
    exit();
}
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_reservation'])) {
    $reservation_id = $_POST['reservation_id'];
    $action = $_POST['update_reservation'];

    if (!in_array($action, ['Pickup', 'Cancelled'])) {
        $error_message = "Invalid reservation action.";
    } else {
        $conn->begin_transaction();

        try {
            $reservation_query = "SELECT item_id, reserved_quantity FROM reservations WHERE reservation_id = ?";
            $stmt = $conn->prepare($reservation_query);
            $stmt->bind_param("i", $reservation_id);
            $stmt->execute();
            $reservation_result = $stmt->get_result()->fetch_assoc();

            if (!$reservation_result) {
                throw new Exception("Reservation not found.");
            }

            $item_id = $reservation_result['item_id'];
            $reserved_quantity = $reservation_result['reserved_quantity'];

            $update_reservation_query = "UPDATE reservations SET status = ? WHERE reservation_id = ?";
            $stmt = $conn->prepare($update_reservation_query);
            $status = ($action == 'Pickup') ? 'Fulfilled' : 'Cancelled';
            $stmt->bind_param("si", $status, $reservation_id);
            $stmt->execute();

            if ($action == 'Pickup') {
                $update_inventory_query = "UPDATE inventory SET quantity = quantity - ? WHERE item_id = ?";
                $stmt = $conn->prepare($update_inventory_query);
                $stmt->bind_param("ii", $reserved_quantity, $item_id);
                $stmt->execute();
            }

            $conn->commit();
            $success_message = "Reservation successfully " . strtolower($action) . ".";

        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error processing reservation: " . $e->getMessage();
        }
    }
}

$pending_query = "
    SELECT r.reservation_id, r.reserved_quantity, r.reserved_at, 
           i.name AS item_name, i.sku, 
           u.username, u.email
    FROM reservations r
    JOIN inventory i ON r.item_id = i.item_id
    JOIN users u ON r.user_id = u.user_id
    WHERE r.status = 'Pending'
    ORDER BY r.reserved_at ASC
";
$pending_result = $conn->query($pending_query);
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

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 20px;
            margin-bottom: 15px;
        }

        .stat-item {
            margin-bottom: 10px;
        }

        .low-stock {
            background-color: #ffdddd;
            padding: 5px;
            border-radius: 5px;
        }

        .zero-stock {
            background-color: #ff9999;
            padding: 5px;
            border-radius: 5px;
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
            <h2 class="my-4">Reservations</h2>
            <?php if(isset($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars(string: $success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if($pending_result->num_rows > 0): ?>
                    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-reservations">
                            <thead>
                                <tr>
                                    <th>Item Details</th>
                                    <th>Reservation Info</th>
                                    <th>Reserved By</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($reservation = $pending_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($reservation['item_name']); ?></strong>
                                            <br>
                                            <small class="text-muted">SKU: <?php echo htmlspecialchars($reservation['sku']); ?></small>
                                            <br>
                                            <span class="badge bg-info">Qty: <?php echo htmlspecialchars($reservation['reserved_quantity']); ?></span>
                                        </td>
                                        <td>
                                            <i class="far fa-clock text-primary"></i>
                                            <?php echo date('M d, Y H:i', strtotime($reservation['reserved_at'])); ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-user text-success"></i>
                                            <?php echo htmlspecialchars($reservation['username']); ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($reservation['email']); ?></small>
                                        </td>
                                        <td class="text-center reservation-actions">
                                            <form method="POST" action="" class="d-flex justify-content-center gap-2">
                                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['reservation_id']; ?>">
                                                <button type="submit" name="update_reservation" value="Pickup" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check-circle"></i> Pickup
                                                </button>
                                                <button type="submit" name="update_reservation" value="Cancelled" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-times-circle"></i> Cancel
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-reservations">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No Pending Reservations</h4>
                        <p class="text-secondary">There are currently no items waiting to be picked up or processed.</p>
                    </div>
                <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>