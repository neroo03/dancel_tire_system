    <?php
    include '../includes/auth.php';
    include '../includes/config.php';

    // Handle product deletion
    if (isset($_GET['delete'])) {
        $productId = $_GET['delete'];
        try {
            $pdo->beginTransaction();
            
            // First delete from inventory (due to foreign key constraint)
            $stmt = $pdo->prepare("DELETE FROM inventory WHERE ProductID = ?");
            $stmt->execute([$productId]);
            
            // Then delete the product
            $stmt = $pdo->prepare("DELETE FROM product WHERE ProductID = ?");
            $stmt->execute([$productId]);
            
            $pdo->commit();
            $_SESSION['success'] = "Product deleted successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error deleting product: " . $e->getMessage();
        }
        header("Location: index.php");
        exit;
    }

    // Get all products with their inventory information
    $products = $pdo->query("
        SELECT p.*, i.QuantityAvailable 
        FROM product p 
        LEFT JOIN inventory i ON p.ProductID = i.ProductID
        ORDER BY p.Name
    ")->fetchAll();

    include '../includes/header.php';
    ?>

    <div class="container-fluid px-4">
        <h1 class="mt-4">Product Management</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Products</li>
        </ol>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-tags me-1"></i>
                        Product List
                    </div>
                    <a href="manage.php" class="btn btn-primary btn-sm">Add New Product</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Price</th>
                                <th>In Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo $product['ProductID']; ?></td>
                                    <td><?php echo htmlspecialchars($product['Name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['Type']); ?></td>
                                    <td><?php echo htmlspecialchars($product['Size']); ?></td>
                                    <td>₱<?php echo number_format($product['Price'], 2); ?></td>
                                    <td class="<?php echo ($product['QuantityAvailable'] ?? 0) < 5 ? 'text-danger fw-bold' : ''; ?>">
                                        <?php echo $product['QuantityAvailable'] ?? 0; ?>
                                        <?php if (($product['QuantityAvailable'] ?? 0) < 5): ?>
                                            <span class="badge bg-warning text-dark">Low Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="manage.php?id=<?php echo $product['ProductID']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="index.php?delete=<?php echo $product['ProductID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product? This will also remove its inventory records.')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>