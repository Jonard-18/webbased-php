<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Database connection
$host = "127.0.0.1";
$dbname = "integrative_final";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get item_id from URL
    if (isset($_GET['id'])) {
        $item_id = $_GET['id'];

        // Prepare and execute query to get item details
        $stmt = $pdo->prepare("SELECT i.*, u.username as added_by_username 
                             FROM inventory i 
                             LEFT JOIN users u ON i.added_by = u.user_id 
                             WHERE i.item_id = ? AND i.deleted = 0");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            header("Location: inventory.php");
            exit();
        }

        // Query for related products
        $stmt = $pdo->prepare("SELECT * FROM inventory 
                             WHERE item_id != ? AND deleted = 0 
                             LIMIT 4");
        $stmt->execute([$item_id]);
        $related_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        header("Location: inventory.php");
        exit();
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if (isset($_SESSION['payment_status'])) {
    $status = $_SESSION['payment_status'];
    $messageClass = ($status === 'success') ? 'alert-success' : 'alert-danger';
    $messageText = ($status === 'success') ? 'Payment successful! Your reservation has been confirmed.' : 'Payment failed. Please try again.';
    unset($_SESSION['payment_status']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVSU-RESERVE - Product Details</title>
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

        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            transition: transform 0.2s ease-in-out;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-img-top {
            border-radius: 10px 10px 0 0;
            object-fit: cover;
            height: 200px;
        }

        .product-card {
            position: relative;
        }

        .product-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 15px;
            color: white;
            font-size: 0.8rem;
            z-index: 1;
        }

        .price {
            font-weight: bold;
            color: var(--primary-red);
        }

        .btn-outline-danger {
            border-color: var(--primary-red);
            color: var(--primary-red);
        }

        .btn-outline-danger:hover {
            background-color: var(--primary-red);
            color: white;
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
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="inventory.php" class="text-decoration-none">Inventory</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php echo htmlspecialchars($item['name']); ?>
                    </li>
                </ol>
            </nav>

            <?php if (isset($messageText)): ?>
                <div class="alert <?php echo $messageClass; ?> alert-dismissible fade show" role="alert">
                    <?php echo $messageText; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Product Information Card -->
            <div class="row">
                <!-- Product Image Column -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <?php if ($item['image_url']): ?>
                            <img src="../staff/<?php echo htmlspecialchars($item['image_url']); ?>"
                                class="card-img-top h-100" alt="<?php echo htmlspecialchars($item['name']); ?>"
                                style="object-fit: cover;">
                        <?php else: ?>
                            <img src="/api/placeholder/800/600" class="card-img-top" alt="No image available">
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Product Details Column -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title border-bottom pb-3">Product Information</h5>

                            <div class="project-details mt-4">
                                <div class="row mb-3">
                                    <div class="col-4 fw-bold">Name:</div>
                                    <div class="col-8"><?php echo htmlspecialchars($item['name']); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4 fw-bold">SKU:</div>
                                    <div class="col-8"><?php echo htmlspecialchars($item['sku']); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4 fw-bold">Added by:</div>
                                    <div class="col-8"><?php echo htmlspecialchars($item['added_by_username']); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4 fw-bold">Date Added:</div>
                                    <div class="col-8"><?php echo date('F j, Y', strtotime($item['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4 fw-bold">Available Stock:</div>
                                    <div class="col-8"><?php echo $item['quantity']; ?> units</div>
                                </div>
                            </div>

                            <h5 class="card-title border-bottom pb-3 mt-4">Description</h5>
                            <p class="card-text mt-3">
                                <?php echo nl2br(htmlspecialchars($item['description'])); ?>
                            </p>

                            <!-- Reserve Now Button Section -->
                            <div class="mt-4 pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4 class="mb-0" style="color: var(--primary-red)">
                                            ₱<?php echo number_format($item['amount'], 2); ?>
                                        </h4>
                                        <?php if ($item['quantity'] > 0): ?>
                                            <small class="text-success">
                                                <i class="fas fa-check-circle"></i>
                                                Available (<?php echo $item['quantity']; ?> in stock)
                                            </small>
                                        <?php else: ?>
                                            <small class="text-danger">
                                                <i class="fas fa-times-circle"></i>
                                                Out of Stock
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($item['quantity'] > 0): ?>
                                        <button
                                            onclick="reserveItem(<?php echo $item['item_id']; ?>, '<?php echo htmlspecialchars(addslashes($item['name'])); ?>')"
                                            class="btn btn-danger btn-lg px-5"
                                            style="background-color: var(--primary-red);">
                                            <i class="fas fa-calendar-plus me-2"></i>Reserve Now
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-danger btn-lg px-5"
                                            style="background-color: var(--primary-red);" disabled>
                                            <i class="fas fa-calendar-plus me-2"></i>Out of Stock
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Related Products Section -->
                <?php if (!empty($related_items)): ?>
                    <h4 class="mt-4 mb-4">Related Products</h4>
                    <div class="row">
                        <?php foreach ($related_items as $related_item): ?>
                            <div class="col-md-3 mb-4">
                                <div class="card product-card h-100">
                                    <?php if ($related_item['quantity'] > 0): ?>
                                        <div class="product-badge bg-success">Available</div>
                                    <?php else: ?>
                                        <div class="product-badge bg-danger">Out of Stock</div>
                                    <?php endif; ?>

                                    <?php if ($related_item['image_url']): ?>
                                        <img src="../staff/<?php echo htmlspecialchars($related_item['image_url']); ?>"
                                            class="card-img-top" alt="<?php echo htmlspecialchars($related_item['name']); ?>">
                                    <?php else: ?>
                                        <img src="/api/placeholder/400/300" class="card-img-top" alt="No image available">
                                    <?php endif; ?>

                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($related_item['name']); ?></h5>
                                        <p class="card-text text-muted"><?php echo htmlspecialchars($related_item['sku']); ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="price">₱<?php echo number_format($related_item['amount'], 2); ?></span>
                                            <a href="product_details.php?id=<?php echo $related_item['item_id']; ?>"
                                                class="btn btn-outline-danger btn-sm">
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>


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


        <!-- Bootstrap Bundle with Popper -->
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
                    const amount = 100; // Fixed fee of 100 pesos

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
                document.getElementById('amount').value = '100.00 PHP';
            });
        });

        // Function to open the reservation modal
        function reserveItem(itemId, itemName) {
            currentItemId = itemId;

            document.getElementById('itemId').value = itemId;
            document.getElementById('itemName').textContent = 'Reserve Item: ' + itemName;
            document.getElementById('quantity').value = 1;
            document.getElementById('amount').value = '100.00 PHP';

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