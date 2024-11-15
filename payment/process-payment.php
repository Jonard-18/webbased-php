<?php
require_once 'config.php';

header('Content-Type: application/json');

// Add logging function
function logDebug($message, $data = null) {
    $logMessage = date('[Y-m-d H:i:s] ') . $message;
    if ($data !== null) {
        $logMessage .= "\nData: " . print_r($data, true);
    }
    error_log($logMessage . "\n", 3, 'payment_debug.log');
}

try {
    // Log incoming request
    logDebug('Received payment request', $_POST);
    
    // Get and validate input
    $input = json_decode(file_get_contents('php://input'), true);
    logDebug('Decoded input', $input);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        logDebug('JSON decode error: ' . json_last_error_msg());
        throw new Exception('Invalid JSON provided');
    }
    
    if (!isset($input['amount']) || !is_numeric($input['amount'])) {
        logDebug('Invalid amount', $input['amount'] ?? null);
        throw new Exception('Invalid amount provided');
    }
    
    if (!isset($input['itemId'])) {
        logDebug('Missing itemId');
        throw new Exception('Item ID is required');
    }
    
    $amount = (int)$input['amount'];
    $quantity = (int)($input['quantity'] ?? 1);
    $itemId = (int)$input['itemId'];
    
    logDebug('Validated input values', [
        'amount' => $amount,
        'quantity' => $quantity,
        'itemId' => $itemId
    ]);
    
    if ($amount <= 0) {
        logDebug('Invalid amount value', $amount);
        throw new Exception('Amount must be greater than 0');
    }

    // Fetch item details from database
    require_once('../config/database.php');
    $stmt = $conn->prepare("SELECT name, quantity AS stock_quantity FROM inventory WHERE item_id = ?");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        logDebug('Item not found in database', $itemId);
        throw new Exception('Item not found');
    }
    
    $item = $result->fetch_assoc();
    $itemName = $item['name'];
    $stockQuantity = (int)$item['stock_quantity'];

    // Check if there is enough stock
    if ($stockQuantity < $quantity) {
        logDebug('Insufficient stock for item', [
            'itemId' => $itemId,
            'requested_quantity' => $quantity,
            'available_quantity' => $stockQuantity
        ]);
        throw new Exception('Insufficient stock');
    }

    logDebug('Retrieved item from database', $item);

    // Create PayMongo payload
    $payload = [
        'data' => [
            'attributes' => [
                'line_items' => [[
                    'name' => $itemName,
                    'quantity' => $quantity,
                    'amount' => $amount * 100, // Convert to cents
                    'currency' => 'PHP',
                ]],
                'payment_method_types' => [
                    'gcash',
                    'grab_pay',
                    'card'
                ],
                'success_url' => APP_URL . '/payment/success.php',
                'cancel_url' => APP_URL . '/payment/error.php',
            ]
        ]
    ];
    
    logDebug('PayMongo request payload', $payload);

    // Initialize PayMongo API request
    $ch = curl_init(PAYMONGO_API_URL . '/checkout_sessions');
    
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY)
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    logDebug('PayMongo API response', [
        'status_code' => $statusCode,
        'response' => $response,
        'curl_error' => $error
    ]);
    
    curl_close($ch);

    if ($error) {
        logDebug('Curl error occurred', $error);
        throw new Exception('Failed to connect to PayMongo: ' . $error);
    }

    $responseData = json_decode($response, true);
    
    if ($statusCode >= 400 || !isset($responseData['data']['attributes']['checkout_url'])) {
        $errorMessage = isset($responseData['errors'][0]['detail']) 
            ? $responseData['errors'][0]['detail']
            : 'Payment initialization failed';
        logDebug('Payment initialization failed', $responseData);
        throw new Exception($errorMessage);
    }

    logDebug('Payment session created successfully', [
        'checkout_url' => $responseData['data']['attributes']['checkout_url']
    ]);

    // Update inventory quantity
    $stmt = $conn->prepare("UPDATE inventory SET quantity = quantity - ? WHERE item_id = ?");
    $stmt->bind_param("ii", $quantity, $itemId);

    if ($stmt->execute()) {
        logDebug('Inventory quantity updated successfully', [
            'itemId' => $itemId,
            'quantity_subtracted' => $quantity
        ]);
    } else {
        logDebug('Failed to update inventory quantity', [
            'itemId' => $itemId,
            'error' => $stmt->error
        ]);
    }

    // Echo the result for debugging
    echo json_encode([
        'success' => true,
        'checkoutUrl' => $responseData['data']['attributes']['checkout_url'],
        'debug' => [
            'amount' => $amount,
            'itemId' => $itemId,
            'itemName' => $itemName,
            'statusCode' => $statusCode
        ]
    ]);

} catch (Exception $e) {
    logDebug('Error occurred', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug_trace' => $e->getTraceAsString()
    ]);
}
