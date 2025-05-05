<?php
include '../includes/auth.php';
include '../includes/config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $productId = $_POST['product_id'];
        $quantity = $_POST['quantity'];
        
        if (isset($_POST['inventory_id'])) {
            // Update existing inventory
            $stmt = $pdo->prepare("UPDATE inventory SET ProductID = ?, QuantityAvailable = ?, LastUpdated = CURDATE() WHERE InventoryID = ?");
            $stmt->execute([$productId, $quantity, $_POST['inventory_id']]);
            $_SESSION['success'] = "Inventory item updated successfully!";
        } else {
            // Add new inventory
            $stmt = $pdo->prepare("INSERT INTO inventory (ProductID, QuantityAvailable, LastUpdated) VALUES (?, ?, CURDATE())");
            $stmt->execute([$productId, $quantity]);
            $_SESSION['success'] = "Inventory item added successfully!";
        }
        
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Get inventory data if editing
$inventory = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE InventoryID = ?");
    $stmt->execute([$_GET['id']]);
    $inventory = $stmt->fetch();
}

// Get all products for dropdown
$products = $pdo->query("SELECT * FROM product ORDER BY Name")->fetchAll();

include '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo isset($inventory) ? 'Edit Inventory Item' : 'Add Inventory Item'; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Inventory</a></li>
        <li class="breadcrumb-item active"><?php echo isset($inventory) ? 'Edit' : 'Add'; ?></li>
    </ol>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="POST">
                <?php if (isset($inventory)): ?>
                    <input type="hidden" name="inventory_id" value="<?php echo $inventory['InventoryID']; ?>">
                <?php endif; ?>
                
                <div class="mb-3">
                    <label for="product_id" class="form-label">Product</label>
                    <select class="form-select" id="product_id" name="product_id" required>
                        <option value="">Select Product</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['ProductID']; ?>"
                                <?php echo (isset($inventory) && $inventory['ProductID'] == $product['ProductID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($product['Name']); ?> - 
                                <?php echo htmlspecialchars($product['Type']); ?> 
                                (<?php echo htmlspecialchars($product['Size']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="quantity" class="form-label">Quantity Available</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" 
                           value="<?php echo isset($inventory) ? $inventory['QuantityAvailable'] : '0'; ?>" min="0" required>
                </div>
                
                <button type="submit" class="btn btn-primary"><?php echo isset($inventory) ? 'Update Inventory' : 'Add Inventory'; ?></button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>