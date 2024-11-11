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
        throw new Exception(message: 'Invalid amount provided');
    }

    if (!isset($input['quantity']) || !is_numeric($input['quantity']) || $input['quantity'] <= 0) {
        throw new Exception('Invalid quantity provided');
    }
    
    $amount = (int)$input['amount'];
    $quantity = (int)$input['quantity'];
    
    if ($amount <= 0) {
        throw new Exception('Amount must be greater than 0');
    }

    // Create PayMongo payment session
    $ch = curl_init(PAYMONGO_API_URL . '/checkout_sessions');
    
    $payload = [
        'data' => [
            'attributes' => [
                'line_items' => [[
                    'name' => 'Payment',
                    'quantity' => $quantity,
                    'amount' => ($amount / $quantity) * 100, // Convert to cents and get per-item amount
                    'currency' => 'PHP',
                ]],
                'payment_method_types' => [
                    'gcash',
                    'grab_pay',
                    'card'
                ],
                'success_url' => APP_URL . '/success.php',
                'cancel_url' => APP_URL . '/error.php',
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