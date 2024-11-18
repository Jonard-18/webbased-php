<?php
include 'db_connect.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$reservation_id = $data['reservation_id'];

// Check if reservation_id is set and is a valid integer
if (isset($reservation_id) && is_numeric($reservation_id)) {
    // Update the reservation status in the database
    $sql = "UPDATE reservations SET status = 'Fulfilled' WHERE reservation_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $reservation_id);
        $response = [];
        
        if ($stmt->execute()) {
            $response['success'] = true;
        } else {
            $response['success'] = false;
            $response['error'] = $stmt->error; // Capture error for debugging
        }
        
        $stmt->close();
    } else {
        $response['success'] = false;
        $response['error'] = $conn->error; // Capture error for debugging
    }
} else {
    $response['success'] = false;
    $response['error'] = 'Invalid reservation ID'; // Handle invalid ID
}

$conn->close();
echo json_encode($response);
?> 