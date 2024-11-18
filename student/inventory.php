<?php
session_start();
include '../config/database.php';
include '../includes/header.php';

if (isset($_SESSION['payment_status'])) {
    $status = $_SESSION['payment_status'];
    $messageClass = ($status === 'success') ? 'alert-success' : 'alert-danger';
    $messageText = ($status === 'success') ? 'Payment successful! Your reservation has been confirmed.' : 'Payment failed. Please try again.';
    unset($_SESSION['payment_status']);
}

$query = "
    SELECT i.sku, i.name, i.description, i.quantity, i.added_by_username, i.updated_at, i.amount, i.item_id AS item_id
    FROM inventory i
    ORDER BY i.name
";

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVSU-RESERVE</title>
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

        .search-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            display: flex;
            padding: 15px;

        }

        .inventory-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: var(--primary-red);
            color: white;
            border: none;
        }

        .table tbody tr:hover {
            background-color: rgba(139, 0, 0, 0.05);
        }

        .btn-primary {
            background-color: var(--primary-red);
            border-color: var(--primary-red);
        }

        .btn-primary:hover {
            background-color: #660000;
            border-color: #660000;
        }

        .btn-outline-secondary {
            color: var(--primary-red);
            border-color: var(--primary-red);
        }

        .btn-outline-secondary:hover {
            background-color: var(--primary-red);
            color: white;
        }

        .modal-header {
            background-color: var(--primary-red);
            color: white;
        }

        .modal-header .btn-close {
            color: white;
        }

        .badge {
            padding: 8px 12px;
            border-radius: 15px;
        }

        .alert {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }


        /* Main Content Styles */
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
            <a href="../auth/Logout.php" class="nav-button" style="margin-top: auto;"><i
                    class="fas fa-sign-out-alt"></i> Exit</a>
        </div>

        <!-- Main Content -->
        <div class="main-content">

            <div class="page-header d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">Inventory Management</h1>
            </div>
            <?php if (isset($messageText)): ?>
                <div class="alert <?php echo $messageClass; ?> alert-dismissible fade show" role="alert">
                    <?php echo $messageText; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="search-card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-7">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search by name or SKU"
                                    name="search" value="<?php echo $_GET['search'] ?? ''; ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="inventory-card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Quantity</th>
                                    <th>Amount</th>
                                    <th>Added By</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['sku']); ?></td>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                                        <td>
                                            <span
                                                class="badge bg-<?php echo $row['quantity'] > 0 ? 'success' : 'danger'; ?>">
                                                <?php echo $row['quantity']; ?> available
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['amount']); ?> PHP</td>
                                        <td><?php echo htmlspecialchars($row['added_by_username']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($row['updated_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-success"
                                                    onclick="reserveItem(<?php echo $row['item_id']; ?>, <?php echo $row['amount']; ?>, '<?php echo htmlspecialchars($row['name']); ?>')">
                                                    <i class="bi bi-bookmark"></i> Reserve
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reservation Payment Modal -->
    <div class="modal fade" id="reservationModal" tabindex="-1" aria-labelledby="reservationModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reservationModalLabel">Reservation Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="paymentForm">
                        <input type="hidden" id="itemId" value="">
                        <h6 class="mb-4" id="itemName"></h6>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" min="1" value="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Total Amount (PHP)</label>
                            <input type="text" class="form-control" id="amount" readonly>
                        </div>
                        <div id="error" class="alert alert-danger d-none"></div>
                        <button type="submit" class="btn btn-primary w-100" id="payButton">
                            Proceed to Payment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://js.paymongo.com/v1/payment.js"></script>

    <script>
        let reservationModal;
        let currentItemAmount = 0;
        let currentItemId = 0;

        document.addEventListener('DOMContentLoaded', function () {
            // Initialize the modal
            reservationModal = new bootstrap.Modal(document.getElementById('reservationModal'));

            // Quantity change handler
            document.getElementById('quantity').addEventListener('input', function (e) {
                const quantity = parseInt(e.target.value) || 0;
                const totalAmount = quantity * currentItemAmount;
                document.getElementById('amount').value = totalAmount.toFixed(2) + ' PHP';
            });

            // Form submission handler
            document.getElementById('paymentForm').addEventListener('submit', async function (e) {
                e.preventDefault();
                const payButton = document.getElementById('payButton');
                const errorDiv = document.getElementById('error');
                const quantityInput = document.getElementById('quantity');

                // Basic validation
                if (!quantityInput.value || quantityInput.value < 1) {
                    errorDiv.textContent = 'Please enter a valid quantity';
                    errorDiv.classList.remove('d-none');
                    return;
                }

                payButton.disabled = true;
                payButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
                errorDiv.classList.add('d-none');

                try {
                    const quantity = parseInt(quantityInput.value);
                    const amount = currentItemAmount * quantity;

                    const response = await fetch('create_payment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            itemId: currentItemId,
                            quantity: quantity,
                            amount: amount
                        })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.error || 'Payment initialization failed');
                    }

                    if (data.success && data.checkoutUrl) {
                        // Save form data to session storage before redirect
                        sessionStorage.setItem('lastPaymentAttempt', JSON.stringify({
                            itemId: currentItemId,
                            quantity: quantity,
                            amount: amount,
                            timestamp: new Date().toISOString()
                        }));

                        // Redirect to PayMongo checkout URL
                        window.location.href = data.checkoutUrl;
                    } else {
                        throw new Error(data.error || 'Failed to create payment session');
                    }
                } catch (error) {
                    errorDiv.textContent = error.message;
                    errorDiv.classList.remove('d-none');
                    payButton.disabled = false;
                    payButton.textContent = 'Proceed to Payment';
                }
            });

            // Handle payment status messages
            const alertElement = document.querySelector('.alert');
            if (alertElement) {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alertElement);
                    bsAlert.close();
                }, 5000);
            }
        });

        // Function to open the reservation modal
        function reserveItem(itemId, itemAmount, itemName) {
            currentItemId = itemId;
            currentItemAmount = itemAmount;

            document.getElementById('itemId').value = itemId;
            document.getElementById('itemName').textContent = 'Reserve Item: ' + itemName;
            document.getElementById('quantity').value = 1;
            document.getElementById('amount').value = itemAmount.toFixed(2) + ' PHP';

            // Reset error message and button state
            document.getElementById('error').classList.add('d-none');
            const payButton = document.getElementById('payButton');
            payButton.disabled = false;
            payButton.textContent = 'Proceed to Payment';

            reservationModal.show();
        }
    </script>

</body>

</html>