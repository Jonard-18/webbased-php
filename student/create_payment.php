<?php
session_start();
include '../config/database.php';
header('Content-Type: application/json');

try {
    // Verify user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
    }

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate input data
    if (!isset($data['itemId']) || !isset($data['quantity']) || !isset($data['amount'])) {
        throw new Exception('Missing required data');
    }

    // Verify item exists and has sufficient quantity
    $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE item_id = ?");
    $stmt->bind_param("i", $data['itemId']);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();

    if (!$item) {
        throw new Exception('Item not found');
    }

    if ($item['quantity'] < $data['quantity']) {
        throw new Exception('Insufficient quantity available');
    }

    // Generate a unique reference number
    $reference = 'ORDER_' . time() . '_' . uniqid();
    
    // PayMongo API endpoint
    $url = 'https://api.paymongo.com/v1/checkout_sessions';
    
    // Get the current domain
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $domain = $_SERVER['HTTP_HOST'];
    $base_url = $protocol . $domain;
    
    // Store payment details in session
    $_SESSION['payments'][$reference] = [
        'item_id' => $data['itemId'],
        'quantity' => $data['quantity'],
        'amount' => $data['amount'],
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'created_at' => time()
    ];
    
    // Prepare PayMongo payload
    $payload = [
        'data' => [
            'attributes' => [
                'line_items' => [[
                    'amount' => intval($data['amount'] * 100),
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
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode('sk_test_XhhSeNsJTpZVbwCstLAJBzso')
        ]
    ]);

    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if (isset($result['data']['attributes']['checkout_url'])) {
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