<?php
session_start();
include('../config/database.php');
include('../includes/header.php');


// Fetch inventory items with added_by username
$query = "SELECT i.*, u.username as added_by_name 
          FROM inventory i 
          LEFT JOIN users u ON i.added_by = u.user_id";

if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $query .= " WHERE i.name LIKE '%$search%' OR i.sku LIKE '%$search%'";
}

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/inventory.css">
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

        <div class="main-content">
            <div class="page-header d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">Inventory Management</h1>
            </div>

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
                                        <td><?php echo htmlspecialchars($row['added_by_name']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($row['updated_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-success"
                                                    onclick="reserveItem(<?php echo $row['item_id']; ?>)">
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
                    <!-- Hidden input for item ID -->
                    <input type="hidden" id="itemId" value="">

                    <h6 class="mb-4">Secure Payment</h6>

                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quantity" placeholder="Enter quantity" min="1" value="1" required>
                    </div>

                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount (PHP)</label>
                        <input type="number" class="form-control" id="amount" placeholder="Enter amount (e.g. 100)" min="1" step="1" required readonly>
                    </div>

                    <div id="error" class="alert alert-danger d-none"></div>

                    <button type="button" class="btn btn-primary w-100" onclick="processPayment()" id="payButton">
                        Pay Securely
                    </button>

                    <div class="mt-3 text-muted small text-center">
                        This is a test payment page. Use test cards or test wallet accounts.
                    </div>
                </div>
            </div>
        </div>
    </div>



    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let reservationModal;

        document.addEventListener('DOMContentLoaded', function () {
            reservationModal = new bootstrap.Modal(document.getElementById('reservationModal'));
        });

        function reserveItem(itemId) {
            document.getElementById('itemId').value = itemId;

            document.getElementById('error').classList.add('d-none');
            document.getElementById('amount').value = '';
            document.getElementById('payButton').disabled = false;
            document.getElementById('payButton').textContent = 'Pay Securely';

            reservationModal.show();
        }

        async function processPayment() {
            const quantity = document.getElementById('quantity').value;
            const itemId = document.getElementById('itemId').value;
            const amountPerItem = 100;
            const totalAmount = quantity * amountPerItem;
            document.getElementById('amount').value = totalAmount;
            const errorDiv = document.getElementById('error');
            const payButton = document.getElementById('payButton');

            errorDiv.classList.add('d-none');
            errorDiv.textContent = '';

            if (!quantity || quantity <= 0) {
                errorDiv.textContent = 'Please enter a valid quantity';
                errorDiv.classList.remove('d-none');
                return;
            }

            try {
                payButton.disabled = true;
                payButton.textContent = 'Processing...';

                const response = await fetch('../payment/process-payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        amount: amountPerItem,
                        itemId: itemId,
                        quantity: quantity
                    })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    window.location.href = data.checkoutUrl;
                } else {
                    throw new Error(data.error || 'Payment failed. Please try again.');
                }
            } catch (error) {
                console.error('Payment error:', error);
                errorDiv.textContent = error.message || 'An error occurred. Please try again.';
                errorDiv.classList.remove('d-none');
                payButton.disabled = false;
                payButton.textContent = 'Pay Securely';
            }
        }
    </script>
</body>

</html>