<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Staff')) {
    header("Location: ../auth/Login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_item'])) {
    $name = $_POST['name'];
    $sku = $_POST['sku'];
    $description = $_POST['description'];
    $quantity = $_POST['quantity'];
    $amount = $_POST['amount'];
    $added_by = $_SESSION['user_id'];
    $added_by_username = $_SESSION['username'];

    $stmt = $conn->prepare("INSERT INTO inventory (name, sku, description, quantity, added_by, added_by_username, amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssissd", $name, $sku, $description, $quantity, $added_by, $added_by_username, $amount);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_item'])) {
    $item_id = $_POST['item_id'];
    $name = $_POST['name'];
    $sku = $_POST['sku'];
    $description = $_POST['description'];
    $quantity = $_POST['quantity'];
    $amount = $_POST['amount'];

    $stmt = $conn->prepare("UPDATE inventory SET name=?, sku=?, description=?, quantity=?, amount=? WHERE item_id=?");
    $stmt->bind_param("sssidi", $name, $sku, $description, $quantity, $amount, $item_id);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_item'])) {
    $item_id = $_POST['delete_item_id'];
    $stmt = $conn->prepare("DELETE FROM inventory WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->close();
}

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$inventory_query = "SELECT * FROM inventory ";

if (!empty($search_query)) {
    $search_param = "%" . $search_query . "%";
    $inventory_query .= "WHERE name LIKE ? OR sku LIKE ? ";
    $stmt_search = $conn->prepare($inventory_query . "ORDER BY quantity ASC");
    $stmt_search->bind_param("ss", $search_param, $search_param);
    $stmt_search->execute();
    $inventory_result = $stmt_search->get_result();
} else {
    $inventory_query .= "ORDER BY quantity ASC";
    $inventory_result = $conn->query($inventory_query);
}

$LOW_STOCK_THRESHOLD = 5;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVSU-RESERVE Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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

            <div class="modal fade" id="addItemModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add New Item</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Item Name</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">SKU</label>
                                    <input type="text" class="form-control" name="sku" required>
                                </div>
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
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="add_item" class="btn btn-primary">Add Item</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Current Inventory</h5>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="fas fa-plus"></i> Add New Item
                    </button>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
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
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>₱<?php echo number_format($item['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($item['added_by_username']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary edit-item" 
                                            data-item-id="<?php echo $item['item_id']; ?>"
                                            data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                            data-sku="<?php echo htmlspecialchars($item['sku']); ?>"
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
                </div>
            </div>

            <div class="modal fade" id="editItemModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Item</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="item_id" id="edit-item-id">
                                <div class="mb-3">
                                    <label class="form-label">Item Name</label>
                                    <input type="text" class="form-control" name="name" id="edit-name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">SKU</label>
                                    <input type="text" class="form-control" name="sku" id="edit-sku" required>
                                </div>
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
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="edit_item" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editButtons = document.querySelectorAll('.edit-item');
            const editModal = new bootstrap.Modal(document.getElementById('editItemModal'));

            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('edit-item-id').value = this.dataset.itemId;
                    document.getElementById('edit-name').value = this.dataset.name;
                    document.getElementById('edit-sku').value = this.dataset.sku;
                    document.getElementById('edit-description').value = this.dataset.description;
                    document.getElementById('edit-quantity').value = this.dataset.quantity;
                    document.getElementById('edit-amount').value = this.dataset.amount;
                    editModal.show();
                });
            });

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