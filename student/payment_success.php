<?php
session_start();
include '../config/database.php';

$reference = $_GET['reference'] ?? '';
$log_file = 'payment_log.txt';

try {
    if (empty($reference)) {
        throw new Exception("Reference number is missing");
    }

    if (!isset($_SESSION['payments'][$reference])) {
        throw new Exception("No payment data found for reference: " . $reference);
    }

    $payment_data = $_SESSION['payments'][$reference];
    $checkout_session_id = $payment_data['checkout_session_id'];

    $url = "https://api.paymongo.com/v1/checkout_sessions/" . $checkout_session_id;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode('sk_test_XhhSeNsJTpZVbwCstLAJBzso')
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $curl_error = curl_error($ch);
        file_put_contents($log_file, "cURL Error: {$curl_error}\n", FILE_APPEND);
        throw new Exception($curl_error);
    }
    curl_close($ch);

    $result = json_decode($response, true);
    file_put_contents($log_file, "API Response: " . json_encode($result) . "\n", FILE_APPEND);

    $payment_success = false;
    
    if (isset($result['data']['attributes']['payment_intent']['attributes']['status']) 
        && $result['data']['attributes']['payment_intent']['attributes']['status'] === 'succeeded') {
        $payment_success = true;
    }
    
    if (isset($result['data']['attributes']['payments']) 
        && !empty($result['data']['attributes']['payments'])
        && $result['data']['attributes']['payments'][0]['attributes']['status'] === 'paid') {
        $payment_success = true;
    }

    if ($payment_success) {
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("INSERT INTO reservations (item_id, user_id, reserved_quantity, down_payment, status, reserved_by_username) VALUES (?, ?, ?, ?, 'Pending', ?)");
            $stmt->bind_param("iiids", 
                $payment_data['item_id'],
                $payment_data['user_id'],
                $payment_data['quantity'],
                $payment_data['amount'],
                $payment_data['username']
            );
            $stmt->execute();
            $reservation_id = $conn->insert_id;

            $payment = $result['data']['attributes']['payments'][0]['attributes'];
            $amount = $payment['amount'] / 100; // Convert from cents
            
            $stmt = $conn->prepare("INSERT INTO payments (reservation_id, user_id, amount, payment_status) VALUES (?, ?, ?, 'Completed')");
            $stmt->bind_param("iid", 
                $reservation_id,
                $payment_data['user_id'],
                $amount
            );
            $stmt->execute();
            
            $stmt = $conn->prepare("UPDATE inventory SET quantity = quantity - ? WHERE item_id = ?");
            $stmt->bind_param("ii", 
                $payment_data['quantity'],
                $payment_data['item_id']
            );
            $stmt->execute();
            
            $conn->commit();
            
            $_SESSION['payment_status'] = 'success';
            $_SESSION['payment_message'] = 'Payment processed successfully and reservation created';
            
            $log_message = sprintf(
                "Payment successful - Reference: %s - Item: %s - Quantity: %s - Amount: %s - Payment ID: %s - Reservation ID: %s\n",
                $reference,
                $payment_data['item_id'],
                $payment_data['quantity'],
                $amount,
                $payment['id'],
                $reservation_id
            );
            file_put_contents($log_file, $log_message, FILE_APPEND);
            
            $_SESSION['last_successful_payment'] = [
                'reference' => $reference,
                'item_id' => $payment_data['item_id'],
                'quantity' => $payment_data['quantity'],
                'amount' => $amount,
                'payment_id' => $payment['id'],
                'reservation_id' => $reservation_id,
                'payment_method' => $result['data']['attributes']['payment_method_used'],
                'completed_at' => $payment['paid_at']
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            throw new Exception("Database error: " . $e->getMessage());
        }
    } else {
        $reason = $result['errors'][0]['detail'] ?? 'Payment verification failed';
        $_SESSION['payment_status'] = 'failed';
        $_SESSION['payment_message'] = 'Payment failed: ' . $reason;
        
        file_put_contents($log_file, "Payment failed - Reference: {$reference} - Reason: {$reason}\n", FILE_APPEND);
    }

    unset($_SESSION['payments'][$reference]);

} catch (Exception $e) {
    $_SESSION['payment_status'] = 'failed';
    $_SESSION['payment_message'] = 'An error occurred while processing the payment: ' . $e->getMessage();
    file_put_contents($log_file, "Error processing payment: {$e->getMessage()}\n", FILE_APPEND);
}

header('Location: inventory.php');
exit;