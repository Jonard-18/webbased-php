<?php
session_start();
include('../config/database.php');
include('../includes/header.php');

// Query to fetch reservations with inventory details and payment status
$query = "
    SELECT r.reservation_id, r.item_id, i.sku, i.name, r.reserved_quantity AS quantity,
           r.reserved_at AS reservation_date, r.status, p.payment_status
    FROM reservations r
    LEFT JOIN inventory i ON r.item_id = i.item_id
    LEFT JOIN payments p ON r.reservation_id = p.reservation_id
    WHERE r.user_id = {$_SESSION['user_id']}
";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        .container {
            margin-top: 50px;
        }
        .table thead th {
            background-color: #8B0000;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">My Reservations</h1>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>SKU</th>
                        <th>Quantity</th>
                        <th>Reservation Date</th>
                        <th>Status</th>
                        <th>Payment Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['sku']); ?></td>
                            <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($row['reservation_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td><?php echo htmlspecialchars($row['payment_status'] ?? 'Unpaid'); ?></td>
                            <td>
                                <div class="btn-group">
                                    <?php if ($row['status'] === 'Pending'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="payReservation(<?php echo $row['reservation_id']; ?>)">
                                            <i class="bi bi-wallet"></i> Pay
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="cancelReservation(<?php echo $row['reservation_id']; ?>)">
                                            <i class="bi bi-x-circle"></i> Cancel
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

?>
