<?php
session_start();
header('Content-Type: application/json');

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Generate a unique reference number
    $reference = 'ORDER_' . time() . '_' . uniqid();
    
    // PayMongo API endpoint for creating payment intent
    $url = 'https://api.paymongo.com/v1/checkout_sessions';
    
    // Get the current domain
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $domain = $_SERVER['HTTP_HOST'];
    $base_url = $protocol . $domain;
    
    // Store payment details in session with reference as key
    $_SESSION['payments'][$reference] = [
        'item_id' => $data['itemId'],
        'quantity' => $data['quantity'],
        'amount' => $data['amount'],
        'created_at' => time()
    ];
    
    // Prepare the data
    $payload = [
        'data' => [
            'attributes' => [
                'line_items' => [[
                    'amount' => intval($data['amount'] * 100), // Convert to cents
                    'currency' => 'PHP',
                    'name' => 'Item Reservation',
                    'quantity' => intval($data['quantity'])
                ]],
                'payment_method_types' => ['card', 'gcash', 'grab_pay'],
                'send_email_receipt' => true,
                'show_description' => true,
                'show_line_items' => true,
                'description' => "Reservation payment for item #{$data['itemId']}",
                'reference_number' => $reference,
                'success_url' => $base_url . '/student/payment_success.php?reference=' . urlencode($reference),
                'cancel_url' => $base_url . '/student/inventory.php'
            ]
        ]
    ];

    // Initialize cURL
    $ch = curl_init($url);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode('sk_test_XhhSeNsJTpZVbwCstLAJBzso')
    ]);

    // Execute cURL request
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    
    curl_close($ch);
    
    // Parse response
    $result = json_decode($response, true);
    
    if (isset($result['data']['attributes']['checkout_url'])) {
        // Store the checkout session ID in the payment data
        $_SESSION['payments'][$reference]['checkout_session_id'] = $result['data']['id'];
        
        echo json_encode([
            'success' => true,
            'checkoutUrl' => $result['data']['attributes']['checkout_url']
        ]);
    } else {
        throw new Exception('Failed to create checkout session');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}