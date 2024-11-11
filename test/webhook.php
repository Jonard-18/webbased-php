<?php
require_once 'config.php';

// Verify webhook signature for security
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';

if (!verifyWebhookSignature($payload, $signature, PAYMONGO_WEBHOOK_SIG)) {
    http_response_code(401);
    exit('Invalid signature');
}

try {
    $data = json_decode($payload, true);
    
    switch ($data['data']['attributes']['type']) {
        case 'source.chargeable':
            // Process the payment when GCash confirms user has sufficient balance
            processPayment($data['data']);
            break;
            
        case 'payment.paid':
            // Payment successful - update your database
            $paymentId = $data['data']['id'];
            $amount = $data['data']['attributes']['amount'] / 100;
            
            // Update transaction status in database
            updateTransactionStatus($paymentId, 'paid', $amount);
            
            // Send confirmation email to customer
            // sendPaymentConfirmation($paymentId);
            break;
            
        case 'payment.failed':
            // Handle failed payment
            $paymentId = $data['data']['id'];
            updateTransactionStatus($paymentId, 'failed');
            break;
    }
    
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    error_log('Webhook Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

function verifyWebhookSignature($payload, $signature, $key) {
    return hash_equals(
        hash_hmac('sha256', $payload, $key),
        $signature
    );
}

function processPayment($sourceData) {
    // Implement payment processing logic
    // This is where you'd create a payment using the source
    // Add your payment creation code here
}

function updateTransactionStatus($paymentId, $status, $amount = null) {
    // Implement database update logic
    // Update your transaction records here
}