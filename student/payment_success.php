<?php
session_start();

$reference = $_GET['reference'] ?? '';
$log_file = 'payment_log.txt';

try {
    if (empty($reference)) {
        throw new Exception("Reference number is missing");
    }

    // Check if we have the payment data in session
    if (!isset($_SESSION['payments'][$reference])) {
        throw new Exception("No payment data found for reference: " . $reference);
    }

    $payment_data = $_SESSION['payments'][$reference];
    $checkout_session_id = $payment_data['checkout_session_id'];

    // PayMongo API endpoint for retrieving checkout session
    $url = "https://api.paymongo.com/v1/checkout_sessions/" . $checkout_session_id;

    // Initialize cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode('sk_test_XhhSeNsJTpZVbwCstLAJBzso')
    ]);

    // Execute cURL request
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $curl_error = curl_error($ch);
        file_put_contents($log_file, "cURL Error: {$curl_error}\n", FILE_APPEND);
        throw new Exception($curl_error);
    }
    curl_close($ch);

    // Parse response
    $result = json_decode($response, true);
    file_put_contents($log_file, "API Response: " . json_encode($result) . "\n", FILE_APPEND);

    // Check payment status - Look for both the payment_intent status and the actual payment status
    $payment_success = false;
    
    if (isset($result['data']['attributes']['payment_intent']['attributes']['status']) 
        && $result['data']['attributes']['payment_intent']['attributes']['status'] === 'succeeded') {
        $payment_success = true;
    }
    
    // Double check the actual payment status if available
    if (isset($result['data']['attributes']['payments']) 
        && !empty($result['data']['attributes']['payments'])
        && $result['data']['attributes']['payments'][0]['attributes']['status'] === 'paid') {
        $payment_success = true;
    }

    if ($payment_success) {
        $_SESSION['payment_status'] = 'success';
        $_SESSION['payment_message'] = 'Payment processed successfully';
        
        // Get payment details
        $payment = $result['data']['attributes']['payments'][0]['attributes'];
        
        // Log success with more details
        $log_message = sprintf(
            "Payment successful - Reference: %s - Item: %s - Quantity: %s - Amount: %s - Payment ID: %s\n",
            $reference,
            $payment_data['item_id'],
            $payment_data['quantity'],
            $payment['amount'] / 100, // Convert back from cents
            $payment['id']
        );
        file_put_contents($log_file, $log_message, FILE_APPEND);
        
        // Store successful payment data in session with more details
        $_SESSION['last_successful_payment'] = [
            'reference' => $reference,
            'item_id' => $payment_data['item_id'],
            'quantity' => $payment_data['quantity'],
            'amount' => $payment['amount'] / 100,
            'payment_id' => $payment['id'],
            'payment_method' => $result['data']['attributes']['payment_method_used'],
            'completed_at' => $payment['paid_at']
        ];
        
    } else {
        $reason = $result['errors'][0]['detail'] ?? 'Payment verification failed';
        $_SESSION['payment_status'] = 'failed';
        $_SESSION['payment_message'] = 'Payment failed: ' . $reason;
        
        file_put_contents($log_file, "Payment failed - Reference: {$reference} - Reason: {$reason}\n", FILE_APPEND);
    }

    // Clean up the payment data from session
    unset($_SESSION['payments'][$reference]);

} catch (Exception $e) {
    $_SESSION['payment_status'] = 'failed';
    $_SESSION['payment_message'] = 'An error occurred while processing the payment: ' . $e->getMessage();
    file_put_contents($log_file, "Error processing payment: {$e->getMessage()}\n", FILE_APPEND);
}

header('Location: inventory.php');
exit;