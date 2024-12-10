<?php
session_start();
include '../config/database.php';
include '../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: ../auth/Login.php");
    exit();
}

if (isset($_SESSION['payment_status'])) {
    $status = $_SESSION['payment_status'];
    $messageClass = ($status === 'success') ? 'alert-success' : 'alert-danger';
    $messageText = ($status === 'success') ? 'Payment successful! Your reservation has been confirmed.' : 'Payment failed. Please try again.';
    unset($_SESSION['payment_status']);
}

$search = "";
if (isset($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
}

$query = "
    SELECT 
    i.sku, 
    i.name, 
    i.description, 
    i.quantity, 
    i.updated_at, 
    i.amount, 
    i.item_id AS item_id, 
    i.image_url
FROM 
    inventory i
WHERE 
    i.deleted = FALSE 
    AND i.quantity != 0";

if (!empty($search)) {
    $query .= " AND (i.name LIKE '%$search%' OR i.sku LIKE '%$search%')";
}

$query .= " ORDER BY i.name";

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
            --card-transition: transform 0.3s ease, box-shadow 0.3s ease;
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

        /* Inventory Cards Styles */
        .inventory-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .inventory-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: var(--card-transition);
            display: flex;
            flex-direction: column;
        }

        .inventory-card:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        /* New Image Container with Badge */
        .image-container {
            position: relative;
        }

        .image-container img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            display: block;
        }

        /* Badge positioned at the top-right corner of the image */
        .badge-top-right {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            background-color: var(--primary-red); /* Fallback color */
            color: white;
            /* Optional: Add a slight shadow for better visibility */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        /* Optional: Different badge colors based on status */
        .badge-top-right.bg-success {
            background-color: #28a745; /* Green */
        }

        .badge-top-right.bg-danger {
            background-color: #dc3545; /* Red */
        }

        .card-body {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .card-title {
            font-size: 1.1rem;
            margin-bottom: 10px;
            font-weight: bold;
            color: var(--primary-red);
        }

        .card-text {
            flex-grow: 1;
            font-size: 0.95rem;
            color: #555;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
        }

        .btn-group {
            margin-top: 10px;
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

        .button-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 15px;
            width: 100%;
        }

        .action-button {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
            border: 2px solid transparent;
            gap: 8px;
            text-decoration: none;
        }

        .reserve-button {
            background-color: #198754;
            color: white;
            border-color: #198754;
        }

        .reserve-button:hover {
            background-color: #157347;
            border-color: #157347;
            transform: translateY(-2px);
        }

        .view-details-button {
            background-color: transparent;
            color: #0d6efd;
            border-color: #0d6efd;
        }

        .view-details-button:hover {
            background-color: #0d6efd;
            color: white;
            transform: translateY(-2px);
        }

        .button-icon {
            font-size: 14px;
        }

        .button-text {
            font-size: 14px;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .nav-button {
                width: 80%;
                padding: 10px 12px;
            }
        }

        @media (max-width: 576px) {
            .dashboard-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
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
                <div class="card-body w-100">
                    <form method="GET" class="row g-3">
                        <div class="col-md-7">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search by name or SKU"
                                    name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="inventory-cards">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        // Determine badge label and color based on quantity
                        if ($row['quantity'] > 5) {
                            $badgeLabel = 'In Stock';
                            $badgeClass = 'bg-success';
                        } elseif ($row['quantity'] > 0) {
                            $badgeLabel = 'Limited Stock';
                            $badgeClass = 'bg-warning';
                        } else {
                            $badgeLabel = 'Out of Stock';
                            $badgeClass = 'bg-danger';
                        }

                        $badgeLabel .= ' (' . $row['quantity'] . ' units)';
                        ?>
                        <div class="inventory-card">
                            <div class="image-container">
                                <?php if (!empty($row['image_url']) && filter_var($row['image_url'], FILTER_VALIDATE_URL)): ?>
                                    <img src="<?php echo htmlspecialchars($row['image_url']); ?>"
                                        alt="<?php echo htmlspecialchars($row['name']); ?>">
                                <?php else: ?>
                                    <img src="../staff/uploads/<?php echo basename($row['image_url']); ?>"
                                        alt="<?php echo htmlspecialchars($row['name']); ?>">
                                <?php endif; ?>
                                <span class="badge-top-right <?php echo $badgeClass; ?>">
    <?php echo $badgeLabel; ?>
</span>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                                <p class="card-text">
                                    <?php echo htmlspecialchars(substr($row['description'], 0, 100)) . (strlen($row['description']) > 100 ? '...' : ''); ?>
                                </p>
                                <div class="mb-1">
                                    <strong>Amount: </strong><?php echo htmlspecialchars($row['amount']); ?> PHP
                                </div>
                                <div class="mb-1">
                                    <strong>Last Updated:
                                    </strong><?php echo date('M d, Y H:i', strtotime($row['updated_at'])); ?>
                                </div>
                                <div class="button-container">
                                    <button type="button" class="action-button reserve-button"
                                        onclick="reserveItem(<?php echo $row['item_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['name'])); ?>')">
                                        <i class="fas fa-bookmark button-icon"></i>
                                        <span class="button-text">Reserve</span>
                                    </button>
                                    <a href="product_details.php?id=<?php echo $row['item_id']; ?>"
                                        class="action-button view-details-button">
                                        <i class="fas fa-eye button-icon"></i>
                                        <span class="button-text">View Details</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center">No inventory items found.</p>
                <?php endif; ?>
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
                            <label for="amount" class="form-label">Reservation Fee per quantity</label>
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
        let currentItemId = 0;

        document.addEventListener('DOMContentLoaded', function () {
            // Initialize the modal
            reservationModal = new bootstrap.Modal(document.getElementById('reservationModal'));

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
                    const amount = 20; // Fixed fee of 100 pesos

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

            // Quantity change handler - always show fixed fee regardless of quantity
            document.getElementById('quantity').addEventListener('input', function (e) {
                document.getElementById('amount').value = '20.00 PHP';
            });
        });

        // Function to open the reservation modal
        function reserveItem(itemId, itemName) {
            currentItemId = itemId;

            document.getElementById('itemId').value = itemId;
            document.getElementById('itemName').textContent = 'Reserve Item: ' + itemName;
            document.getElementById('quantity').value = 1;
            document.getElementById('amount').value = '20.00 PHP';

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