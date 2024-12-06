<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Staff')) {
    header("Location: ../auth/Login.php");
    exit();
}

// Function to generate the next SKU
function generate_next_sku($conn) {
    $stmt = $conn->prepare("SELECT MAX(sku) as max_sku FROM inventory");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row['max_sku']) {
        // Extract the numeric part from the SKU
        $number = intval(substr($row['max_sku'], 3));
        $next_number = $number + 1;
    } else {
        $next_number = 1;
    }

    return 'SKU' . str_pad($next_number, 3, '0', STR_PAD_LEFT);
}

// Function to handle image upload
function upload_image($file) {
    $target_dir = "uploads/"; // Ensure this directory exists and is writable
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    $target_file = $target_dir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Validate image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return ['success' => false, 'error' => "File is not an image."];
    }

    // Check file size (e.g., max 5MB)
    if ($file["size"] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => "Sorry, your file is too large."];
    }

    // Allow certain file formats
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if(!in_array($imageFileType, $allowed_types)) {
        return ['success' => false, 'error' => "Sorry, only JPG, JPEG, PNG & GIF files are allowed."];
    }

    // Generate a unique file name to prevent overwriting
    $unique_name = uniqid('img_', true) . '.' . $imageFileType;
    $target_file = $target_dir . $unique_name;

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'path' => $target_file];
    } else {
        return ['success' => false, 'error' => "Sorry, there was an error uploading your file."];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Adding a new item
    if (isset($_POST['add_item'])) {
        $name = $_POST['name'];
        // $sku = $_POST['sku']; // Remove manual SKU input
        $description = $_POST['description'];
        $quantity = $_POST['quantity'];
        $amount = $_POST['amount'];
        $added_by = $_SESSION['user_id'];
        $added_by_username = $_SESSION['username'];

        // Generate SKU automatically
        $sku = generate_next_sku($conn);

        // Handle image upload
        $image_url = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
            $upload_result = upload_image($_FILES['image']);
            if ($upload_result['success']) {
                $image_url = $upload_result['path'];
            } else {
                // Handle upload error (you can set a session message or similar)
                echo "<div class='alert alert-danger'>{$upload_result['error']}</div>";
                // Optionally, you might want to exit or continue without image
            }
        }

        // Prepare and execute insert statement
        $stmt = $conn->prepare("INSERT INTO inventory (name, sku, description, quantity, added_by, added_by_username, amount, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiisss", $name, $sku, $description, $quantity, $added_by, $added_by_username, $amount, $image_url);
        if($stmt->execute()){
            // Optionally, set a success message
            header("Location: inventory.php?msg=Item+added+successfully");
            exit();
        } else {
            echo "<div class='alert alert-danger'>Error adding item: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }

    // Editing an existing item
    if (isset($_POST['edit_item'])) {
        $item_id = $_POST['item_id'];
        $name = $_POST['name'];
        // $sku = $_POST['sku']; // SKU is auto-generated; typically not editable
        $description = $_POST['description'];
        $quantity = $_POST['quantity'];
        $amount = $_POST['amount'];

        // Handle image upload
        $image_url = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
            $upload_result = upload_image($_FILES['image']);
            if ($upload_result['success']) {
                $image_url = $upload_result['path'];
            } else {
                // Handle upload error
                echo "<div class='alert alert-danger'>{$upload_result['error']}</div>";
                // Optionally, you might want to exit or continue without image
            }
        }

        // If image is uploaded, include it in the update
        if ($image_url) {
            $stmt = $conn->prepare("UPDATE inventory SET name=?, description=?, quantity=?, amount=?, image_url=? WHERE item_id=?");
            $stmt->bind_param("ssidis", $name, $description, $quantity, $amount, $image_url, $item_id);
        } else {
            // Update without changing the image
            $stmt = $conn->prepare("UPDATE inventory SET name=?, description=?, quantity=?, amount=? WHERE item_id=?");
            $stmt->bind_param("ssidi", $name, $description, $quantity, $amount, $item_id);
        }

        if($stmt->execute()){
            header("Location: inventory.php?msg=Item+updated+successfully");
            exit();
        } else {
            echo "<div class='alert alert-danger'>Error updating item: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }

    // Deleting an item
    if (isset($_POST['delete_item'])) {
        $item_id = $_POST['delete_item_id'];

        // Check for existing reservations
        $stmt_check = $conn->prepare("SELECT COUNT(*) as count FROM reservations WHERE item_id = ?");
        $stmt_check->bind_param("i", $item_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $row = $result_check->fetch_assoc();
        $stmt_check->close();

        if ($row['count'] > 0) {
            echo "<div class='alert alert-danger'>Cannot delete item. There are existing reservations for this item.</div>";
        } else {
            // Soft delete the inventory item
            $stmt_delete = $conn->prepare("UPDATE inventory SET deleted = TRUE WHERE item_id = ?");
            $stmt_delete->bind_param("i", $item_id);
            $stmt_delete->execute();
            $stmt_delete->close();

            header("Location: inventory.php?msg=Item+deleted+successfully");
            exit();
        }
    }
}

// Handling search
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
if (!empty($search_query)) {
    $search_param = "%" . $search_query . "%";
    $inventory_query = "SELECT * FROM inventory WHERE deleted = FALSE AND (name LIKE ? OR sku LIKE ?) ORDER BY quantity ASC";
    $stmt_search = $conn->prepare($inventory_query);
    $stmt_search->bind_param("ss", $search_param, $search_param);
    $stmt_search->execute();
    $inventory_result = $stmt_search->get_result();
} else {
    $inventory_query = "SELECT * FROM inventory WHERE deleted = FALSE ORDER BY quantity ASC";
    $inventory_result = $conn->query($inventory_query);
}

$LOW_STOCK_THRESHOLD = 5;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- [Keep your existing head content] -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVSU-RESERVE Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* [Keep your existing styles] */
        :root {
            --primary-red: #8B0000;
            --accent-yellow: #FFD700;
            --light-gray: #f4f6f9;
            --white: #ffffff;
            --soft-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            background-color: var(--light-gray);
            color: #333;
        }
        .low-stock { background-color: #ffdddd; }
        .zero-stock { background-color: #ff9999; }

        .dashboard-container {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, var(--primary-red), #6D0000);
            padding: 20px 0;
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-yellow);
            padding: 15px;
            margin-bottom: 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header img {
            height: 40px;
            margin-right: 10px;
        }

        .nav-button {
            display: flex;
            align-items: center;
            width: 85%;
            margin: 10px auto;
            padding: 12px 15px;
            background-color: rgba(255, 215, 0, 0.1);
            border-radius: 8px;
            color: var(--white);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .nav-button:hover {
            background-color: var(--accent-yellow);
            color: var(--primary-red);
            transform: translateX(10px);
        }

        .nav-button i {
            margin-right: 10px;
            opacity: 0.8;
        }

        .main-content {
            flex-grow: 1;
            overflow-y: auto;
            padding: 30px;
            background-color: var(--light-gray);
        }

        .search-container {
            margin-bottom: 20px;
        }

        /* Additional styling for images in table */
        .item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="sidebar-header">
                EVSU-RESERVE
            </div>
            <a href="dashboard.php" class="nav-button">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="inventory.php" class="nav-button">
                <i class="fas fa-box"></i> Inventory
            </a>
            <a href="reservation.php" class="nav-button">
                <i class="fas fa-calendar-alt"></i> Reservations
            </a>
            <a href="../auth/Logout.php" class="nav-button" style="margin-top: auto;">
                <i class="fas fa-sign-out-alt"></i> Exit
            </a>
        </div>

        <div class="main-content container-fluid">
            <h2 class="my-4">Inventory Management</h2>

            <div class="search-container">
                <form method="GET" class="d-flex">
                    <input 
                        type="search" 
                        name="search" 
                        class="form-control" 
                        placeholder="Search by Name or SKU" 
                        value="<?php echo htmlspecialchars($search_query); ?>"
                    >
                    <button type="submit" class="btn btn-primary ms-2">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if (!empty($search_query)): ?>
                        <a href="inventory.php" class="btn btn-secondary ms-2">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (!empty($search_query)): ?>
                <div class="alert alert-info">
                    Search results for: "<?php echo htmlspecialchars($search_query); ?>"
                    (<?php echo $inventory_result->num_rows; ?> item(s) found)
                </div>
            <?php endif; ?>

            <!-- Add Item Modal -->
            <div class="modal fade" id="addItemModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add New Item</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Item Name</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <!-- Removed SKU input since it's auto-generated -->
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" class="form-control" name="quantity" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Price</label>
                                    <input type="number" step="0.01" class="form-control" name="amount" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Image (Optional)</label>
                                    <input type="file" class="form-control" name="image" accept="image/*">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="add_item" class="btn btn-primary">Add Item</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Current Inventory</h5>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="fas fa-plus"></i> Add New Item
                    </button>
                </div>
                <div class="card-body">
                    <?php if($inventory_result->num_rows > 0): ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Image</th> <!-- New Image Column -->
                                    <th>Name</th>
                                    <th>SKU</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Added By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($item = $inventory_result->fetch_assoc()): ?>
                                    <tr class="<?php 
                                        echo $item['quantity'] == 0 ? 'zero-stock' : 
                                            ($item['quantity'] <= $LOW_STOCK_THRESHOLD ? 'low-stock' : ''); 
                                    ?>">
                                        <td>
                                            <?php if($item['image_url'] && file_exists($item['image_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="Image" class="item-image">
                                            <?php else: ?>
                                                <i class="fas fa-image fa-lg text-muted"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>₱<?php echo number_format($item['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($item['added_by_username']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary edit-item" 
                                                data-item-id="<?php echo $item['item_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                                data-description="<?php echo htmlspecialchars($item['description']); ?>"
                                                data-quantity="<?php echo $item['quantity']; ?>"
                                                data-amount="<?php echo $item['amount']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-item" 
                                                data-item-id="<?php echo $item['item_id']; ?>"
                                                data-item-name="<?php echo htmlspecialchars($item['name']); ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-center">No items found in the inventory.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Edit Item Modal -->
            <div class="modal fade" id="editItemModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Item</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="modal-body">
                                <input type="hidden" name="item_id" id="edit-item-id">
                                <div class="mb-3">
                                    <label class="form-label">Item Name</label>
                                    <input type="text" class="form-control" name="name" id="edit-name" required>
                                </div>
                                <!-- SKU is auto-generated and typically not editable. If editable, include here -->
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" id="edit-description"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" class="form-control" name="quantity" id="edit-quantity" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Price</label>
                                    <input type="number" step="0.01" class="form-control" name="amount" id="edit-amount" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Change Image (Optional)</label>
                                    <input type="file" class="form-control" name="image" accept="image/*">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="edit_item" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Item Modal -->
            <div class="modal fade" id="deleteItemModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title">Delete Item</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete <strong id="delete-item-name"></strong>?</p>
                                <input type="hidden" name="delete_item_id" id="delete-item-id">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="delete_item" class="btn btn-danger">Delete</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Edit Item
            const editButtons = document.querySelectorAll('.edit-item');
            const editModal = new bootstrap.Modal(document.getElementById('editItemModal'));

            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('edit-item-id').value = this.dataset.itemId;
                    document.getElementById('edit-name').value = this.dataset.name;
                    document.getElementById('edit-description').value = this.dataset.description;
                    document.getElementById('edit-quantity').value = this.dataset.quantity;
                    document.getElementById('edit-amount').value = this.dataset.amount;
                    editModal.show();
                });
            });

            // Delete Item
            const deleteButtons = document.querySelectorAll('.delete-item');
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteItemModal'));

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const itemId = this.dataset.itemId;
                    const itemName = this.dataset.itemName;
                    document.getElementById('delete-item-id').value = itemId;
                    document.getElementById('delete-item-name').textContent = itemName;
                    deleteModal.show();
                });
            });
        });
    </script>
</body>
</html>