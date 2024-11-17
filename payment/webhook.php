<?php
require_once 'config.php';
require_once('../config/database.php');

function validateSignature($payload, $signature, $signingKey) {
    $computedSignature = hash_hmac('sha256', $payload, $signingKey);
    return hash_equals($computedSignature, $signature);
}

function logWebhookError($message, $data = null) {
    $logFile = 'payment_logs.txt';
    $logMessage = date('[Y-m-d H:i:s] ') . $message;
    if ($data !== null) {
        $logMessage .= "\nData: " . json_encode($data, JSON_PRETTY_PRINT);
    }
    file_put_contents($logFile, $logMessage . "\n", FILE_APPEND);
}

function processPaymentEvent($data, $conn) {
    $paymentId = $data['data']['id'];
    $amount = $data['data']['attributes']['amount'] / 100;
    $referenceNumber = $data['data']['attributes']['reference_number'] ?? '';

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("UPDATE reservations SET status = 'Fulfilled', updated_at = CURRENT_TIMESTAMP WHERE reservation_id = ?");
        $stmt->bind_param("i", $referenceNumber);
        $stmt->execute();

        $stmt = $conn->prepare("
            INSERT INTO payments (reservation_id, user_id, amount, payment_status)
            SELECT ?, user_id, ?, 'Completed' FROM reservations WHERE reservation_id = ?
        ");
        $stmt->bind_param("idi", $referenceNumber, $amount, $referenceNumber);
        $stmt->execute();

        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, reservation_id, message, is_read)
            SELECT user_id, ?, 'Your payment has been confirmed and your reservation is now fulfilled.', 0 FROM reservations WHERE reservation_id = ?
        ");
        $stmt->bind_param("ii", $referenceNumber, $referenceNumber);
        $stmt->execute();

        $stmt = $conn->prepare("
            INSERT INTO useractivities (user_id, action, reservation_id, details)
            SELECT user_id, 'Payment Completed', ?, CONCAT('Payment of PHP ', ?, ' completed for reservation #', ?) 
            FROM reservations WHERE reservation_id = ?
        ");
        $stmt->bind_param("idii", $referenceNumber, $amount, $referenceNumber, $referenceNumber);
        $stmt->execute();

        $conn->commit();
        logWebhookError("PAYMENT SUCCESS", [
            'payment_id' => $paymentId,
            'amount' => $amount,
            'reference' => $referenceNumber
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function processFailedPaymentEvent($data, $conn) {
    $paymentId = $data['data']['id'];
    $amount = $data['data']['attributes']['amount'] / 100;
    $referenceNumber = $data['data']['attributes']['reference_number'] ?? '';

    logWebhookError("PAYMENT FAILED", [
        'payment_id' => $paymentId,
        'amount' => $amount,
        'reference' => $referenceNumber
    ]);

    // Similar to processPaymentEvent, with specific steps for handling failed payments
}

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';

if (!validateSignature($payload, $signature, PAYMONGO_WEBHOOK_SIG)) {
    http_response_code(401);
    exit('Invalid signature');
}

try {
    $data = json_decode($payload, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON payload');
    }

    $type = $data['data']['attributes']['type'] ?? '';
    
    switch ($type) {
        case 'payment.paid':
            processPaymentEvent($data, $conn);
            break;
        case 'payment.failed':
            processFailedPaymentEvent($data, $conn);
            break;
        default:
            throw new Exception("Unhandled event type: $type");
    }
    
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    logWebhookError('Webhook Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
