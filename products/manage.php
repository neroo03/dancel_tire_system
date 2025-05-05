<?php
include '../includes/auth.php';
include '../includes/config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['add_product'])) {
            // Add new product
            $stmt = $pdo->prepare("INSERT INTO product (Name, Type, Size, Price) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_POST['name'],
                $_POST['type'],
                $_POST['size'],
                $_POST['price']
            ]);
            
            $productId = $pdo->lastInsertId();
            
            // Add to inventory
            $stmt = $pdo->prepare("INSERT INTO inventory (ProductID, QuantityAvailable, LastUpdated) VALUES (?, ?, CURDATE())");
            $stmt->execute([$productId, $_POST['quantity']]);
            
            $_SESSION['success'] = "Product added successfully!";
        } elseif (isset($_POST['update_product'])) {
            // Update existing product
            $stmt = $pdo->prepare("UPDATE product SET Name = ?, Type = ?, Size = ?, Price = ? WHERE ProductID = ?");
            $stmt->execute([
                $_POST['name'],
                $_POST['type'],
                $_POST['size'],
                $_POST['price'],
                $_POST['product_id']
            ]);
            
            $_SESSION['success'] = "Product updated successfully!";
        }
        
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Get product data if editing
$product = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT p.*, i.QuantityAvailable 
                          FROM product p 
                          JOIN inventory i ON p.ProductID = i.ProductID 
                          WHERE p.ProductID = ?");
    $stmt->execute([$_GET['id']]);
    $product = $stmt->fetch();
}

include '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo isset($product) ? 'Edit Product' : 'Add New Product'; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Products</a></li>
        <li class="breadcrumb-item active"><?php echo isset($product) ? 'Edit' : 'Add'; ?></li>
    </ol>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="POST">
                <?php if (isset($product)): ?>
                    <input type="hidden" name="product_id" value="<?php echo $product['ProductID']; ?>">
                <?php endif; ?>
                
                <div class="mb-3">
                    <label for="name" class="form-label">Product Name</label>
                    <input type="text" class="form-control" id="name" name="name" 
                           value="<?php echo isset($product) ? htmlspecialchars($product['Name']) : ''; ?>" required>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="">Select Type</option>
                            <option value="Brand New Tire" <?php echo (isset($product) && $product['Type'] == 'Brand New Tire') ? 'selected' : ''; ?>>Brand New Tire</option>
                            <option value="Second-hand Tire" <?php echo (isset($product) && $product['Type'] == 'Second-hand Tire') ? 'selected' : ''; ?>>Second-hand Tire</option>
                            <option value="Rim" <?php echo (isset($product) && $product['Type'] == 'Rim') ? 'selected' : ''; ?>>Rim</option>
                            <option value="Lug Nut" <?php echo (isset($product) && $product['Type'] == 'Lug Nut') ? 'selected' : ''; ?>>Lug Nut</option>
                            <option value="Tire Interior" <?php echo (isset($product) && $product['Type'] == 'Tire Interior') ? 'selected' : ''; ?>>Tire Interior</option>
                            <option value="Center Cap" <?php echo (isset($product) && $product['Type'] == 'Center Cap') ? 'selected' : ''; ?>>Center Cap</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="size" class="form-label">Size</label>
                        <input type="text" class="form-control" id="size" name="size" 
                               value="<?php echo isset($product) ? htmlspecialchars($product['Size']) : ''; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="price" class="form-label">Price (₱)</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" 
                               value="<?php echo isset($product) ? $product['Price'] : ''; ?>" required>
                    </div>
                </div>
                
                <?php if (!isset($product)): ?>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Initial Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="0" value="0" required>
                    </div>
                <?php endif; ?>
                
                <button type="submit" name="<?php echo isset($product) ? 'update_product' : 'add_product'; ?>" 
                        class="btn btn-primary">
                    <?php echo isset($product) ? 'Update Product' : 'Add Product'; ?>
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>