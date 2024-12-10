<<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Staff')) {
    header("Location: ../auth/Login.php");
    exit();
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle payment processing and pickup
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['show_payment'])) {
        $reservation_id = $_POST['reservation_id'];
        
        // Fetch reservation details including price
        $details_query = "
            SELECT r.reservation_id, r.reserved_quantity, r.reserved_at, 
                   i.name AS item_name, i.sku, i.price,
                   u.username, u.email
            FROM reservations r
            JOIN inventory i ON r.item_id = i.item_id
            JOIN users u ON r.user_id = u.user_id
            WHERE r.reservation_id = ?";
            
        $stmt = $conn->prepare($details_query);
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $reservation_details = $stmt->get_result()->fetch_assoc();
        
        // Calculate total amount
        $total_amount = $reservation_details['price'] * $reservation_details['reserved_quantity'];
        $_SESSION['payment_details'] = $reservation_details;
        $_SESSION['total_amount'] = $total_amount;
    }
    
    if (isset($_POST['process_payment'])) {
        $reservation_id = $_POST['reservation_id'];
        $amount_paid = $_POST['amount_paid'];
        $payment_method = $_POST['payment_method'];
        
        $conn->begin_transaction();
        
        try {
            // Update reservation status and record payment
            $update_query = "UPDATE reservations SET status = 'Fulfilled', 
                           payment_amount = ?, payment_method = ?, 
                           payment_date = CURRENT_TIMESTAMP 
                           WHERE reservation_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("dsi", $amount_paid, $payment_method, $reservation_id);
            $stmt->execute();
            
            // Update inventory
            $update_inventory = "
                UPDATE inventory i
                JOIN reservations r ON i.item_id = r.item_id
                SET i.quantity = i.quantity - r.reserved_quantity
                WHERE r.reservation_id = ?";
            $stmt = $conn->prepare($update_inventory);
            $stmt->bind_param("i", $reservation_id);
            $stmt->execute();
            
            $conn->commit();
            $success_message = "Payment processed successfully!";
            
            // Generate receipt
            $_SESSION['show_receipt'] = true;
            $_SESSION['receipt_details'] = [
                'reservation_id' => $reservation_id,
                'amount_paid' => $amount_paid,
                'payment_method' => $payment_method,
                'payment_date' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error processing payment: " . $e->getMessage();
        }
    }
}

// Rest of your existing query for pending reservations
$pending_query = "
    SELECT r.reservation_id, r.reserved_quantity, r.reserved_at, 
           i.name AS item_name, i.sku, i.price,
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
    <!-- Your existing head content -->
    <style>
        /* Add to your existing styles */
        .payment-modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            z-index: 1000;
            width: 400px;
        }
        
        .modal-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        
        .receipt {
            width: 300px;
            padding: 20px;
            border: 1px solid #ddd;
            margin: 20px auto;
            background: white;
            font-family: 'Courier New', monospace;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .receipt-logo {
            width: 100px;
            height: auto;
            margin-bottom: 10px;
        }
        
        .receipt-details {
            margin: 10px 0;
            border-top: 1px dashed #ddd;
            border-bottom: 1px dashed #ddd;
            padding: 10px 0;
        }
        
        .receipt-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <!-- Your existing dashboard container and sidebar -->
    
    <div class="main-content container-fluid p-4">
        <!-- Your existing content -->
        
        <!-- Payment Modal -->
        <div class="modal-backdrop" id="modalBackdrop"></div>
        <div class="payment-modal" id="paymentModal">
            <?php if(isset($_POST['show_payment']) && isset($_SESSION['payment_details'])): ?>
                <h3>Process Payment</h3>
                <div class="mb-3">
                    <p><strong>Item:</strong> <?php echo htmlspecialchars($_SESSION['payment_details']['item_name']); ?></p>
                    <p><strong>Quantity:</strong> <?php echo htmlspecialchars($_SESSION['payment_details']['reserved_quantity']); ?></p>
                    <p><strong>Total Amount:</strong> ₱<?php echo number_format($_SESSION['total_amount'], 2); ?></p>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="reservation_id" value="<?php echo $_SESSION['payment_details']['reservation_id']; ?>">
                    <input type="hidden" name="amount_paid" value="<?php echo $_SESSION['total_amount']; ?>">
                    <div class="mb-3">
                        <label>Payment Method:</label>
                        <select name="payment_method" class="form-control" required>
                            <option value="Cash">Cash</option>
                            <option value="GCash">GCash</option>
                            <option value="Credit Card">Credit Card</option>
                        </select>
                    </div>
                    <button type="submit" name="process_payment" class="btn btn-success">Process Payment</button>
                    <button type="button" onclick="closePaymentModal()" class="btn btn-secondary">Cancel</button>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Receipt Modal -->
        <?php if(isset($_SESSION['show_receipt']) && $_SESSION['show_receipt']): ?>
        <div class="receipt">
            <div class="receipt-header">
                <img src="../assets/images/logo.png" alt="EVSU Logo" class="receipt-logo">
                <h4>EVSU-RESERVE</h4>
                <p>Official Receipt</p>
            </div>
            <div class="receipt-details">
                <p>Receipt No: <?php echo str_pad($_SESSION['receipt_details']['reservation_id'], 6, '0', STR_PAD_LEFT); ?></p>
                <p>Date: <?php echo date('Y-m-d H:i:s'); ?></p>
                <p>Item: <?php echo htmlspecialchars($_SESSION['payment_details']['item_name']); ?></p>
                <p>Quantity: <?php echo htmlspecialchars($_SESSION['payment_details']['reserved_quantity']); ?></p>
                <p>Amount: ₱<?php echo number_format($_SESSION['receipt_details']['amount_paid'], 2); ?></p>
                <p>Payment Method: <?php echo htmlspecialchars($_SESSION['receipt_details']['payment_method']); ?></p>
            </div>
            <div class="receipt-footer">
                <p>Thank you for your transaction!</p>
                <button onclick="window.print()" class="btn btn-sm btn-primary">Print Receipt</button>
            </div>
        </div>
        <?php 
            unset($_SESSION['show_receipt']);
            unset($_SESSION['receipt_details']);
            unset($_SESSION['payment_details']);
        endif; 
        ?>
        
        <!-- Update your existing table's action buttons -->
        <td class="text-center reservation-actions">
            <form method="POST" action="" class="d-flex justify-content-center gap-2">
                <input type="hidden" name="reservation_id" value="<?php echo $reservation['reservation_id']; ?>">
                <button type="submit" name="show_payment" class="btn btn-sm btn-success">
                    <i class="fas fa-check-circle"></i> Process Pickup
                </button>
                <button type="submit" name="update_reservation" value="Cancelled" class="btn btn-sm btn-danger">
                    <i class="fas fa-times-circle"></i> Cancel
                </button>
            </form>
        </td>
    </div>

    <script>
    function showPaymentModal() {
        document.getElementById('modalBackdrop').style.display = 'block';
        document.getElementById('paymentModal').style.display = 'block';
    }
    
    function closePaymentModal() {
        document.getElementById('modalBackdrop').style.display = 'none';
        document.getElementById('paymentModal').style.display = 'none';
    }
    
    <?php if(isset($_POST['show_payment'])): ?>
        showPaymentModal();
    <?php endif; ?>
    </script>
</body>
</html>