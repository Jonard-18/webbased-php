<?php
include 'db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);
$reservation_id = $data['reservation_id'] ?? null;

if ($reservation_id) {
    $sql = "UPDATE reservations SET status = 'Fulfilled', updated_at = NOW() WHERE reservation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reservation_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid reservation ID']);
}

$conn->close();
?>
