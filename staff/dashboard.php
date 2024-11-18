<?php
include 'db_connect.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: ../auth/Login.php");
    exit();
}
// Fetch inventory data
$sql = "SELECT * FROM inventory";
$result = $conn->query($sql);

$inventory_data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $inventory_data[] = $row;
    }
}

// Fetch reservations data
$sql = "SELECT r.*, u.username, u.email, i.name as item_name 
        FROM reservations r 
        JOIN users u ON r.user_id = u.user_id 
        JOIN inventory i ON r.item_id = i.item_id";
$result = $conn->query($sql);

$reservations_data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reservations_data[] = $row;
    }
}

// Convert PHP arrays to JSON for use in JavaScript
$inventory_json = json_encode($inventory_data);
$reservations_json = json_encode($reservations_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style_dash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header>
        <h1>EVSU Dashboard</h1>
        <div class="user-profile">
            <div class="dropdown">
                <button class="dropbtn">Profile ▼</button>
                <div class="dropdown-content">
                    <a href="../auth/Logout.php" onclick="logout()">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <div class="sidebar">
        <a href="#" id="inventory" onclick="showFeature('inventory')"><i class="fas fa-box"></i>View Inventory</a>
        <a href="#" id="reservations" onclick="showFeature('reservations')"><i class="fas fa-calendar"></i>View and Process Reservations</a>
        <a href="#" id="availability" onclick="showFeature('availability')"><i class="fas fa-search"></i>Item Availability</a>
        <a href="#" id="fulfillments" onclick="showFeature('fulfillments')"><i class="fas fa-check"></i>Mark Reservations</a>
    </div>

    <!-- Main Content Area -->
    <div class="content">
        <div id="inventory-content" class="feature-content">
            <h2>Current Inventory Levels</h2>
            <div class="content-list">
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Name</th>
                            <th>Quantity</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventory_data as $item): ?>
                        <tr>
                            <td><?php echo $item['item_id']; ?></td>
                            <td><?php echo $item['name']; ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo $item['quantity'] > 0 ? 'Available' : 'Out of Stock'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div id="reservations-content" class="feature-content" style="display: none;">
            <h2>View and Process Reservations</h2>
            <div class="content-list">
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Reserved By</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations_data as $reservation): ?>
                        <tr>
                            <td>
                                <div class="item-info">
                                    <span class="item-name"><?php echo $reservation['item_name']; ?></span>
                                    <span class="item-id">ID: <?php echo $reservation['item_id']; ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="user-info">
                                    <span class="username"><?php echo $reservation['username']; ?></span>
                                    <span class="email"><?php echo $reservation['email']; ?></span>
                                </div>
                            </td>
                            <td class="quantity"><?php echo $reservation['reserved_quantity']; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($reservation['status']); ?>">
                                    <?php echo $reservation['status']; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($reservation['reserved_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div id="availability-content" class="feature-content" style="display: none;">
            <h2>Item Availability</h2>
            <div class="content-list">
                <!-- Availability content will be populated by JavaScript -->
            </div>
        </div>
        <div id="fulfillments-content" class="feature-content" style="display: none;">
            <h2>Mark Reservations as Fulfilled</h2>
            <div class="content-list" id="fulfillments-list">
                <!-- Fulfillments content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        const inventoryData = <?php echo $inventory_json; ?>;
        const reservationsData = <?php echo $reservations_json; ?>;

        document.addEventListener('DOMContentLoaded', function () {
            const dropdownBtn = document.querySelector('.dropbtn');
            const dropdownContent = document.querySelector('.dropdown-content');

            dropdownBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                dropdownContent.classList.toggle('show');
            });

            window.addEventListener('click', function () {
                if (dropdownContent.classList.contains('show')) {
                    dropdownContent.classList.remove('show');
                }
            });
        });

        function showFeature(feature) {
            const contents = document.querySelectorAll('.feature-content');
            contents.forEach(content => content.style.display = 'none');

            document.getElementById(`${feature}-content`).style.display = 'block';

            if (feature === 'availability') {
                displayAvailability();
            } else if (feature === 'fulfillments') {
                displayFulfillments();
            }
        }

        function displayAvailability() {
            const contentList = document.querySelector('#availability-content .content-list');
            contentList.innerHTML = `
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${inventoryData.map(item => `
                            <tr>
                                <td>${item.name}</td>
                                <td>${item.quantity}</td>
                                <td>${item.quantity > 0 ? 'Available' : 'Out of Stock'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }

        function displayFulfillments() {
            const contentList = document.getElementById('fulfillments-list');
            const pendingReservations = reservationsData.filter(reservation => reservation.status === 'Pending');

            if (pendingReservations.length > 0) {
                contentList.innerHTML = `
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Reserved By</th>
                                <th>Quantity</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${pendingReservations.map(reservation => `
                                <tr>
                                    <td>
                                        <div class="item-info">
                                            <span class="item-name">${reservation.item_name}</span>
                                            <span class="item-id">ID: ${reservation.item_id}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <span class="username">${reservation.username}</span>
                                            <span class="email">${reservation.email}</span>
                                        </div>
                                    </td>
                                    <td class="quantity">${reservation.reserved_quantity}</td>
                                    <td>${new Date(reservation.reserved_at).toLocaleDateString()}</td>
                                    <td>
                                        <button class="fulfill-btn" onclick="markAsFulfilled(${reservation.reservation_id})">
                                            Mark as Fulfilled
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            } else {
                contentList.innerHTML = '<p class="no-data">No pending reservations.</p>';
            }
        }

        function markAsFulfilled(reservationId) {
            fetch('mark_fulfilled.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ reservation_id: reservationId }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Reservation marked as fulfilled!');
                    location.reload(); // Reload to update the list
                } else {
                    alert('Error marking reservation as fulfilled.');
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function logout() {
            window.location.href = '../auth/Login.php';
        }
    </script>
</body>
</html>
