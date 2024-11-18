<?php
// payment_form.php

require_once 'config.php';

// Get the current script URL
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[SCRIPT_NAME]";

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ch = curl_init();
    
    // Create PayMongo checkout session
    $payload = [
        'data' => [
            'attributes' => [
                'line_items' => [[
                    'name' => 'Scheduling Fee',
                    'amount' => 10000, 
                    'currency' => 'PHP',
                    'quantity' => 1
                ]],
                'payment_method_types' => ['card', 'gcash'],
                'success_url' => $current_url . '?status=success',
                'cancel_url' => $current_url . '?status=cancel',
                'description' => 'Scheduling Fee Payment'
            ]
        ]
    ];

    curl_setopt_array($ch, [
        CURLOPT_URL => PAYMONGO_API_URL . '/checkout_sessions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY),
            'Content-Type: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        echo json_encode(['success' => false, 'message' => 'Payment failed: ' . $err]);
        exit;
    }

    $result = json_decode($response, true);
    if (isset($result['data']['attributes']['checkout_url'])) {
        echo json_encode([
            'success' => true,
            'checkout_url' => $result['data']['attributes']['checkout_url']
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create checkout session']);
        exit;
    }
}

// Handle status messages after payment
$status = isset($_GET['status']) ? $_GET['status'] : '';
$statusMessage = '';
$statusClass = '';

if ($status === 'success') {
    $statusMessage = 'Payment successful! Thank you for your payment.';
    $statusClass = 'success';
} elseif ($status === 'cancel') {
    $statusMessage = 'Payment was cancelled.';
    $statusClass = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduling Fee Payment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
        }
        .payment-container {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .button {
            background-color: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }
        .button:hover {
            background-color: #45a049;
        }
        .message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 4px;
            text-align: center;
        }
        .success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        .error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
        .amount {
            font-size: 24px;
            color: #333;
            text-align: center;
            margin: 20px 0;
            font-weight: bold;
        }
        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <h2>Scheduling Fee Payment</h2>
        <div class="amount">PHP 100.00</div>
        <?php if ($statusMessage): ?>
            <div class="message <?php echo $statusClass; ?>">
                <?php echo htmlspecialchars($statusMessage); ?>
            </div>
        <?php endif; ?>
        <?php if (!$status): ?>
            <button id="payButton" class="button">Pay Now</button>
            <div id="message" class="message" style="display: none;"></div>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('payButton')?.addEventListener('click', async () => {
            try {
                const button = document.getElementById('payButton');
                button.disabled = true;
                button.textContent = 'Processing...';

                // Create checkout session
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });
                
                const result = await response.json();
                
                if (!result.success) {
                    showMessage(result.message, false);
                    button.disabled = false;
                    button.textContent = 'Pay Now';
                    return;
                }

                window.location.href = result.checkout_url;
                
            } catch (error) {
                showMessage('An error occurred: ' + error.message, false);
                const button = document.getElementById('payButton');
                button.disabled = false;
                button.textContent = 'Pay Now';
            }
        });

        function showMessage(message, isSuccess) {
            const messageDiv = document.getElementById('message');
            messageDiv.textContent = message;
            messageDiv.className = 'message ' + (isSuccess ? 'success' : 'error');
            messageDiv.style.display = 'block';
        }
    </script>
</body>
</html>