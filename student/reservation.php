<?php
session_start();
include('../config/database.php');
include('../includes/header.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: ../auth/Login.php");
    exit();
}

// Initialize message variables
$success_message = '';
$error_message = '';

// Handle reservation cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reservation'])) {
    $reservation_id = intval($_POST['reservation_id']);

    // Fetch the reserved quantity and item_id before canceling
    $fetch_query = "SELECT item_id, reserved_quantity FROM reservations WHERE reservation_id = ? AND user_id = ?";
    $stmt = $conn->prepare($fetch_query);
    $stmt->bind_param("ii", $reservation_id, $_SESSION['user_id']);
    $stmt->execute();
    $fetch_result = $stmt->get_result();
    
    if ($fetch_result->num_rows > 0) {
        $reservation_data = $fetch_result->fetch_assoc();
        $item_id = $reservation_data['item_id'];
        $reserved_quantity = $reservation_data['reserved_quantity'];

        // Update the reservation status to 'Cancelled'
        $update_query = "UPDATE reservations SET status = 'Cancelled' WHERE reservation_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $reservation_id);
        
        if ($stmt->execute()) {
            // Add the quantity back to the inventory
            $inventory_update_query = "UPDATE inventory SET quantity = quantity + ? WHERE item_id = ?";
            $stmt = $conn->prepare($inventory_update_query);
            $stmt->bind_param("ii", $reserved_quantity, $item_id);
            if ($stmt->execute()) {
                $success_message = "Reservation cancelled successfully. Please note that the reservation fee is non-refundable.";
            } else {
                $error_message = "Reservation cancelled, but failed to update inventory.";
            }
        } else {
            $error_message = "Error cancelling reservation. Please try again later.";
        }
    } else {
        $error_message = "Reservation not found or you do not have permission to cancel it.";
    }
}

// Query to fetch reservations with inventory details and payment status
$query = "
    SELECT r.reservation_id, r.item_id, i.sku, i.name, r.reserved_quantity AS quantity,
           r.reserved_at AS reservation_date, r.status, p.amount
    FROM reservations r
    LEFT JOIN inventory i ON r.item_id = i.item_id
    LEFT JOIN payments p ON r.reservation_id = p.reservation_id
    WHERE r.user_id = ?
    ORDER BY r.reserved_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVSU-RESERVE Student Dashboard</title>
    <!-- Bootstrap CSS -->
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

        .page-header {
            margin-bottom: 32px;
            padding-bottom: 16px;
            border-bottom: 2px solid rgba(139, 0, 0, 0.1);
        }

        .page-title {
            color: var(--primary-red);
            font-size: 2rem;
            font-weight: 700;
        }

        /* Table Styles */
        .table-container {
            background: white;
            border-radius: 8px; /* Adjusted from var(--border-radius) */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 24px;
            margin-top: 20px;
        }

        .table {
            margin-bottom: 0;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table thead th {
            background-color: rgba(139, 0, 0, 0.05);
            color: var(--primary-red);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 16px;
            border-bottom: 2px solid rgba(139, 0, 0, 0.1);
        }

        .table tbody td {
            padding: 16px;
            vertical-align: middle;
            color: #444;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table tbody tr:hover {
            background-color: rgba(255, 215, 0, 0.05);
        }

        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
            text-align: center;
            min-width: 100px;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-fulfilled {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .payment-completed {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .payment-unpaid {
            background-color: #f8d7da;
            color: #842029;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 16px;
            color: #dee2e6;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 16px;
            }

            .nav-button {
                width: 95%;
            }

            .table-container {
                overflow-x: auto;
            }

            .status-badge {
                white-space: nowrap;
                min-width: auto;
            }

            .table td,
            .table th {
                min-width: 120px;
            }

            .table td:first-child,
            .table th:first-child {
                position: sticky;
                left: 0;
                background: white;
                z-index: 1;
            }
        }

        /* Loading State */
        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }

        .loading-spinner::after {
            content: "";
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-red);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">EVSU-RESERVE - STUDENT</div>
            <a href="dashboard.php" class="nav-button"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="inventory.php" class="nav-button"><i class="fas fa-box"></i> Inventory</a>
            <a href="reservation.php" class="nav-button"><i class="fas fa-calendar-alt"></i> My Reservation</a>
            <a href="payment_history.php" class="nav-button"><i class="fas fa-money-bill-wave"></i> Payment History</a>
            <a href="support.php" class="nav-button"><i class="fas fa-headset"></i> Support</a>
            <a href="../auth/Logout.php" class="nav-button" style="margin-top: auto;"><i class="fas fa-sign-out-alt"></i> Exit</a>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">My Reservations</h1>
            </div>

            <!-- Display Success or Error Messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <?php if (!$result): ?>
                    <div class="alert alert-danger" role="alert">
                        Error loading reservations. Please try again later.
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>SKU</th>
                                <th>Quantity</th>
                                <th>Reservation Date</th>
                                <th>Pickup Status</th>
                                <th>Amount</th>
                                <th>Action</th> <!-- Added Action column -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    // Format the date
                                    $reservation_date = date('M d, Y H:i', strtotime($row['reservation_date']));
                                    
                                    // Determine status badge classes
                                    $status_class = '';
                                    switch($row['status']) {
                                        case 'Pending':
                                            $status_class = 'status-pending';
                                            break;
                                        case 'Fulfilled':
                                            $status_class = 'status-fulfilled';
                                            break;
                                        case 'Cancelled':
                                            $status_class = 'status-cancelled';
                                            break;
                                    }

                                    // Prepare the action button based on the status
                                    $action_button = '';
                                    if ($row['status'] === 'Pending') {
                                        $action_button = "<button class='btn btn-danger btn-sm' 
                                                            data-bs-toggle='modal' 
                                                            data-bs-target='#cancelConfirmationModal' 
                                                            data-reservation-id='" . htmlspecialchars($row['reservation_id']) . "'>
                                                            Cancel
                                                        </button>";
                                    }

                                    echo "<tr>
                                        <td>" . htmlspecialchars($row['name']) . "</td>
                                        <td>" . htmlspecialchars($row['sku']) . "</td>
                                        <td>" . htmlspecialchars($row['quantity']) . "</td>
                                        <td>{$reservation_date}</td>
                                        <td><span class='status-badge {$status_class}'>" . 
                                            htmlspecialchars($row['status']) . "</span></td>
                                        <td>" . htmlspecialchars($row['amount']) . "</td>
                                        <td>{$action_button}</td> <!-- Conditional action button -->
                                    </tr>";
                                }
                            } else {
                                echo "<tr>
                                    <td colspan='7' class='empty-state'>
                                        <i class='fas fa-calendar-times'></i>
                                        <p>No reservations found</p>
                                    </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Cancellation Confirmation Modal -->
    <div class="modal fade" id="cancelConfirmationModal" tabindex="-1" aria-labelledby="cancelConfirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="cancelReservationForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancelConfirmationModalLabel">Confirm Cancellation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to cancel this reservation? Please note that the reservation fee is non-refundable.
                        <input type="hidden" name="reservation_id" id="modalReservationId" value="">
                        <input type="hidden" name="cancel_reservation" value="1">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger">Cancel Reservation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle passing reservation ID to the modal form
        var cancelConfirmationModal = document.getElementById('cancelConfirmationModal');
        cancelConfirmationModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Button that triggered the modal
            var reservationId = button.getAttribute('data-reservation-id'); // Extract info from data-* attributes

            // Update the hidden input with the reservation ID
            var modalReservationIdInput = cancelConfirmationModal.querySelector('#modalReservationId');
            modalReservationIdInput.value = reservationId;
        });
    </script>
</body>

</html>