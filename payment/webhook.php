<?php
require_once 'config.php';

// Get webhook signature
$signingKey = PAYMONGO_WEBHOOK_SIG;
$signature = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';
$payload = file_get_contents('php://input');

// Verify webhook signature
$computedSignature = hash_hmac('sha256', $payload, $signingKey);

if (!hash_equals($computedSignature, $signature)) {
    http_response_code(401);
    exit('Invalid signature');
}

try {
    $data = json_decode($payload, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON payload');
    }

    // Process the webhook based on type
    $type = $data['data']['attributes']['type'] ?? '';
    
    switch ($type) {
        case 'payment.paid':
            // Handle successful payment
            $paymentId = $data['data']['id'];
            $amount = $data['data']['attributes']['amount'] / 100; // Convert from cents
            $status = $data['data']['attributes']['status'];
            
            // Log or process the payment success
            error_log("Payment successful - ID: $paymentId, Amount: $amount");
            break;
            
        case 'payment.failed':
            // Handle failed payment
            $paymentId = $data['data']['id'];
            $failureCode = $data['data']['attributes']['failure_code'] ?? '';
            
            // Log the failure
            error_log("Payment failed - ID: $paymentId, Code: $failureCode");
            break;
    }
    
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    error_log('Webhook Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

