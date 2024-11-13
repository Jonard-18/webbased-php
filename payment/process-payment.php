<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Get and validate input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON provided');
    }
    
    if (!isset($input['amount']) || !is_numeric($input['amount'])) {
        throw new Exception('Invalid amount provided');
    }
    
    if (!isset($input['itemId'])) {
        throw new Exception('Item ID is required');
    }
    
    $amount = (int)$input['amount'];
    $quantity = (int)$input['quantity'];
    $itemId = (int)$input['itemId'];
    
    if ($amount <= 0) {
        throw new Exception('Amount must be greater than 0');
    }

    // Fetch item details from database
    require_once('../config/database.php');
    $stmt = $conn->prepare("SELECT name FROM inventory WHERE item_id = ?");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Item not found');
    }
    
    $item = $result->fetch_assoc();
    $itemName = $item['name'];

    // Create PayMongo payment session
    $ch = curl_init(PAYMONGO_API_URL . '/checkout_sessions');
    
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
                'success_url' => 'https://7773-222-127-73-6.ngrok-free.app/payment/sucess.php',
                'cancel_url' => 'https://7773-222-127-73-6.ngrok-free.app/payment/error.php',
            ]
        ]
    ];

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
    
    curl_close($ch);

    if ($error) {
        throw new Exception('Failed to connect to PayMongo: ' . $error);
    }

    $responseData = json_decode($response, true);
    
    if ($statusCode >= 400 || !isset($responseData['data']['attributes']['checkout_url'])) {
        $errorMessage = isset($responseData['errors'][0]['detail']) 
            ? $responseData['errors'][0]['detail']
            : 'Payment initialization failed';
        throw new Exception($errorMessage);
    }

    echo json_encode([
        'success' => true,
        'checkoutUrl' => $responseData['data']['attributes']['checkout_url']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}