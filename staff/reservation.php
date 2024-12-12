<?php
require_once 'db_connect.php';
session_start();

// Redirect if user is not logged in or doesn't have the right role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header("Location: ../auth/Login.php");
    exit();
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Pickup and Cancellation Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Process Reservation Update (Pickup or Cancelled)
    if (isset($_POST['update_reservation'])) {
        $reservation_id = intval($_POST['reservation_id']);
        $action = $_POST['update_reservation'];

        if (!in_array($action, ['Pickup', 'Cancelled'])) {
            $error_message = "Invalid reservation action.";
        } else {
            $conn->begin_transaction();

            try {
                // Fetch reservation details
                $reservation_query = "SELECT r.reservation_id, r.item_id, r.reserved_quantity, r.user_id, r.reserved_at,
                i.name AS item_name, i.sku, i.amount AS price, 
                u.username, u.email
         FROM reservations r
         JOIN inventory i ON r.item_id = i.item_id
         JOIN users u ON r.user_id = u.user_id
         WHERE r.reservation_id = ?";
                $stmt = $conn->prepare($reservation_query);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("i", $reservation_id);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                $reservation_result = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$reservation_result) {
                    throw new Exception("Reservation not found.");
                }

                $item_id = $reservation_result['item_id'];
                $reserved_quantity = $reservation_result['reserved_quantity'];
                $username = $reservation_result['username'];
                $email = $reservation_result['email'];
                $item_name = $reservation_result['item_name'];
                $sku = $reservation_result['sku'];
                $price_per_unit = $reservation_result['price'];
                $total_amount = $reserved_quantity * $price_per_unit;

                // Update reservation status
                $update_reservation_query = "UPDATE reservations SET status = ? WHERE reservation_id = ?";
                $stmt = $conn->prepare($update_reservation_query);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $status = ($action == 'Pickup') ? 'Fulfilled' : 'Cancelled';
                $stmt->bind_param("si", $status, $reservation_id);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                $stmt->close();

                $conn->commit();

                if ($action == 'Pickup') {
                    // Prepare receipt data
                    $receipt = [
                        'Reservation ID' => $reservation_id,
                        'Item Name' => $item_name,
                        'SKU' => $sku,
                        'Quantity' => $reserved_quantity,
                        'Price per Unit' => format_currency($price_per_unit),
                        'Total Amount' => format_currency($total_amount),
                        'Reserved At' => date('M d, Y H:i', strtotime($reservation_result['reserved_at'])),
                        'Fulfilled By' => $_SESSION['username'],
                        'User Name' => $username,
                        'User Email' => $email
                    ];

                    // Store receipt data in session to display in modal
                    $_SESSION['receipt'] = $receipt;

                    $success_message = "Reservation successfully fulfilled. Receipt generated.";
                } else {
                    $update_inventory_query = "UPDATE inventory SET quantity = quantity + ? WHERE item_id = ?";
                    $stmt = $conn->prepare($update_inventory_query);
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $stmt->bind_param("ii", $reserved_quantity, $item_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Execute failed: " . $stmt->error);
                    }
                    $stmt->close();

                    $status = 'Cancelled';
                    $success_message = "Reservation successfully cancelled.";
                }

            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "Error processing reservation: " . $e->getMessage();
            }
        }
    }
}

// Fetch Pending Reservations
$pending_query = "
    SELECT r.reservation_id, r.reserved_quantity, r.reserved_at, 
           i.name AS item_name, i.sku, i.amount AS price,
           u.username, u.email
    FROM reservations r
    JOIN inventory i ON r.item_id = i.item_id
    JOIN users u ON r.user_id = u.user_id
    WHERE r.status = 'Pending'
    ORDER BY r.reserved_at ASC
";
$pending_result = $conn->query($pending_query);

if (!$pending_result) {
    die("Database query failed: " . $conn->error);
}

// Function to format currency
function format_currency($amount)
{
    return '₱' . number_format($amount, 2);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVSU-RESERVE Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* Custom Styles */
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

        .low-stock {
            background-color: #ffdddd;
        }

        .zero-stock {
            background-color: #ff9999;
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
            font-size: 1.5rem;
            font-weight: bold;
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
            font-size: 1rem;
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
            box-shadow: var(--soft-shadow);
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

        .table-reservations th,
        .table-reservations td {
            vertical-align: middle;
        }

        .reservation-actions form {
            margin: 0;
        }

        @media print {
            body * {
                visibility: hidden;
            }

            #receiptModal,
            #receiptModal * {
                visibility: visible;
            }

            #receiptModal {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .modal-footer {
                display: none !important;
            }

            .btn-close {
                display: none !important;
            }
        }

        /* Custom Receipt Styles */
        #receiptModal .modal-content {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        #receiptModal .receipt-details {
            font-size: 0.95rem;
        }

        #receiptModal .modal-body {
            background-color: #ffffff;
        }

        #receiptModal .btn {
            border-radius: 8px;
            padding: 8px 20px;
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
        <div class="main-content container-fluid">
            <h2 class="my-4">Pending Reservations</h2>

            <!-- Success Message -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Reservations Table -->
            <?php if ($pending_result->num_rows > 0): ?>
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-reservations table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Item Details</th>
                                <th>Reservation Info</th>
                                <th>Reserved By</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($reservation = $pending_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($reservation['item_name']); ?></strong>
                                        <br>
                                        <small class="text-muted">SKU:
                                            <?php echo htmlspecialchars($reservation['sku']); ?></small>
                                        <br>
                                        <span class="badge bg-info">Qty:
                                            <?php echo htmlspecialchars($reservation['reserved_quantity']); ?></span>
                                        <br>
                                        <span class="badge bg-secondary">Price:
                                            <?php echo format_currency($reservation['price']); ?></span>
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
                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal"
                                            data-bs-target="#pickupModal"
                                            data-reservation-id="<?php echo $reservation['reservation_id']; ?>"
                                            data-item-name="<?php echo htmlspecialchars($reservation['item_name']); ?>"
                                            data-sku="<?php echo htmlspecialchars($reservation['sku']); ?>"
                                            data-quantity="<?php echo htmlspecialchars($reservation['reserved_quantity']); ?>"
                                            data-price="<?php echo htmlspecialchars($reservation['price']); ?>"
                                            data-username="<?php echo htmlspecialchars($reservation['username']); ?>"
                                            data-email="<?php echo htmlspecialchars($reservation['email']); ?>"
                                            data-reserved-at="<?php echo htmlspecialchars($reservation['reserved_at']); ?>">
                                            <i class="fas fa-check-circle"></i> Pickup
                                        </button>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="reservation_id"
                                                value="<?php echo $reservation['reservation_id']; ?>">
                                            <button type="submit" name="update_reservation" value="Cancelled"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to cancel this reservation?');">
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
                <div class="empty-reservations text-center mt-5">
                    <i class="fas fa-inbox fa-5x text-muted mb-3"></i>
                    <h4 class="text-muted">No Pending Reservations</h4>
                    <p class="text-secondary">There are currently no items waiting to be picked up or processed.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pickup Modal -->
    <div class="modal fade" id="pickupModal" tabindex="-1" aria-labelledby="pickupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form method="POST" action="" id="pickupForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="pickupModalLabel">Process Pickup</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Reservation Details -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6>Item Details</h6>
                                <p><strong>Name:</strong> <span id="modalItemName"></span></p>
                                <p><strong>SKU:</strong> <span id="modalSku"></span></p>
                            </div>
                            <div class="col-md-6">
                                <h6>User Details</h6>
                                <p><strong>Name:</strong> <span id="modalUsername"></span></p>
                                <p><strong>Email:</strong> <span id="modalUserEmail"></span></p>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6>Reservation Info</h6>
                                <p><strong>Reserved At:</strong> <span id="modalReservedAt"></span></p>
                                <p><strong>Quantity:</strong> <span id="modalQuantity"></span></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Payment Details</h6>
                                <p><strong>Price per Unit:</strong> <span id="modalPrice"></span></p>
                                <p><strong>Total Amount:</strong> <span id="modalTotalAmount"></span></p>
                            </div>
                        </div>
                        <!-- Hidden Inputs -->
                        <input type="hidden" name="reservation_id" id="modalReservationId" value="">
                        <!-- Additional Notes (Optional) -->
                        <div class="mb-3">
                            <label for="notes" class="form-label">Additional Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"
                                placeholder="Enter any additional information here..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_reservation" value="Pickup" class="btn btn-success">Confirm
                            Pickup & Pay</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Receipt Modal -->
    <?php if (isset($_SESSION['receipt'])): ?>
        <div class="modal fade show" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true"
            style="display: block;">
            <div class="modal-dialog modal-md">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <!-- Close button redirects to reservation.php to prevent modal from showing again -->
                        <button type="button" class="btn-close" onclick="window.location.href='reservation.php'"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <!-- Logo -->
                        <div class="text-center mb-4">
                            <img src="logo.png" alt="EVSU Logo" class="img-fluid" style="max-width: 120px;">
                        </div>

                        <!-- Receipt Header -->
                        <div class="text-center mb-4">
                            <h4 class="fw-bold mb-1">EVSU-RESERVE</h4>
                            <p class="text-muted small mb-1">Eastern Visayas State University</p>
                            <p class="text-muted small mb-3">Reservation Receipt</p>
                            <div class="border-bottom w-100 mb-4"></div>
                        </div>

                        <!-- Receipt Details -->
                        <div class="receipt-details">
                            <?php foreach ($_SESSION['receipt'] as $key => $value): ?>
                                <div class="row mb-2">
                                    <div class="col-5">
                                        <span class="text-muted"><?php echo htmlspecialchars($key); ?></span>
                                    </div>
                                    <div class="col-7">
                                        <span class="fw-medium"><?php echo htmlspecialchars($value); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Thank You Message -->
                        <div class="text-center mt-4">
                            <div class="border-top w-100 mb-4"></div>
                            <p class="mb-1">Thank you for your payment!</p>
                            <p class="text-muted small">Please keep this receipt for your records.</p>
                        </div>
                    </div>
                    <div class="modal-footer border-0 justify-content-center">
                        <button type="button" class="btn btn-secondary me-2"
                            onclick="window.location.href='reservation.php'">Close</button>
                        <button type="button" class="btn btn-primary" onclick="window.print();">
                            <i class="fas fa-print me-2"></i>Print Receipt
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        // Clear the receipt session after displaying the modal to prevent it from showing again
        unset($_SESSION['receipt']);
        ?>
    <?php endif; ?>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Populate Pickup Modal with Reservation Data
        var pickupModal = document.getElementById('pickupModal');
        pickupModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;

            // Extract data from button attributes
            var reservationId = button.getAttribute('data-reservation-id');
            var itemName = button.getAttribute('data-item-name');
            var sku = button.getAttribute('data-sku');
            var quantity = button.getAttribute('data-quantity');
            var price = parseFloat(button.getAttribute('data-price'));
            var username = button.getAttribute('data-username');
            var userEmail = button.getAttribute('data-email');
            var reservedAt = button.getAttribute('data-reserved-at');

            // Calculate total amount
            var totalAmount = quantity * price;

            // Update modal fields
            document.getElementById('modalReservationId').value = reservationId;
            document.getElementById('modalItemName').textContent = itemName;
            document.getElementById('modalSku').textContent = sku;
            document.getElementById('modalQuantity').textContent = quantity;
            document.getElementById('modalPrice').textContent = formatCurrency(price);
            document.getElementById('modalTotalAmount').textContent = formatCurrency(totalAmount);
            document.getElementById('modalUsername').textContent = username;
            document.getElementById('modalUserEmail').textContent = userEmail;
            document.getElementById('modalReservedAt').textContent = new Date(reservedAt).toLocaleString();
        });

        // Function to format number as currency
        function formatCurrency(amount) {
            return '₱' + amount.toFixed(2);
        }
    </script>
</body>

</html>